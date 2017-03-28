<?php

namespace Iris\Config\CRM\sections\Product;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки цены продукта
 */
class ds_Product_Price extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $result = GetValuesFromTable('Product', $params['detail_column_value'], 
                array('Price', 'UnitID', 'Cost'), $this->connection);
        
        $result = FieldValueFormat('Discount', '0', null, $result);

        return $result;
    }
}
