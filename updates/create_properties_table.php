<?php namespace Hambern\Properties\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePropertiesTable extends Migration
{

    public function up()
    {
        Schema::create('hambern_properties_properties', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title');
            $table->string('placeholder');
            $table->string('type');
            $table->nullableTimestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hambern_properties_properties');
    }

}
