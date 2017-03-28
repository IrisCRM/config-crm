<?php
/**
 * Серверная логика вкладки продуктов в договорах
 */

namespace Iris\Config\CRM\sections\Pact;

use Iris\Config\CRM\common\Lib\DocumentConfig;

class ds_Pact_Product extends DocumentConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, 'Pact');
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $parent_id = $params['detail_column_value'];

        // Значения справочников
        $result = GetDictionaryValues(
            array(
                array('Dict' => 'Unit', 'Code' => 'Item')
            ), $this->connection);

        // Номер добавляемой позиции
        $result = $this->getNextProductNumber($parent_id, $result);

        // Количество = 1
        $result = FieldValueFormat('Count', 1, null, $result);

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data) {
        $parent_id = $this->getActualValue($old_data, $new_data, 
                strtolower($this->_parent) . 'id');
        if (!$parent_id) {
            return;
        }

        // Расчёт суммы документа
        $result = $this->updateParentAmount($parent_id);

        // Перенумеруем продукты при необходимости
        $this->renumberProducts($old_data, $new_data, $id);

        return $result;
    }

    public function onBeforePostProductID($parameters) {
        $con = $this->connection;
        $product_id = $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], 'productid');
        $parameters['new_data'] = GetLinkedValuesDetailed(
                '{Product}', $product_id, array('{{Unit}}'), $con, 
                $parameters['new_data']);
        return $this->getAmounts($parameters);
    }

    public function onBeforePostCount($parameters) {
        return $this->getAmounts($parameters, false);
    }

    public function onBeforePostUnitID($parameters) {
        return $this->getAmounts($parameters);
    }

    public function onBeforePostPrice($parameters) {
        list ($count, $price, $discount) = 
                $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], array('count', 'price', 'discount'));

        $parameters['new_data'] = FieldValueFormat('Amount', 
                ((100 - $discount) * $count * $price) / 100, null, 
                $parameters['new_data']);

        return $parameters['new_data'];
    }

    public function onBeforePostDiscount($parameters) {
        list ($count, $price, $discount) = 
                $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], array('count', 'price', 'discount'));

        $parameters['new_data'] = FieldValueFormat('Amount', 
                ((100 - $discount) * $count * $price) / 100, null, 
                $parameters['new_data']);

        return $parameters['new_data'];
    }

    public function getAmounts($parameters, $useProductCardPrice = true) {
        $old_data = $parameters['old_data'];
        $new_data = $parameters['new_data'];
        $con = $this->connection;
        list ($count, $product_id, $unit_id) = 
                $this->getActualValue($old_data, $new_data, array(
                    'count', 'productid', 'unitid'));

        $new_data = $this->getProductPrice($product_id, $count, $unit_id, 
                null, null, $new_data, array('Price'), $useProductCardPrice);

        list ($discount, $price, $cost) = 
                $this->getActualValue($old_data, $new_data, array(
                    'discount', 'price', 'cost'));
        $discount = 0;

        $new_data = FieldValueFormat('Amount', 
                ((100 - $discount) * $count * $price) / 100, null, $new_data);

        return $new_data;
    }

}
