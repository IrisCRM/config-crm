<?php

namespace Iris\Config\CRM\sections\Product;

use Config;
use Iris\Iris;

/**
 * Карточка комплектации продукта
 */
class dc_Product_Constituent extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, [
            'common/Lib/lib.php',
        ]);
    }

    public function onChangeConstituentID($params)
    {
        return GetValuesFromTable('Product', $params['value'], 
                array('Price', 'UnitID', 'Cost'), $this->connection);
    }
}
