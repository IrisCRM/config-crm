<?php
/**
 * Серверная логика вкдалки продуктов в заказе
 */

namespace Iris\Config\CRM\sections\Project;

use Iris\Config\CRM\common\Lib\DocumentConfig;
use Local;

class ds_Project_Product extends DocumentConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, 'Project');
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $con = $this->connection;
        $p_id = $params['detail_column_value'];
        $result = null;

        // Значения справочников
        $result = GetDictionaryValues(
            array (
                array ('Dict' => 'Unit', 'Code' => 'Item')
            ), $con);
                
        // Номер добавляемой позиции
        $result = $this->getNextProductNumber($p_id, $result);
        
        // Если уже есть позиции, то возьмем из них минимальную скидку
        $select_sql = "select count(*) from iris_Project_Product pp " 
                . "where pp.ProjectID = :p_id and Discount is null";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_id', $p_id);
        $statement->execute();
        $statement->bindColumn(1, $Count);
        $res = $statement->fetch();
        if ($Count == 0) {        
            $select_sql = "select min(pp.Discount) " 
                    . "from iris_Project_Product pp where pp.ProjectID = :p_id";
            $statement = $con->prepare($select_sql);
            $statement->bindParam(':p_id', $p_id);
            $statement->execute();
            $statement->bindColumn(1, $Discount);
            $res = $statement->fetch();        
        }
        if ($Discount != '') {
            $result = FieldValueFormat('Discount', $Discount, null, $result);
        }

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

        // Расчёт суммы заказа
        $this->updateParentAmount($parent_id, null, array(
            'PlanIncome' => 'Amount',
            'PlanExpense' => 'CostAmount',
            'PlanProfit' => $this->_DB->nullToZero('Amount') . ' - ' 
                    . $this->_DB->nullToZero('CostAmount'),
        ));

        // Перенумеруем продукты при необходимости
        $this->renumberProducts($old_data, $new_data, $id);
    }

    public function onBeforePostStartDate($parameters) {
        return $this->getFinishDate($parameters);
    }

    public function onBeforePostProductID($parameters) {
        $con = $this->connection;
        $product_id = $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], 'productid');
        $parameters['new_data'] = GetLinkedValuesDetailed('{Product}', $product_id, 
                array('{{Unit}}', 'TimeUnit', 'Duration'), $con, 
                $parameters['new_data']);
        $parameters['new_data'] = $this->getFinishDate($parameters);
        return $this->getAmounts($parameters);
    }

    public function onBeforePostCount($parameters) {
        return $this->getAmounts($parameters, false);
    }

    public function onBeforePostTimeUnit($parameters) {
        $parameters['new_data'] = $this->getFinishDate($parameters);
        return $this->getAmounts($parameters);
    }

    public function onBeforePostDuration($parameters) {
        $parameters['new_data'] = $this->getFinishDate($parameters);
        return $this->getAmounts($parameters);
    }

    public function onBeforePostUnitID($parameters) {
        return $this->getAmounts($parameters);
    }

    public function onBeforePostPrice($parameters) {
        list($count, $price, $discount) = 
                $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], array('count', 'price', 'discount'));

        $parameters['new_data'] = FieldValueFormat('PriceAmount', 
                $count * $price, null, $parameters['new_data']);
        $parameters['new_data'] = FieldValueFormat('Amount', 
                ((100 - $discount) * $count * $price) / 100, null, 
                $parameters['new_data']);

        return $parameters['new_data'];
    }

    public function onBeforePostCost($parameters) {
        list($count, $cost) = $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], array('count', 'cost'));

        $parameters['new_data'] = FieldValueFormat('CostAmount', 
                $count * $cost, null, $parameters['new_data']);

        return $parameters['new_data'];
    }

    public function onBeforePostDiscount($parameters) {
        list($count, $price, $discount) = 
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
        list($count, $product_id, $unit_id, $duration, $time_unit) = 
                $this->getActualValue($old_data, $new_data, array(
                    'count', 'productid', 'unitid', 'duration', 'timeunit'));

        $new_data = $this->getProductPrice($product_id, $count, $unit_id, 
                $duration, $time_unit, $new_data, 
                array('Price', 'Cost', 'Discount'), $useProductCardPrice);

        list($discount, $price, $cost) = 
                $this->getActualValue($old_data, $new_data, array(
                    'discount', 'price', 'cost'));

        $new_data = FieldValueFormat('PriceAmount', $count * $price, null, 
                $new_data);
        $new_data = FieldValueFormat('CostAmount', $count * $cost, null, 
                $new_data);
        $new_data = FieldValueFormat('Amount', 
                ((100 - $discount) * $count * $price) / 100, null, $new_data);

        return $new_data;
    }

    public function getFinishDate($parameters) {
        $old_data = $parameters['old_data'];
        $new_data = $parameters['new_data'];
        list($start_date, $duration, $time_unit) = 
                $this->getActualValue($old_data, $new_data, 
                array('startdate', 'duration', 'timeunit'));

        if (!$start_date || !$duration || !$time_unit) {
            $new_data = FieldValueFormat('FinishDate', null, null, $new_data);
            $new_data = FieldValueFormat('Description', null, null, $new_data);
            return $new_data;
        }

        $Local = Local::getInstance();
        $format = $Local->getDateFormat();
        $start_time = $Local->localDateToTime($start_date);
        $finish_date = null;

        if ($time_unit == 'd') {
            $metric = $duration == 1 ? 'day' : 'days';
        }
        elseif ($time_unit == 'm') {
            $metric = $duration == 1 ? 'month -1 day' : 'months -1 day';
        }
        elseif ($time_unit == 'y') {
            $metric = $duration == 1 ? 'year -1 day' : 'years -1 day';
        }
        if ($duration >= 0) {
            $duration = '+' . $duration;
        }

        if (!empty($metric)) {
            $finish_date = date($format, strtotime("$duration $metric", $start_time));
            $new_data = FieldValueFormat('FinishDate', $finish_date, null, $new_data);
            $new_data = FieldValueFormat('Description', 
                    "Период с $start_date по $finish_date", null, $new_data);
        }

        return $new_data;
    }

}
