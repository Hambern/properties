<?php namespace Hambern\Properties\Controllers;

use BackendMenu;
use Flash;
use Lang;
use Hambern\Properties\Models\Option;

/**
 * Options Back-end Controller
 */
class Options extends Controller
{
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Hambern.Properties', 'properties', 'options');
    }

    /**
     * Deleted checked options.
     */
    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $optionId) {
                if (!$option = Option::find($optionId)) continue;
                $option->delete();
            }

            Flash::success(Lang::get('hambern.properties::lang.options.delete_selected_success'));
        }
        else {
            Flash::error(Lang::get('hambern.properties::lang.options.delete_selected_empty'));
        }
        
        return $this->listRefresh();
    }
}
