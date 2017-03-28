<?php

namespace Iris\Config\CRM\common\Lib;

use Config;
use Iris\Iris;
use PDO;

/**
 * Методы для работы с документами
 */
class DocumentConfig extends Config
{
    protected $_parent;

    public function __construct($Loader, $parent = null, $include = array())
    {
        parent::__construct($Loader, array_merge(
                array('common/Lib/lib.php'), $include));
        $this->_parent = $parent;
    }

    /**
     * Обновление суммы в родительской записи
     */
    public function updateParentAmount($parent_id, $parent = null, 
            $fields = array('Amount' => 'Amount')) {
        if (!$parent_id) {
            return;
        }

        $result = null;

        if ($parent == null) {
            $parent = $this->_parent;
        }

        $field_list = '';
        foreach ($fields as $key => $value) {
            $field_list .= $field_list ? ', ' : '';
            $field_list .= 'sum(' . $value . ') as ' . strtolower($key);
        }

        // Посчитаем общую сумму
        $select_sql = 'select ' . $field_list
                . ' from iris_' . $parent . '_Product ' 
                . 'where ' . $parent . 'ID = :parent_id';
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':parent_id' => $parent_id
        ));
        $row = $statement->fetch();

        foreach ($fields as $key => $value) {
            $result = FieldValueFormat($key, 
                    $row[strtolower($key)] ? $row[strtolower($key)] : 0, 
                    null, $result);
        }

        // Обновим сумму в родительской записи
        UpdateRecord($parent, $result['FieldValues'], $parent_id, 
                $this->connection);

        return $result;
    }


    /**
     * Перенумерация продуктов при необходимости
     */
    public function renumberProducts($old_data, $new_data, $id, $parent = null)
    {
        if ($parent == null) {
            $parent = $this->_parent;
        }
        list($parent_id, $number) = $this->getActualValue($old_data, $new_data, 
                array(strtolower($parent).'id', 'number'));

        // При удалении продукта - перенумеруем продукты
        if (!$new_data) {
            $this->_doRenumberProducts($parent_id, $id, $number, null, '-', $parent);
        }
        // При добавлении продукта - перенумеруем продукты
        elseif (!$old_data) {
            $this->_doRenumberProducts($parent_id, $id, $number, null, '+', $parent);
        }
        // При изменении продукта - перенумеруем продукты, если номер изменился
        else {
            $number_old = $this->fieldValue($old_data, 'number');
            $number_new = $this->getActualValue($old_data, $new_data, 'number');
            if ($number_old > $number_new) {
                $this->_doRenumberProducts($parent_id, $id, 
                        $number_new, $number_old, '+', $parent);
            }
            elseif ($number_old < $number_new) {
                $this->_doRenumberProducts($parent_id, $id, 
                        $number_old, $number_new, '-', $parent);
            }
        }
    }

    /**
     * Перенумерация продуктов
     */
    protected function _doRenumberProducts($parent_id, $id, $number, $number2, 
            $operation, $parent)
    {
        if (!$parent_id || !$number || !$operation) {
            return;
        }

        $con = $this->connection;
        $update_sql = "update iris_" . $parent . "_Product "
                . "set Number = Number $operation 1 "
                . "where " . $parent . "ID = :parent_id "
                . "and number >= :number "
                . "and (number <= :number2 or :number2 is null) "
                . "and id != :id";
        $statement = $con->prepare($update_sql);
        $statement->execute(array(
            ':parent_id' => $parent_id,
            ':id' => $id,
            ':number' => $number, 
            ':number2' => $number2, 
        ));
    }

    /**
     * Получение цены продукта с учётом вкладки "Цены"
     */
    public function getProductPrice($product_id, $count, $unit_id, 
            $duration, $time_unit, $result, $fields = array('Price'), $useCardPrice = true) {

        if (!$duration) {
            $duration = 0;
        }
        // найдем подходящую цену продукта по следующему алгоритму:
        // «Единица» = «Единица» (даже если обе null)
        // «Минимальное количество» >= «Количество» >= «Максимальное количество»
        // «Единица времени» = «Единица времени» (даже если обе null)
        // «Мин. продолжительность» >= «Продолжительность» >= «Макс. продолжительность»
        //$sql  = "select id, price, cost, unitid, discount from iris_product_price ";
        $sql  = "select id from iris_product_price "
                . "where productid = :product_id "
                . "and :count >= mincount and :count <= maxcount "
                . "and (unitid = :unit_id or (unitid is null and :unit_id  = '')) "
                . "and ((:duration >= minduration and :duration <= maxduration) "
                . "  or (:duration = '0' and minduration is null and maxduration is null)) "
                . "and (timeunit = :timeunit "
                . "  or (timeunit is null and (:timeunit = '' or :timeunit is null))) ";
        $cmd = $this->connection->prepare($sql, 
                array(PDO::ATTR_EMULATE_PREPARES => true));
        $cmd->execute(array(
            ":product_id" => $product_id, 
            ":count" => $count, 
            ":unit_id" => $unit_id, 
            ":duration" => $duration, 
            ":timeunit" => $time_unit
        ));
        $price = $cmd->fetchAll(PDO::FETCH_ASSOC);

        // Если нашли подходящую цену, то вернем ее, вместо стандартной
        if (!empty($price[0]['id'])) {
            $result = GetValuesFromTable('Product_Price', $price[0]['id'], 
                    $fields, $this->connection, $result);
        }
        elseif ($useCardPrice) {
            $result = GetValuesFromTable('Product', $product_id, 
                    $fields, $this->connection, $result);
        }
        return $result;
    }

    /**
     * Номер следующей по счёту позиции
     */
    public function getNextProductNumber($parent_id, $result) 
    {
        $select_sql = "select max(Number) as number " 
                . "from iris_" . $this->_parent . "_Product "
                . "where " . $this->_parent . "ID = :id";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':id' => $parent_id
        ));
        $row = $statement->fetch();
        return FieldValueFormat('Number', $row['number'] + 1, null, $result);
    }

}
