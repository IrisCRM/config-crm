<?php
/**
 * Серверная логика вкдалки продуктов в счетах
 */

namespace Iris\Config\CRM\sections\Marketing;

use Iris\Config\CRM\common\Lib\DocumentConfig;

class ds_Marketing_Product extends DocumentConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, 'Marketing');
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        // Значения справочников
        $result = GetDictionaryValues(
            array(
                array('Dict' => 'Unit', 'Code' => 'Item')
            ), $this->connection);

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
        $this->updateParentAmount($parent_id, null, 
                array('PlanIncome' => 'Amount'));
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
        return $this->getAmounts($parameters);
    }

    public function onBeforePostUnitID($parameters) {
        return $this->getAmounts($parameters);
    }

    public function onBeforePostPrice($parameters) {
        list ($count, $price) = 
                $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], array('count', 'price'));

        $parameters['new_data'] = FieldValueFormat('Amount', 
                $count * $price, null, $parameters['new_data']);

        return $parameters['new_data'];
    }

    public function getAmounts($parameters) {
        $old_data = $parameters['old_data'];
        $new_data = $parameters['new_data'];
        $con = $this->connection;
        list ($count, $product_id, $unit_id) = 
                $this->getActualValue($old_data, $new_data, array(
                    'count', 'productid', 'unitid'));

        $new_data = $this->getProductPrice($product_id, $count, $unit_id, 
                null, null, $new_data);

        list ($price, $cost) = $this->getActualValue(
                $old_data, $new_data, array('price', 'cost'));

        $new_data = FieldValueFormat('Amount', $count * $price, null, 
                $new_data);

        return $new_data;
    }

}
