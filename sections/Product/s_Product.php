<?php

namespace Iris\Config\CRM\sections\Product;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки продукта
 */
class s_Product extends Config
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

        $result = GetDefaultOwner(GetUserName(), $this->connection);
        $result = FieldValueFormat('have', 0, null, $result);
        $result = FieldValueFormat('wait', 0, null, $result);
        $result = FieldValueFormat('reserve', 0, null, $result);

        return $result;
    }

    function onAfterPost($table, $id, $old_data, $new_data) {
        $Result = null;
        $price = $this->getActualValue($old_data, $new_data, 'Price');
        $unit_id = $this->getActualValue($old_data, $new_data, 'UnitID');
        $price_old = $this->fieldValue($old_data, 'Price');
        $unit_id_old = $this->fieldValue($old_data, 'UnitID');

        if ($price != $price_old || $unit_id != $unit_id_old) {
            $update_sql = "update iris_Product_Price "
                    . "set Price = (:p_price * (100 - Discount)) / 100"
                    . "where UnitID = :p_unit_id and ProductID = :p_product_id";
            $statement = $this->connection->prepare($update_sql);
            $statement->execute(array(
                ':p_price' => $price,
                ':p_unit_id' => $unit_id,
                ':p_product_id' => $id,
            ));

            $Result = 'Calculated';
        }
        $l_result['ProductCalculatePrices']['Result'] = $Result;
        return $l_result;   
    }
}
