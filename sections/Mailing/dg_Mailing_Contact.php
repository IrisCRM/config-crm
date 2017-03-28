<?php
//********************************************************************
// Раздел "Рассылка". серверная логика вкладки "Получатели"
//********************************************************************

namespace Iris\Config\CRM\sections\Mailing;

use Config;
use Iris\Iris;
use PDO;

include_once Iris::$app->getCoreDir() . 'core/engine/printform.php';

class dg_Mailing_Contact extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
            'common/Lib/report.php',
        ));
    }

    public function renderSelectContactDialog($params) {
        // Описание колонок, которые будут отображаться в таблице
        $columns = array(
            'name' => array(
                'caption' => 'ФИО',
                'type' => 'string',
                'width' => '50%',
            ),
            'account' => array(
                'caption' => 'Компания',
                'type' => 'string',
                'width' => '25%',
            ),
            'type' => array(
                'caption' => 'Тип',
                'type' => 'string',
                'width' => '25%',
            ),
        );
        // Выбираем данные для отображения в таблице
        $sql = $this->_DB->considerAccess('{contact}',
                "select T0.id as id, 
                T0.name as name,
                T1.name as account,
                T2.name as type
                from " . $this->_DB->tableName('{contact}') . " T0
                left join " . $this->_DB->tableName('{account}') . " T1 
                  on T1.id = t0.accountid
                left join " . $this->_DB->tableName('{contacttype}') . " T2 
                  on T2.id = t0.contacttypeid",
                "where T0.id not in (select MC.contactid from iris_mailing_contact MC where MC.mailingid=:mailingid)
                   and T0.email is not null ")
            . "order by t0.name";
        $filter = array(
            ':mailingid' => $params['mailingId'],
        );
        $values = $this->_DB->exec($sql, $filter);

        // Выбранная по умолчанию запись - либо следующая либо текущая цель
        $parameters = array(
            'grid_id' => 'custom_grid_'. md5(time() . rand(0, 10000)),
        );

        // Подготовка данных для представления таблицы
        $data = $this->getCustomGrid($columns, $values, $parameters);

        // Построение представления таблицы
        $result = array(
            'Card' => $this->renderView('grid', $data),
            'GridId' => $parameters['grid_id'],
        );
        return $result;
    }

    public function addContact($params) {
        $con = $this->connection;
        $mailingId = $params['mailingId'];
        $contactId = $params['contactId'];
        $permissions = array();


        // проверим, может ли данный пользователь править рассылку. если нет, то не дадим добавить контакт
        GetCurrentUserRecordPermissions('iris_mailing', $mailingId, $permissions, $con);
        if ($permissions['w'] == 0) {
            return array('isSuccess' => false, 'message' => json_convert('Для добавления контакта пользователь должен иметь права записи на рассылку'));
        }

        // проверим, не добавлен ли уже контакт
        $cmd = $con->prepare("select id from iris_mailing_contact where contactid=:contactid and mailingid = :mailingid");
        $cmd->execute(array(":contactid" => $contactId, ":mailingid" => $mailingId));
        $contact_exists = current($cmd->fetchAll(PDO::FETCH_ASSOC));
        if ($contact_exists['id'] != '') {
            return array('isSuccess' => false, 'message' => json_convert('Этот контакт уже добавлен'));
        }

        // добавим контакт
        $ins_cmd = $con->prepare("insert into iris_mailing_contact (id, contactid, mailingid) values (iris_genguid(), :contactid, :mailingid)");
        $ins_cmd->execute(array(":contactid" => $contactId, ":mailingid" => $mailingId));
        if ($ins_cmd->errorCode() != '00000') {
            return array('isSuccess' => false, 'message' => json_convert('Не удалось добавить контакт'));
        }

        return array('isSuccess' => true);
    }

    public function removeContact($params) {
        $con = $this->connection;
        $mailingId = $params['mailingId'];
        $contactId = $params['contactId'];
        $permissions = array();

        // проверим, может ли данный пользователь править рассылку. если нет, то не дадим удалить файл
        GetCurrentUserRecordPermissions('iris_mailing', $mailingId, $permissions, $con);
        if ($permissions['w'] == 0) {
            return array('isSuccess' => false, 'message' => json_convert('Для исключения контакта из рассылки пользователь должен иметь права записи на рассылку'));
        }

        // если у этого пользователя есть отправленное письмо расылки, то не дадим удалить
        $sql  = "select T0.emailid as id, T2.code as code from iris_mailing_contact T0 ";
        $sql .= "left join iris_email T1 on T0.emailid = T1.id ";
        $sql .= "left join iris_emailtype T2 on T1.emailtypeid = T2.id ";
        $sql .= "where T0.contactid=:contactid and T0.mailingid =:mailingid";
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":contactid" => $contactId, ":mailingid" => $mailingId));
        $email = current($cmd->fetchAll(PDO::FETCH_ASSOC));
        if ($email['code'] == 'Mailing_sent') {
            return array('isSuccess' => false, 'message' => json_convert('Невозможно удалить получателя, так как у него есть отправленное письмо'));
        }

        // исключим пользователя из рассылки
        $cmd = $con->prepare("delete from iris_mailing_contact where contactid = :contactid and mailingid = :mailingid");
        $cmd->execute(array(":contactid" => $contactId, ":mailingid" => $mailingId));
        if ($cmd->errorCode() != '00000') {
            return array('isSuccess' => false, 'message' => json_convert('Не удалось исключить контакт'));
        }

        // удалим письмо рассылки
        $cmd = $con->prepare("delete from iris_email where id=:id");
        if ($cmd->execute(array(":id" => $email['id'])) == 0) {
            return array('isSuccess' => false, 'message' => json_convert('Не удалось удалить письмо рассылки'));
        }

        return array('isSuccess' => true);
    }

    public function addContactFromReport($params) {
        $reportCode = $params['reportCode'];
        $reportId = null;
        $mailingId = $params['mailingId'];
        $filtersArray = $params['filters'];
        $filters = json_decode(json_encode($filtersArray)); // $filters must be object, not associative array

        if ($reportCode != '') {
            $con = db_connect();
            $cmd = $con->prepare("select id from iris_report where code=:code");
            $cmd->execute(array(":code" => $reportCode));
            $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
            $reportId = $res[0]['id'];
        }

        //Подготовка отчета
        list($sql, $reportParams, $show_info) = BuildReportSQL($reportId, $filters);
        $report_info = GetReportInfo($reportId);
        list($contacts, $tmp) = BuildReportData($show_info, $sql, $reportParams); //mnv: с новой версией отчётов работает так
        if (count($contacts) == 0) {
            return array('isSuccess' => false, 'message' => json_convert('Не найдено контактов, удовлетворяющих условиям поиска'));
        }

        $sql = '';
        $con = $this->connection;
        $cmd = $con->prepare("select id from iris_mailing_contact where mailingid=:mailingid and contactid=:contactid");
        foreach($contacts as $contact) {
            $cmd->execute(array(":mailingid" => $mailingId, ":contactid" => $contact['id']));
            $exists_id = current($cmd->fetchAll(PDO::FETCH_ASSOC));
            // если этого контакта еще нет в списке рассылки, то добавим его
            if ($exists_id == '') {
                $sql .= "insert into iris_mailing_contact (id, mailingid, contactid) values (iris_genguid(), '".$mailingId."', '".$contact['id']."');".chr(10);
            }
        }
        if ($sql == '') {
            return array('isSuccess' => false, 'message' => json_convert('Данные контакты уже добавлены в рассылку'));
        }

        if ($con->exec($sql) == 0) {
            return array('isSuccess' => false, 'message' => json_convert('Не удалось добавить контактов в рассылку'));
        }

        return array('isSuccess' => true);
    }

    public function previewEmail($params) {
        $contactId = $params['contactId'];
        $mailingId = $params['mailingId'];
        $con = $this->connection;
        $permissions = array();

        // проверим, может ли данный пользователь читать рассылку. если нет, то не дадим делать предпросмотр
        GetCurrentUserRecordPermissions('iris_mailing', $mailingId, $permissions, $con);
        if ($permissions['r'] == 0) {
            return array('isSuccess' => false, 'message' => json_convert('У пользователя нет прав для просмотра данной рассылки'));
        }

        $res = $this->createEmailFields($contactId, $mailingId);

        return array ('isSuccess' => true, "subject" => $res['subject'], "body" => $res['body']);
    }

    private function createEmailFields($contactId, $mailingId) {
        $con = $this->connection;
        $cmd = $con->prepare("select subject, text from iris_mailing where id=:id");
        $cmd->execute(array(":id" => $mailingId));
        $mailing = current($cmd->fetchAll(PDO::FETCH_ASSOC));

        $subject = FillFormFromText($mailing['subject'], 'Contact', $contactId);
        $body = FillFormFromText($mailing['text'], 'Contact', $contactId);

        return array ("subject" => $subject, "body" => $body);
    }

    public function createEmails($params) {
        $mailingId = $params['mailingId'];
        $con = $this->connection;
        $start_time = $this->getSeconds();

        // считаем из рассылки ответственного, почтовый ящик (2 поля)
        $mailing_cmd = $con->prepare("select T0.ownerid as ownerid, T0.emailaccountid as emailaccountid, T1.email as email from iris_mailing T0 left join iris_emailaccount T1 on T0.emailaccountid = T1.id where T0.id=:id");
        $mailing_cmd->execute(array(":id" => $mailingId));
        $mailing = current($mailing_cmd->fetchAll(PDO::FETCH_ASSOC));

        // тип письма - Рассылка исходящее
        $emailType = current($con->query("select id from iris_emailtype where code = 'Mailing_outbox'")->fetchAll(PDO::FETCH_ASSOC));

        // сформируем права доступа на добавляемые письма (права как у рассылки, только убираем запись)
        GetRecordPermissions('iris_mailing', $mailingId, $permissions, $con);
        foreach ($permissions as $key => $val) {
            $permissions[$key]['w'] = 0;
        }

        $sql = "select T0.contactid as id, T3.email as email, T3.accountid, T3.ownerid as ownerid from iris_mailing_contact T0 left join iris_contact T3 on T0.contactid = T3.id where T0.mailingid=:mailingid and T0.emailid is null order by T3.name";
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":mailingid" => $mailingId));
        $contacts = $cmd->fetchAll(PDO::FETCH_ASSOC);
        $leftCount = count($contacts);
        $createCount = 0;

        $sql = "insert into iris_email (id, e_from, emailaccountid, e_to, contactid, accountid, ownerid, emailtypeid, subject, body) values (:id, :e_from, :emailaccountid, :e_to, :contactid, :accountid, :ownerid, :emailtypeid, :subject, :body)";
        $ins_cmd = $con->prepare($sql);
        foreach ($contacts as $contact) {
            $email_fields = $this->createEmailFields($contact['id'], $mailingId);

            $new_id = create_guid();
            $ins_cmd->bindParam(":id", $new_id);
            $ins_cmd->bindParam(":e_from", $mailing['email']);
            $ins_cmd->bindParam(":emailaccountid", $mailing['emailaccountid']);
            $ins_cmd->bindParam(":e_to", $contact['email']);
            $ins_cmd->bindParam(":contactid", $contact['id']);
            $ins_cmd->bindParam(":accountid", $contact['accountid']);
            $ins_cmd->bindParam(":ownerid", $mailing['ownerid']);
            $ins_cmd->bindParam(":emailtypeid", $emailType['id']);
            $ins_cmd->bindParam(":subject", $email_fields['subject']);
            $ins_cmd->bindParam(":body", $email_fields['body']);
            if ($ins_cmd->execute() == 0) {
                return array('isSuccess' => false, 'message' => json_convert('ошибка при добавлении письма'));
            }

            // вставка прав доступа на письмо
            ChangeRecordPermissions('iris_email', $new_id, $permissions, $con);

            // дадим доступ на чтение ответственному за контакта (если у него не было доступа на чтение рассылки)
            GetUserRecordPermissions('iris_mailing', $mailingId, $contact['ownerid'], $contact_perm, $con);
            if ($contact_perm['r'] == 0) {
                $add_perm[] = array('userid' => $contact['ownerid'], 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
                ChangeRecordPermissions('iris_email', $new_id, $add_perm, $con);
            }

            // проставим ссылку на новое письмо в iris_mailing_contact
            $upd_cmd = $con->prepare("update iris_mailing_contact set emailid=:emailid where mailingid=:mailingid and contactid=:contactid");
            $upd_cmd->execute(array(":emailid"=> $new_id, ":mailingid" => $mailingId, ":contactid" => $contact['id']));
            if ($upd_cmd->errorCode() != '00000') {
                return array('isSuccess' => false, 'message' => json_convert('ошибка при добавлении письма в расссылку'));
            }

            // вставка файлов к письму
            $files_cmd = $con->prepare("insert into iris_email_file (id, fileid, emailid) (select iris_genguid(), fileid, '".$new_id."' from iris_mailing_file where mailingid=:mailingid)");
            if ($files_cmd->execute(array(":mailingid" => $mailingId)) == 0) {
                return array('isSuccess' => false, 'message' => json_convert('ошибка при добавлении файлов к письму'));
            }

            $createCount++;

            // если скрипт скоро завершит работу, то остановимся
            $exec_time = $this->getSeconds() - $start_time;
            if ($exec_time + 5 > ini_get('max_execution_time')) {
                return array('isSuccess' => true, 'leftcount' => $leftCount - $createCount, 'message' => '');
            }
        }

        return array('isSuccess' => true, 'message' => json_convert('Все письма созданы'));
    }

    private function getSeconds() {
        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        return $mtime;
    }

    public function deleteEmails($params) {
        $mailingId = $params['mailingId'];
        $con = $this->connection;
        $permissions = array();

        GetCurrentUserRecordPermissions('iris_mailing', $mailingId, $permissions, $con);
        if ($permissions['w'] == 0) {
            return array('isSuccess' => false, 'message' => json_convert('Для удаления писем необходимо иметь доступ на редактирование рассылки'));
        }

        $del_sql = "delete from iris_email where id in (select emailid from iris_mailing_contact where mailingid=:mailingid) and emailtypeid = (select id from iris_emailtype where code = 'Mailing_outbox')";
        $cmd = $con->prepare($del_sql);
        $cmd->execute(array(":mailingid" => $mailingId));
        if ($cmd->errorCode() != '00000') {
            return array('isSuccess' => false, 'message' => json_convert('Не удалось удалить письма рассылки'));
        }

        return array('isSuccess' => true, 'message' => '');
    }
}
