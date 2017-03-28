<?php

namespace Iris\Config\CRM\sections\Interview;

use Config;
use PDO;

/**
 * Серверная логика карточки интервью
 */
class ds_Interview_Response extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, ['common/Lib/lib.php']);
    }


    function onPrepare($params)
    {
        if ($params['mode'] != 'insert') {
            return null;
        };

        $con = $this->connection;

        $result = null;

        //Номер добавляемой позиции
        $select_sql = <<<EOD
/*
select id as pollquestionid, questionid as questionid, name as name
from iris_poll_question 
where 
(pollid = (
  select pollid 
  from iris_interview i3
  where i3.id = :id
) or pollid is null)
and orderpos::integer = (
  select max(q2.orderpos) 
  from iris_interview_response ir2 
  left join iris_question q2 on q2.id = ir2.questionid
  where interviewid = :id
)::integer + 1
*/

select id as pollquestionid, questionid as questionid, name as name
from iris_poll_question 
where 
(pollid = (
  select pollid 
  from iris_interview i3
  where i3.id = :id
) or pollid is null)
and orderpos::integer = (
  case (select count(*) from iris_interview_response where interviewid = :id)
  when 0 then 0
  else (  
    select max(pq2.orderpos::integer) 
    from iris_interview_response ir2 
    left join iris_poll_question pq2 on pq2.id = ir2.pollquestionid
    where interviewid = :id
  )::integer
  end
) + 1

EOD;
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            'id' => $params['detail_column_value'],
        ));
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $row = $rows[0];

            $result = FieldValueFormat('QuestionID', $row['questionid'], $row['name'], $result);
            $result = FieldValueFormat('PollQuestionID', $row['pollquestionid'], $row['name'], $result);
        }

        return $result;
    }

    public function onBeforePostResponseID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'ResponseID');
        $mark = GetFieldValueByID('Response', $id, 'Mark', $this->connection);
        $result = FieldValueFormat('mark', $mark);

        return $result;
    }

    public function onBeforePostPollQuestionID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'PollQuestionID');
        $result = GetLinkedValues('Poll_Question', $id, ['Question'], $this->connection);

        return $result;
    }

    function onAfterPost($p_table, $p_id, $p_oldvalues, $p_newvalues)
    {
        $new_id = GetArrayValueByParameter($p_newvalues['FieldValues'], 'Name', 'id', 'Value');
        $interview_id = GetArrayValueByParameter($p_newvalues['FieldValues'], 'Name', 'interviewid', 'Value');

        $con = GetConnection(null);

        //Обновим id вопроса и orderpos
        //TODO: а также номер для сортировки и значение строкой для печатных форм
        $sql = <<<EOD
update iris_interview_response
set questionid = (
  select q1.id 
  from iris_poll_question pq1 
  left join iris_question q1 on q1.id = pq1.questionid
  where pq1.id = pollquestionid
),
orderforprint = (
  select to_char(coalesce(pq1.orderpos::integer, 0), 'FM0000MI')||':'
  ||to_char(coalesce((select r1.orderpos::integer from iris_response r1 where r1.id = iris_interview_response.responseid), 0), 'FM0000MI')||':'
  ||pq1.id::varchar
  from iris_poll_question pq1 
  where pq1.id = pollquestionid
),
valueforprint = (
  case (
    select rvt1.code
    from iris_poll_question pq1 
    left join iris_question q1 on q1.id = pq1.questionid
    left join iris_responsevaluetype rvt1 on rvt1.id = q1.valuetypeid
    where pq1.id = pollquestionid
  )
  when 'Int' then iris_interview_response.intvalue::varchar
  when 'Float' then iris_interview_response.floatvalue::varchar
  when 'Date' then iris_interview_response.datevalue::varchar
  when 'Datetime' then iris_interview_response.datetimevalue::varchar
  when 'Single' then (select r1.stringvalue from iris_response r1 where r1.id = iris_interview_response.responseid)::varchar
  else iris_interview_response.stringvalue end
),
orderpos = (
  select pq1.orderpos 
  from iris_poll_question pq1 
  where pq1.id = pollquestionid
)
where id = :id
EOD;
        $statement = $con->prepare($sql);
        $statement->execute(array(
            ':id' => $new_id,
        ));

        //Вычислим оценку интервью
        $sql = <<<EOD
select (
  select sum(result) from (
    select sum(ir1.mark)*max(pq1.weight) as result
    from iris_interview_response ir1
    left join iris_poll_question pq1 on pq1.id = ir1.PollQuestionID
    where ir1.interviewid = :interviewid
    group by ir1.PollQuestionID
  ) t
) / (
  select sum(pq1.weight) as weight
  from iris_interview i1
  left join iris_poll ip1 on ip1.id = i1.pollid
  left join iris_poll_question pq1 on pq1.pollid = ip1.id
  where i1.id = :interviewid
) as result
EOD;
        $statement = $con->prepare($sql);
        $statement->execute(array(
            ':interviewid' => $interview_id,
        ));
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $interview_result = $rows[0]['result'];
        $fields = FieldValueFormat('result', $interview_result, null);

        //Был ли проставлен оператор в интервью? Если нет, то заполним текущим пользователем
        $operatorid = GetFieldValueByID('Interview', $interview_id, 'operatorid', $con);
        if (!$operatorid) {
            list ($userid, $username) = GetShortUserInfo(GetUserName(), $con);
            $fields = FieldValueFormat('operatorid', $userid, null);
        }

        //Дата последней попытки
        $Date = GetCurrentDBDate($con);
        $fields = FieldValueFormat('LastDate', $Date, null, $fields);

        //Если вопросов больше нет, то обновим состояние и результат интервью: finished, finished
        $poll_id = GetFieldValueByID('Interview', $interview_id, 'PollID', $con);
        $sql = 'select count(*) as count from iris_poll_question '.
            'where id not in (select pollquestionid from iris_interview_response where interviewid = :interviewid) '.
            'and pollid = :pollid';
        $statement = $con->prepare($sql);
        $statement->execute(array(
            ':interviewid' => $interview_id,
            ':pollid' => $poll_id,
        ));
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($rows[0]['count'] == 0) {
            $fields = GetDictionaryValues(
                array (
                    array ('Dict' => 'InterviewState', 'Code' => 'finished'),
                    array ('Dict' => 'InterviewResult', 'Code' => 'finished'),
                ),
                $con, $fields
            );
        }

        UpdateRecord('Interview', $fields['FieldValues'], $interview_id, $con);
    }

}
