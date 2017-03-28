<?php
/**
 * Серверная логика вкдалки полей отчёта
 */

namespace Iris\Config\CRM\sections\Report_Column;

use Iris\Config\CRM\common\Lib\ReportConfig;

class ds_Report_Column extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader);
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }
        $result = null;

        //Номер
        $result = FieldValueFormat('Number', 
                $this->getPosition($params['detail_column_value'], 'Column'),
                null, $result);

        //Показывать
        $result = FieldValueFormat('ShowInReport', 1, null, $result);

        return $result;
    }

    public function onBeforePost($parameters) {
        $con = $this->connection;
                
        $code = GetArrayValueByName($parameters['new_data']['FieldValues'], 'Code');
        $reportid = GetArrayValueByName($parameters['new_data']['FieldValues'], 'ReportID');
        $id = GetArrayValueByName($parameters['old_data']['FieldValues'], 'id');

        // Ищем колонки в этом отчете с таким-же алиасом
        if (!$id) {
            $select_sql = "select ID from iris_Report_Column "
                    . "where Code = :p_code and ReportID = :p_reportid";
            $statement = $con->prepare($select_sql);
            $statement->bindParam(':p_code', $code);
            $statement->bindParam(':p_reportid', $reportid);
        }
        else {
            $select_sql = "select ID from iris_Report_Column "
                    . "where Code = :p_code and ReportID = :p_reportid "
                    . "and id <> :p_id";
            $statement = $con->prepare($select_sql);
            $statement->bindParam(':p_code', $code);
            $statement->bindParam(':p_reportid', $reportid);
            $statement->bindParam(':p_id', $id);
        }
        $statement->execute();
        $res = $statement->fetch();
        if ($res) {
            return array('Error' => 'Колонка с таким кодом уже определена в этом отчёте');
        }
        return $parameters['new_data'];
    }

    public function onAfterPost($table, $id, $old_data, $new_data) {
        // Перенумеруем позиции при необходимости
        $this->renumber($old_data, $new_data, $id, 'Column');
    }

}
