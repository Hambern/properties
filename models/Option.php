<?php namespace Hambern\Properties\Models;

/**
 * Option Model
 */
class Option extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'hambern_properties_options';

    public $belongsTo = [
      'property' => ['Hambern\Properties\Models\Property'],
    ];
}
