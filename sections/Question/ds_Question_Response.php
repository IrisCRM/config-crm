<?php

/**
 * Справочник Стандартные ответы
 */

namespace Iris\Config\CRM\sections\Question;

use Config;
use PDO;

class ds_Question_Response extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, [
            'common/Lib/lib.php']);
    }

    public function onBeforePostQuestionID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'QuestionID');
        $result = $this->GetQuestionInfo(['_p_questionid' => $id]);
        return $result;
    }

    /**
     * Получить параметры вопроса по id вопроса в опросе
     */
    function GetQuestionInfo($params)
    {
        $p_questionid = $params['_p_questionid'];
        $p_result = null;

        $con = $this->connection;

        //Получить ИД и код типа вопроса
        $select_sql = <<<EOD
select rt1.code as responsetypecode
from iris_Question q1 
left join iris_ResponseValueType rt1 on rt1.id = q1.valuetypeid
where q1.id = :p_questionid
EOD;
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            'p_questionid' => $p_questionid,
        ));
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $row = isset($rows[0]) ? $rows[0] : null;

        $result = $p_result;
        $result['Params']['ResponseTypeCode'] = $row['responsetypecode'];

        return $result;
    }

}
