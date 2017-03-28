<?php
//********************************************************************
// Раздел "Рассылка". серверная логика карточки
//********************************************************************

namespace Iris\Config\CRM\sections\Mailing;

use Config;
use Iris\Iris;
use PDO;

class c_Mailing extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php'
        ));
    }

    public function sendMailing($params) {
        $mailingId = $params['mailingId'];
        $con = $this->connection;
        $startTime = $this->getSeconds();
        $userPermissions = array();
        $recordPermissions = array();

        // если нет доступа на праву рассылки - то ругаемся
        GetCurrentUserRecordPermissions('iris_mailing', $mailingId, $userPermissions, $con);
        if ($userPermissions['w'] == 0) {
            return array('isSuccess' => false, 'message' => json_convert('Чтобы отправить рассылку, Вы должны иметь доступ на ее изменение'));
        }

        // если есть несозданные письма - то ругаемся
        $cmd = $con->prepare("select count(id) as cnt from iris_mailing_contact where mailingid=:mailingid and emailid is null");
        $cmd->execute(array(":mailingid" => $mailingId));
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
        if ($res[0]['cnt'] != 0) {
            return array('isSuccess' => false, 'message' => json_convert('У некоторых получателей отсутсвуют письма. Их нужно сгенерировать Во вкладке "Получатели"'));
        }

        // если нет ни одного получателя письма - то ругаемся
        $cmd = $con->prepare("select count(id) as cnt from iris_mailing_contact where mailingid=:mailingid");
        $cmd->execute(array(":mailingid" => $mailingId));
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
        if ($res[0]['cnt'] == 0) {
            return array('isSuccess' => false, 'message' => json_convert('Нужно добавить хотя бы одного получателя, чтобы отправить рассылку'));
        }

        // получение количества уже отправленных писем и общего количества писем
        $mailingStatus = $this->getMailingStatus($mailingId);
        $sendCount = $mailingStatus['sendCount'];
        $allCount = $mailingStatus['allCount'];

        // получение списка писем для отправки (которые еще не отправлены)
        $sql  = "select T0.id as mc_id, T0.emailid as emailid from iris_mailing_contact T0 ";
        $sql .= "left join iris_email T1 on T0.emailid = T1.id ";
        $sql .= "left join iris_emailtype T2 on T1.emailtypeid = T2.id ";
        $sql .= "where mailingid=:mailingid ";
        $sql .= "and T2.code = 'Mailing_outbox'";
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":mailingid" => $mailingId));
        $emails = $cmd->fetchAll(PDO::FETCH_ASSOC);

        if (count($emails) > 0) {
            // установим дату начала рассылки
            $cmd = $con->prepare("update iris_mailing set startdate=now() where id=:id and startdate is null");
            $cmd->execute(array(":id" => $mailingId));
        }

        // отправка писем будет осуществляться через g_Email
        $className = $this->_Loader->getActualClassName('sections\\Email\\g_Email');
        $EmailClass = new $className($this->_Loader);

        // отправка писем и подсчет их количества
        $count = 0;
        foreach($emails as $email) {
            $res = $EmailClass->sendEmail(array(
                "recordId" => $email['emailid'],
                "sendMode" => 'Mailing_outbox'
            ));
            if ($res['status'] != '+') {
                return array('isSuccess' => false, 'message' => json_convert('Не удалось отправить письмо рассылки<br>' . UtfDecode($res['message']) . '<br>Попробуйте повторить операцию еще раз<br>Рассыла прервана'));
            }

            // установка времени отправки в iris_mailing_contact для отправленного письма
            $cmd = $con->prepare("update iris_mailing_contact set senddate=now() where id=:id");
            $cmd->execute(array(":id" => $email['mc_id']));

            $count++; // подсчет количества отправленных за запрос писем

            // смотрим время выполнения, заканчиваем если осталось 10 сек
            $execTime = $this->getSeconds() - $startTime;
            if ($execTime + 10 > ini_get('max_execution_time')) {
                break;
            }
        }

        // если отправили все письма, то
        if ($sendCount + $count >= $allCount) {
            // сделаем рассылку доступной только для чтения
            GetRecordPermissions('iris_mailing', $mailingId, $recordPermissions, $con);
            foreach ($recordPermissions as $key => $val) {
                $recordPermissions[$key]['w'] = 0;
            }
            ChangeRecordPermissions('iris_mailing', $mailingId, $recordPermissions, $con);

            // установим дату окончания рассылки (и признак отправлена)
            $cmd = $con->prepare("update iris_mailing set enddate=now() where id=:id");
            $cmd->execute(array(":id" => $mailingId));
        }

        return array('isSuccess' => true, 'sendCount' => $sendCount + $count, 'allCount' => $allCount);
    }

    private function getSeconds() {
        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        return $mtime;
    }

    function getMailingStatus($params) {
        $mailingId = $params['mailingId'];

        // получение количества уже отправленных писем и общего количества писем
        $con = $this->connection;
        $sql  = "select count(T0.id) as cnt, T2.code as code from iris_mailing_contact T0 ";
        $sql .= "left join iris_email T1 on T0.emailid = T1.id ";
        $sql .= "left join iris_emailtype T2 on T1.emailtypeid = T2.id ";
        $sql .= "where mailingid=:mailingid ";
        $sql .= "group by T2.code ";
        $sql .= "order by T2.code desc";
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":mailingid" => $mailingId));
        $count = $cmd->fetchAll(PDO::FETCH_ASSOC);
        $sendCount = ($count[0]['code'] == 'Mailing_outbox' ? 0 : (int)$count[0]['cnt']);
        $allCount = (int)$count[0]['cnt'] + (!empty($count[1]['cnt']) ? (int)$count[1]['cnt'] : 0);

        return array('isSuccess' => true, 'sendCount' => $sendCount, 'allCount' => $allCount);
    }
}