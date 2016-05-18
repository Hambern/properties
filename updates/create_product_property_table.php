<?php namespace Hambern\Properties\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProductPropertyTable extends Migration
{

    public function up()
    {
        Schema::create('hambern_product_property', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('product_id')->index();
            $table->integer('property_id')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hambern_product_property');
    }

}
