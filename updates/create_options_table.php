<?php namespace Hambern\Properties\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateOptionsTable extends Migration
{

    public function up()
    {
        Schema::create('hambern_properties_options', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('property_id')->nullable()->index();
            $table->string('title');
            $table->nullableTimestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hambern_properties_options');
    }

}
