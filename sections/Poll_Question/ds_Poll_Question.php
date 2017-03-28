<?php

namespace Iris\Config\CRM\sections\Poll_Question;

use Config;

class ds_Poll_Question extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, ['common/Lib/lib.php']);
    }

    function onPrepare($params)
    {
        //var_dump($params);exit;
        if ($params['mode'] != 'insert') {
            return null;
        };

        $result = null;

        //Номер добавляемой позиции
        $select_sql = "select max(orderpos::integer) from iris_Poll_Question ".
            "where PollID = :p_id";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(':p_id' => $params['detail_column_value']));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();
        $Number++;
        $result = FieldValueFormat('orderpos', $Number, null, $result);
        $result = FieldValueFormat('weight', 100, null, $result);

        return $result;
    }

    function onAfterPost($p_table, $p_id, $p_oldvalues, $p_newvalues)
    {
        $new_id = GetArrayValueByParameter($p_newvalues['FieldValues'], 'Name', 'id', 'Value');
        $pollid_new = GetArrayValueByParameter($p_newvalues['FieldValues'], 'Name', 'pollid', 'Value');
        $pollid_old = GetArrayValueByParameter($p_oldvalues['FieldValues'], 'Name', 'pollid', 'Value');
        $orderpos_new = GetArrayValueByParameter($p_newvalues['FieldValues'], 'Name', 'orderpos', 'Value');
        $orderpos_old = GetArrayValueByParameter($p_oldvalues['FieldValues'], 'Name', 'orderpos', 'Value');

        $con = $this->connection;

        //Если запись вставляется между другими записями, то скорректируем номера
        $select_sql = <<<EOD
update iris_Poll_Question set orderpos = (orderpos::integer + 1)::varchar
where PollID = :pollid 
and orderpos::integer >= :pos
and id != :newid
EOD;
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            ':pollid' => $pollid_new,
            ':pos' => $orderpos_new,
            ':newid' => $new_id,
        ));

        $select_sql = <<<EOD
update iris_Poll_Question set orderpos = (orderpos::integer - 1)::varchar
where PollID = :pollid 
and orderpos::integer > :pos
EOD;
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            ':pollid' => $pollid_old,
            ':pos' => $orderpos_old,
        ));

        //Если запись вставляется в конец и номер назначен очень большой, то уменьшим номер
        $select_sql = "select max(orderpos::integer) from iris_Poll_Question ".
            "where PollID = :pollid and id != :id";
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            ':pollid' => $pollid_new,
            ':id' => $new_id,
        ));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();

        if ($orderpos_new > $Number + 1) {
            $select_sql = <<<EOD
update iris_Poll_Question set orderpos = :num 
where id = :id
EOD;
            $statement = $con->prepare($select_sql);
            $statement->execute(array(
                ':num' => $Number + 1,
                ':id' => $new_id,
            ));
        }

        //Текст вопроса в опрос скопируем из вопроса
        $select_sql = <<<EOD
update iris_Poll_Question 
set name = (select name from iris_question where id = questionid)
where id = :id
EOD;
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            ':id' => $new_id,
        ));
    }

}
