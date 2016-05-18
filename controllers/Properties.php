<?php namespace Hambern\Properties\Controllers;

use BackendMenu;
use Flash;
use Lang;
use Hambern\Properties\Models\Property;

/**
 * Properties Back-end Controller
 */
class Properties extends Controller
{
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Jiri.JKShop', 'jkshop', 'properties');
    }

    /**
     * Deleted checked properties.
     */
    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $propertyId) {
                if (!$property = Property::find($propertyId)) continue;
                $property->delete();
            }

            Flash::success(Lang::get('hambern.properties::lang.properties.delete_selected_success'));
        }
        else {
            Flash::error(Lang::get('hambern.properties::lang.properties.delete_selected_empty'));
        }

        return $this->listRefresh();
    }
}
