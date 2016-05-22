<?php namespace Hambern\Properties;

use Cart;
use Backend;
use Event;
use Symfony\Component\Console\Output\ConsoleOutput;
use System\Classes\PluginBase;
use Jiri\JKShop\Models\Product;
use Jiri\JKShop\Components\Basket;

/**
* Properties Plugin Information File
*/
class Plugin extends PluginBase
{

  public $require = ['Jiri.JKShop'];

  public function pluginDetails()
  {
    return [
      'name'        => 'hambern.properties::lang.plugin.name',
      'description' => 'hambern.properties::lang.plugin.description',
      'author'      => 'Hambern',
      'icon'        => 'icon-cog',
    ];
  }

  public function boot()
  {
    $this->extendNavigation();
    $this->extendModel();
    $this->extendField();
  }

  public function extendNavigation()
  {
    Event::listen('backend.menu.extendItems', function($manager)
    {
      $manager->addMainMenuItems('Jiri.JKShop', [
        'jkshop' => [
          'url' => Backend::url('hambern/properties/orders'),
        ]
      ]);
      $manager->addSideMenuItems('Jiri.JKShop', 'jkshop', [
        'properties' => [
          'label'       => 'hambern.properties::lang.properties.menu_label',
          'icon'        => 'icon-cog',
          'code'        => 'properties',
          'owner'       => 'Jiri.JKShop',
          'url'         => Backend::url('hambern/properties/properties')
        ]
      ]);
      $manager->addSideMenuItems('Jiri.JKShop', 'jkshop', [
        'orders' => [
          'url'         => Backend::url('hambern/properties/orders')
        ]
      ]);
    });
  }

  public function registerComponents() {
    return [
      'Hambern\Properties\Components\ProductDetail' => 'myProductDetail',
      'Hambern\Properties\Components\Basket' => 'myBasket',
    ];
  }

  public function extendModel()
  {
    Product::extend(function ($model) {
      $model->belongsToMany['properties'] = [
        'Hambern\Properties\Models\Property',
        'table' => 'hambern_product_property',
      ];
    });
  }

  public function extendField()
  {
    Event::listen('backend.form.extendFields', function($widget) {
      if (!$widget->getController() instanceof \Jiri\JKShop\Controllers\Products) return;
      if (!$widget->model instanceof \Jiri\JKShop\Models\Product) return;
      $widget->addTabFields([
        'properties' => [
          'label' => 'hambern.properties::lang.properties.menu_label',
          'nameFrom' => 'title',
          'type' => 'relation',
          'tab' => 'hambern.properties::lang.plugin.name',
        ]
      ]);
    });
  }

  public function register()
  {
    set_exception_handler([$this, 'handleException']);
  }

  public function handleException($e)
  {
    if (! $e instanceof Exception) {
      $e = new \Symfony\Component\Debug\Exception\FatalThrowableError($e);
    }

    $handler = $this->app->make('Illuminate\Contracts\Debug\ExceptionHandler');
    $handler->report($e);

    if ($this->app->runningInConsole()) {
      $handler->renderForConsole(new ConsoleOutput, $e);
    } else {
      $handler->render($this->app['request'], $e)->send();
    }
  }
}
