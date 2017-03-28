<?php

namespace Iris\Config\CRM\common\Lib;

use Config;
use Iris\Iris;

/**
 * Общие методы для отчётов
 */
class ReportConfig extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    /**
     * Фильрует колонку по таблице
     */
    public function filterTableColumn($params)
    {
        list($table_id_report) = GetFieldValuesByID('Report_Table', 
                $params['report_table_id'], array('TableID'), $this->connection);
        list($table_id_column) = GetFieldValuesByID('Table_Column', 
                $params['table_column_id'], array('TableID'), $this->connection);

        $result['table_id'] = $table_id_report;
        $result['clear'] = $table_id_report != $table_id_column;

        return $result;
    }

    /**
     * Возвращает следующий номер для добавления позиции
     */
    public function getPosition($parent_id, $target) 
    {
        // Номер
        $select_sql = "select max(Number) from iris_Report_" . $target . " "
                . "where ReportID = :parent_id";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(':parent_id' => $parent_id));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();
        return $Number + 1;
    }

    /**
     * Перенумерация позиций при необходимости
     */
    public function renumber($old_data, $new_data, $id, $target)
    {
        list($parent_id, $number) = $this->getActualValue($old_data, $new_data, 
                array('reportid', 'number'));
        if (!$parent_id) {
            return;
        }

        // При удалении продукта - перенумеруем продукты
        if (!$new_data) {
            $this->_doRenumber($parent_id, $id, $number, null, '-', $target);
        }
        // При добавлении продукта - перенумеруем продукты
        elseif (!$old_data) {
            $this->_doRenumber($parent_id, $id, $number, null, '+', $target);
        }
        // При изменении продукта - перенумеруем продукты, если номер изменился
        else {
            $number_old = $this->fieldValue($old_data, 'number');
            $number_new = $this->getActualValue($old_data, $new_data, 'number');
            if ($number_old > $number_new) {
                $this->_doRenumber($parent_id, $id, 
                        $number_new, $number_old, '+', $target);
            }
            elseif ($number_old < $number_new) {
                $this->_doRenumber($parent_id, $id, 
                        $number_old, $number_new, '-', $target);
            }
        }
    }

    /**
     * Перенумерация позиций
     */
    protected function _doRenumber($parent_id, $id, $number, $number2, 
            $operation, $target)
    {
        if (!$parent_id || !$number || !$operation) {
            return;
        }

        $con = $this->connection;
        $update_sql = "update iris_Report_" . $target . " "
                . "set Number = Number $operation 1 "
                . "where ReportID = :parent_id "
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

}
