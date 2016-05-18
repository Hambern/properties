<?php namespace Hambern\Properties\Models;

/**
 * Property Model
 */
class Property extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'hambern_properties_properties';

    public $hasMany = [
      'options' => ['Hambern\Properties\Models\Option'],
    ];

    public function getTypeOptions() {
      return [
        'select' => 'hambern.properties::lang.types.select',
        'buttongroup' => 'hambern.properties::lang.types.buttongroup',
        'checkboxlist' => 'hambern.properties::lang.types.checkboxlist',
        'text' => 'hambern.properties::lang.types.text',
        'textarea' => 'hambern.properties::lang.types.textarea',
        'number' => 'hambern.properties::lang.types.number',
      ];
    }
}
