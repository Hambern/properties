<?php namespace Hambern\Properties\Components;

use Cms\Classes\ComponentBase;
use Session;
use Cms\Classes\Page;
use Jiri\JKShop\Components\Basket as BaseClass;
use Jiri\JKShop\Models\Product;
use Hambern\Properties\Models\Order;
use Jiri\JKShop\Models\Settings;


class Basket extends BaseClass
{
  /**
  * Get Basket from session
  *
  * @return basket data as array
  */
  public function getSessionBasket() {

    $jkshopSetting = \Jiri\JKShop\Models\Settings::instance();

    $defaultEmptyBasket = [
      "products" => []
    ];

    Session::forget('key');
    $basketJson = Session::get('jkshop-basket', json_encode($defaultEmptyBasket));
    $basket = json_decode($basketJson, true);

    // ---------------------------------------------------------------------
    // get all products obj
    // - check qty vs stock
    // ---------------------------------------------------------------------
    $basket["additional_shipping_fees"] = 0;
    foreach ($basket["products"] as $id => $productJson) {
      $product = \Jiri\JKShop\Models\Product::find($productJson["product_id"]);
      $product->setUrl($this->property('productPage'), $this->controller);

      // check qty vs stoch
      $basket["products"][$id]["basket_quantity"] = $product->orderProduct($basket["products"][$id]["basket_quantity"], false);

      $basket["products"][$id]["product"] = $product;

      $productFinalTax = $product->tax->getTaxFromPriceWithTax($product->getFinalPrice());
      $basket["products"][$id]["total_price_without_tax"] = ($product->getFinalPrice() - $productFinalTax) * $basket["products"][$id]["basket_quantity"];
      $basket["products"][$id]["total_tax"] =  $productFinalTax * $basket["products"][$id]["basket_quantity"];
      $basket["products"][$id]["total_price"] = $product->getFinalPrice() * $basket["products"][$id]["basket_quantity"];

      if ($product->additional_shipping_fees > 0)  {
        $basket["additional_shipping_fees"] += $product->additional_shipping_fees;
      }
    }

    // ---------------------------------------------------------------------

    // ---------------------------------------------------------------------
    // re-compute all total values, max values
    // ---------------------------------------------------------------------
    $basket["total_price_without_tax"] = 0;
    $basket["total_tax"] = 0;
    $basket["total_price"] = 0;
    $basket["total_weight"] = 0;

    $basket["max_product_width"] = 0;
    $basket["max_product_height"] = 0;
    $basket["max_product_depth"] = 0;

    foreach ($basket["products"] as $id => $productJson) {
      $basket["total_price_without_tax"] += $productJson["total_price_without_tax"];
      $basket["total_tax"] += $productJson["total_tax"];
      $basket["total_price"] += $productJson["total_price"];

      $basket["total_weight"] += $productJson["product"]->package_weight;

      if ($productJson["product"]->package_width > $basket["max_product_width"]) { $basket["max_product_width"] = $productJson["product"]->package_width; }
      if ($productJson["product"]->package_height > $basket["max_product_height"]) { $basket["max_product_height"] = $productJson["product"]->package_height; }
      if ($productJson["product"]->package_depth > $basket["max_product_depth"]) { $basket["max_product_depth"] = $productJson["product"]->package_depth; }
    }
    // round 2 places
    $basket["total_price_without_tax"] = round($basket["total_price_without_tax"], 2);
    $basket["total_tax"] = round($basket["total_tax"], 2);
    $basket["total_price"] = round($basket["total_price"], 2);

    // add formated price
    $basket["total_price_without_tax_formatted"] = $jkshopSetting->getPriceFormatted($basket["total_price_without_tax"]);
    $basket["total_tax_formatted"] = $jkshopSetting->getPriceFormatted($basket["total_tax"]);
    $basket["total_price_formatted"] = $jkshopSetting->getPriceFormatted($basket["total_price"]);
    // ---------------------------------------------------------------------

    // ---------------------------------------------------------------------
    // Shipping
    // ---------------------------------------------------------------------
    $shipping_options = \Jiri\JKShop\Models\Carrier::where("active",1)->get();
    // filter for this order
    foreach ($shipping_options as $key => $shipping) {
      if ($shipping->isAvaliableForThisOrder($basket) == false ) {
        unset($shipping_options[$key]);
      }
    }
    $basket["shipping_options"] = $shipping_options;

    if (isset($basket["shipping_id"])) {
      $basket["shipping"] = \Jiri\JKShop\Models\Carrier::find($basket["shipping_id"]);
      $shippingCurrentPrice = $basket["shipping"]->getCurrentPrice($basket);
      $shippingCurrentPriceWithTax = $basket["shipping"]->getCurrentPriceWithTax($basket);
      $shippingCurrentTax = ($shippingCurrentPriceWithTax-$shippingCurrentPrice);

      // round 2 places
      $shippingCurrentPrice = round($shippingCurrentPrice, 2);
      $shippingCurrentPriceWithTax = round($shippingCurrentPriceWithTax, 2);
      $shippingCurrentTax = round($shippingCurrentTax, 2);

      $basket["shipping_price_without_tax"] = $shippingCurrentPrice;
      $basket["shipping_tax"] = $shippingCurrentTax;
      $basket["shipping_price"] = $shippingCurrentPriceWithTax;

      $basket["total_price_without_tax_with_shipping"] = $basket["total_price_without_tax"] + $shippingCurrentPrice;
      $basket["total_tax_with_shipping"] = $basket["total_tax"] + $shippingCurrentTax;
      $basket["total_price_with_shipping"] = $basket["total_price"] + $shippingCurrentPriceWithTax;

      // add formated price
      $basket["total_price_without_tax_with_shipping_formatted"] = $jkshopSetting->getPriceFormatted($basket["total_price_without_tax_with_shipping"]);
      $basket["total_tax_with_shipping_formatted"] = $jkshopSetting->getPriceFormatted($basket["total_tax_with_shipping"]);
      $basket["total_price_with_shipping_formatted"] = $jkshopSetting->getPriceFormatted($basket["total_price_with_shipping"]);
    }
    // ---------------------------------------------------------------------

    // ---------------------------------------------------------------------
    // Payment method
    // ---------------------------------------------------------------------
    $order = new \Jiri\JKShop\Models\Order();
    $basket["payment_method_options"] = $order->getPaymentMethodOptions(true);

    if (isset($basket["payment_method_id"])) {
      $basket["payment_method"] = $order->getPaymentMethodOptions()[$basket["payment_method_id"]];
    }
    // ---------------------------------------------------------------------

    return $basket;
  }

  /**
   * Create order, sent email, redirect to paypall, etc..
   *
   */
  public function onCompleteOrder() {
      $jkshopSetting = Settings::instance();

      // create order
      $basket = $this->getSessionBasket();
      $order = new Order();
      $order->createFromBasket($basket);
      $order->save();
      $order = Order::find($order->id);

      // clear basket
      Session::forget('jkshop-basket');

      // response tanks or redirect on paypal
      $data = [
          "order" => $order,
          "jkshopSetting" => $jkshopSetting
      ];
      switch ($order->payment_method) {
          case 1: //"Cash on delivery"
              return [
                  $this->property("idElementWrapperBasketComponent") => $this->renderPartial('@basket-4-thanks-cash-on-delivery')
              ];
          case 2: //"Bank transfer"
              return [
                  $this->property("idElementWrapperBasketComponent") => $this->renderPartial('@basket-4-thanks-bank-transfer')
              ];
          case 3: // "Paypal"
              return [
                  $this->property("idElementWrapperBasketComponent") => $this->renderPartial('@basket-4-thanks-paypal', $data)
              ];
      }
  }

  /**
  * Ajax call - add to basket
  *
  */
  public function onAddToBasket() {
    $info = input();
    $md5 = md5(json_encode($info));
    $basket = $this->getSessionBasket();
    $product = Product::where("active",1)->where("available_for_order",1)->find($info['id']);

    // minimal order
    $qtyOperation = 1;
    if ($product->minimum_quantity > 1) {
      $qtyOperation = $qtyOperation * $product->minimum_quantity;
    }

    if (isset($product)) {
      if (array_key_exists($md5, $basket["products"])) {
        $basket["products"][$md5]["basket_quantity"] = $basket["products"][$md5]["basket_quantity"] + $qtyOperation;
      }
      else {
        $basket["products"][$md5] = [
          "product_id" => $product->id,
          "basket_quantity" => $qtyOperation,
          "options" => input('options')
        ];
      }
      \Debugbar::info($basket);
      $basket = $this->setSessionBasket($basket);
      \Debugbar::info($basket);
    }

    return [
      $this->property("idElementTotalCartPrice") => $basket["total_price_formatted"]
    ];
  }

  /**
   * +-1 Quantity on basket product
   *
   * @return type
   */
  public function onBasketProductChangeQunatity() {
      $basket = $this->getSessionBasket();
      $id = input('id');
      $md5 = input('md5');
      $qtyOperation = input('qty_operation');

      $product = \Jiri\JKShop\Models\Product::find($id);

      if ($product->minimum_quantity > 1) {
          $qtyOperation = $qtyOperation * $product->minimum_quantity;
      }

      if (array_key_exists($md5, $basket["products"])) {
          if ($qtyOperation > 0) {
              // plus
              $basket["products"][$md5]["basket_quantity"] += $qtyOperation;
          }
          else {
              // minus
              $basket["products"][$md5]["basket_quantity"] += $qtyOperation;
              if ($basket["products"][$md5]["basket_quantity"] <= 0) {
                  unset($basket["products"][$md5]);
              }
          }
      }
      $basket = $this->setSessionBasket($basket);

      $data = array();
      $data["basket"] = $basket;
      $data["jkshopSetting"] = \Jiri\JKShop\Models\Settings::instance();

      return [
          $this->property("idElementWrapperBasketComponent") => $this->renderPartial('@basket-0', $data),
          $this->property("idElementTotalCartPrice") => $basket["total_price_formatted"]
      ];

  }
}
