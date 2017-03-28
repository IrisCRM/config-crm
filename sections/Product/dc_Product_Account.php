<?php

namespace Iris\Config\CRM\sections\Product;

use Config;
use Iris\Iris;

/**
 * Карточка комплектации продукта
 */
class dc_Product_Account extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php'));
    }

    public function onChangeProductID($params)
    {
        $result = GetValuesFromTable('Product', $params['value'], 
                array('Price', 'UnitID', 'Cost'), $this->connection);

        $result = FieldValueFormat('ActualityDate', 
        		GetCurrentDBDate($this->connection), null, $result);
        return $result;
    }
}
