<?php namespace Hambern\Properties\Controllers;

use BackendMenu;
use Backend\Classes\Controller as BaseController;
use Flash;
use Lang;

/**
 * Options Back-end Controller
 */
class Controller extends BaseController
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.RelationController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';
}
