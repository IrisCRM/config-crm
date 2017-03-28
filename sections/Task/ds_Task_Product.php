<?php

namespace Iris\Config\CRM\sections\Task;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки продукта в деле
 */
class ds_Task_Product extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    function onBeforePostProductID($params, $con = null)
    {
        $id = $this->fieldValue($params['old_data'], 'ProductID');
        return GetValuesFromTable('Product', $id, array('UnitID', 'Price'), $con);
    }
}
?>
