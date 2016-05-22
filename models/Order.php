<?php namespace Hambern\Properties\Models;

use Model;
use DB;
use Mail;
use App;
use Lang;
use Jiri\JKShop\Models\Carrier;
use Jiri\JKShop\Models\Product;
use Jiri\JKShop\Models\Order as BaseClass;

/**
 * Order Model
 */
class Order extends BaseClass
{
    public function createFromBasket($basket) {
        $jkshopSetting = \Jiri\JKShop\Models\Settings::instance();

        // User - Rainlab User if exist
        if (class_exists("\RainLab\User\Models\User")) {
            $user = \RainLab\User\Facades\Auth::getUser();
            if (isset($user)) {
                $this->user_id = \RainLab\User\Facades\Auth::getUser()->id;
            }
        }

        // Delivery address
        $this->ds_first_name = $basket["ds_first_name"];
        $this->ds_last_name = $basket["ds_last_name"];
        $this->ds_address = $basket["ds_address"];
        $this->ds_address_2 = $basket["ds_address_2"];
        $this->ds_postcode = $basket["ds_postcode"];
        $this->ds_city = $basket["ds_city"];
        $this->ds_country = $basket["ds_country"];

        // Invoice address
        $this->is_first_name = $basket["is_first_name"];
        $this->is_last_name = $basket["is_last_name"];
        $this->is_address = $basket["is_address"];
        $this->is_address_2 = $basket["is_address_2"];
        $this->is_postcode = $basket["is_postcode"];
        $this->is_city = $basket["is_city"];
        $this->is_country = $basket["is_country"];

        // Carrier
        $this->carrier = Carrier::find($basket["shipping_id"]);

        // Price
        $this->total_price_without_tax = $basket["total_price_without_tax"];
        $this->total_tax = $basket["total_tax"];
        $this->total_price = $basket["total_price"];

        $this->shipping_price_without_tax = $basket["shipping_price_without_tax"];
        $this->shipping_tax = $basket["shipping_tax"];
        $this->shipping_price = $basket["shipping_price"];

        // Payment method
        $this->payment_method = $basket["payment_method_id"];

        // Contact
        $this->contact_email = $basket["contact_email"];
        $this->contact_phone = $basket["contact_phone"];

        // OrderStatus
        switch ($this->payment_method) {
            case 1: // "Cash on delivery"
                $this->orderstatus = $jkshopSetting->cash_on_delivery_order_status_before;
                break;
            case 2: // "Bank transfer"
                $this->orderstatus = $jkshopSetting->bank_transfer_order_status_before;
                break;
            case 3: // "Paypal"
                $this->orderstatus = $jkshopSetting->paypal_order_status_before;
                break;
        }

        // Products
        $products_json = [];
        foreach ($basket["products"] as $md5 => $productJson) {

            $qty = $productJson["basket_quantity"];

            // check and remove qty form stock
            $product = Product::find($productJson["product_id"]);
            $qty = $product->orderProduct($qty, true);
            // i call this immediately get basket and this method check stock availability

            // $basket["products"][$id]["product"]
            $products_json[] = [
                "product_id" => $productJson["product_id"],
                "quantity" => $productJson["basket_quantity"],
                "options" => $productJson["options"],
                "total_price_without_tax" => $productJson["total_price_without_tax"],
                "total_tax" => $productJson["total_tax"],
                "total_price" => $productJson["total_price"],
            ];
        }
        $this->products_json = $products_json;
    }
}
