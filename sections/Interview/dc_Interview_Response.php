<?php

namespace Iris\Config\CRM\sections\Interview;

use Config;
use PDO;

/**
 * Карточка документа
 */
class dc_Interview_Response extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, [
            'common/Lib/lib.php',
        ]);
    }

    public function GetInterviewParams($params)
    {
        $p_id = $params['_p_id'];
        $con = $this->connection;

        //Номер добавляемой позиции
        $select_sql = "select pollid from iris_Interview where id = :p_id";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_id', $p_id);
        $statement->execute();
        $statement->bindColumn(1, $PollID);
        $res = $statement->fetch();

        $result = null;
        $result['Params']['PollID'] = $PollID;

        return $result;
    }

    /**
     * Получить параметры вопроса по id вопроса в опросе
     */
    public function GetQuestionInfo($params)
    {
        $p_pollquestionid = $params['_p_pollquestionid'];
        $p_interviewid = $params['_p_interviewid'];
        $con = $this->connection;
        $result_values = null;

        //Получить ИД и код типа вопроса
        $select_sql = <<<EOD
select rt1.code as responsetypecode, q1.id as questionid
from iris_Poll_Question pq1 
left join iris_Question q1 on q1.id=pq1.questionid
left join iris_ResponseValueType rt1 on rt1.id=q1.valuetypeid
where pq1.id = :p_pollquestionid
EOD;
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            'p_pollquestionid' => $p_pollquestionid,
        ));
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $row = $rows[0];

        $result = null;
        $result['Params']['ResponseTypeCode'] = $row['responsetypecode'];
        $questionid = $row['questionid'];

        //Если множественный выбор, то вернём также и поля-чекбоксы
        if ('Multi' == $row['responsetypecode']) {
            $select_sql = <<<EOD
select 
r1.stringvalue as name, 
r1.id as responseid, 
( select max(ir2.id) 
  from iris_interview_response ir2 
  where ir2.responseid=r1.id 
  and ir2.interviewid = :p_interviewid
  and ir2.pollquestionid = :p_pollquestionid
) as interviewresponseid,
( select max(ir3.intvalue) from iris_interview_response ir3 
  where ir3.responseid=r1.id 
  and ir3.interviewid = :p_interviewid
  and ir3.pollquestionid = :p_pollquestionid
) as responsevalue
from iris_response r1 
where r1.questionid = :p_questionid
order by r1.orderpos
EOD;
            $statement = $con->prepare($select_sql);
            $statement->execute(array(
                'p_questionid' => $questionid,
                'p_interviewid' => $p_interviewid,
                'p_pollquestionid' => $p_pollquestionid,
            ));
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $result = FieldValueFormat(json_encode_str($row['name']),
                    $row['responseid'], $row['interviewresponseid'], $result);
                $result['FieldValues'][count($result['FieldValues'])-1]['ResponseValue'] = $row['responsevalue'];
            }
        }

        //Вернём диапазоны для расчёта оценки
        $responsetypecode = isset($row['responsetypecode']) ? $row['responsetypecode'] : null;
        $valuefield = 'Single' != $responsetypecode && 'Multi' != $responsetypecode
            ? 'r1.'.iris_strtolower($responsetypecode).'value'
            : 'r1.id';
        $resultfield = $valuefield;
        if ('Date' == $responsetypecode) {
            $resultfield = _db_date_to_string($valuefield);
        }
        else
            if ('Datetime' == $responsetypecode) {
                $resultfield = _db_datetime_to_string($valuefield);
            }
        $select_sql = "select $resultfield as value, $valuefield as sortvalue, ".<<<EOD
r1.mark as responsevalue
from iris_response r1 
where r1.questionid = :p_questionid
order by sortvalue
EOD;
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            'p_questionid' => $questionid,
        ));
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $result_values = FieldValueFormat(json_encode_str($row['value']), json_encode_str($row['value']), null, $result_values);
            $result_values['FieldValues'][count($result_values['FieldValues'])-1]['ResponseValue'] = $row['responsevalue'];
        }
        $result['ResponseValues'] = $result_values['FieldValues'];

        return $result;
    }

    /**
     * Обновить
     */
    public function UpdateMultiResponse($params)
    {
        $p_id = $params['_p_id'];
        $p_pollquestionid = $params['_p_pollquestionid'];
        $p_interviewid = $params['_p_interviewid'];
        $p_values = $params['_p_values'];

        $result['success'] = '1';
        $con = GetConnection();

        $values = json_decode($p_values, true);

        list ($userid, $username) = GetShortUserInfo(GetUserName(), $con);

        foreach($values as $val) {
            //Если ответ уже был в базе и сейчас он обновляется
            if (!IsEmptyValue($val['interviewresponseid']) && $val['interviewresponseid'] != 'undefined') {
                $sql_upd = 'update iris_interview_response '.
                    'set interviewid = :interviewid, '.
                    'responseid = :responseid, '.
                    'intvalue = :responsevalue, '.
                    'pollquestionid = :pollquestionid, '.
                    'questionid = (select questionid from iris_poll_question where id = :pollquestionid), '.
                    'modifydate = now(), '.
                    'modifyid = :userid, '.
                    'mark = (select mark from iris_response where id = :responseid) * :responsevalue, '.
                    'orderpos = (select pq1.orderpos from iris_poll_question pq1 where pq1.id = :pollquestionid), '.
                    'valueforprint = (select stringvalue from iris_response where id = :responseid), '.
                    'orderforprint = ('.
                    '  select to_char(coalesce(pq1.orderpos::integer, 0), \'FM0000MI\')||\':\''.
                    '  ||to_char(coalesce((select r1.orderpos::integer from iris_response r1 where r1.id = :responseid), 0), \'FM0000MI\')||\':\''.
                    '  ||pq1.id::varchar' .
                    '  from iris_poll_question pq1 '.
                    '  where pq1.id = :pollquestionid '.
                    ') '.
                    'where id = :id';
                $statement = $con->prepare($sql_upd, array(PDO::ATTR_EMULATE_PREPARES => true));
                $statement->execute(array(
                    'id' => $val['interviewresponseid'],
                    'interviewid' => $p_interviewid,
                    'responseid' => $val['responseid'],
                    'responsevalue' => $val['responsevalue'],
                    'pollquestionid' => $p_pollquestionid,
                    'userid' => $userid,
                ));

                //$result['sql'] = $sql_upd;
                $result['params'] = array(
                    'id' => $val['interviewresponseid'],
                    'interviewid' => $p_interviewid,
                    'responseid' => $val['responseid'],
                    'responsevalue' => $val['responsevalue'],
                    'pollquestionid' => $p_pollquestionid,
                    'userid' => $userid,
                );
            }
            //Если ответа ещё не было в базе
            else {
                $sql_ins = 'insert into iris_interview_response '.
                    '(id, interviewid, responseid, intvalue, pollquestionid, questionid, '.
                    'dateofanswer, createdate, modifydate, createid, modifyid, mark, orderpos, '.
                    'valueforprint, orderforprint) '.
                    'values (iris_genguid(), :interviewid, :responseid, :responsevalue, :pollquestionid, '.
                    '(select questionid from iris_poll_question where id = :pollquestionid), '.
                    'now(), now(), now(), :userid, :userid, '.
                    '(select mark from iris_response where id = :responseid) * :responsevalue, '.
                    '(select pq1.orderpos from iris_poll_question pq1 where pq1.id = :pollquestionid), '.
                    '(select stringvalue from iris_response where id = :responseid),'.
                    '('.
                    '  select to_char(coalesce(pq1.orderpos::integer, 0), \'FM0000MI\')||\':\''.
                    '  ||to_char(coalesce((select r1.orderpos::integer from iris_response r1 where r1.id = :responseid), 0), \'FM0000MI\')||\':\''.
                    '  ||pq1.id::varchar' .
                    '  from iris_poll_question pq1 '.
                    '  where pq1.id = :pollquestionid '.
                    ') )';
                $statement = $con->prepare($sql_ins, array(PDO::ATTR_EMULATE_PREPARES => true));
                $statement->execute(array(
                    'interviewid' => $p_interviewid,
                    'responseid' => $val['responseid'],
                    'responsevalue' => $val['responsevalue'],
                    'pollquestionid' => $p_pollquestionid,
                    'userid' => $userid,
                ));

                $sql_del = 'delete from iris_interview_response '.
                    'where id = :id';
                $statement = $con->prepare($sql_del);
                $statement->execute(array(
                    'id' => $p_id,
                ));
            }
        }
        return $result;
    }

}
