<?php

namespace Iris\Config\CRM\sections\Product;

use Config;
use Iris\Iris;

/**
 * Карточка цены продукта
 */
class dc_Product_Price extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php'));
    }

    public function onChangeProductID($params, $con = null)
    {
        return GetValuesFromTable('Product', $params['value'], 
                array('Price', 'UnitID', 'Cost'), $this->connection);
    }
}
