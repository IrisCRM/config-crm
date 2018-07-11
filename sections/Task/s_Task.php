<?php

namespace Iris\Config\CRM\sections\Task;

use Config;
use Iris\Iris;
use IrisUser;
use PDO;

/**
 * Серверная логика карточки дела
 */
class s_Task extends Config
{
    public function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    public function onBeforePostContactID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'ContactID');
        return $this->getLinkedValues('{Contact}', $id, 
                array('{{Account}}', '{{Object}}'));
    }

    public function onBeforePostObjectID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'ObjectID');
        return $this->getLinkedValues('{Object}', $id, 
                array('{{Account}}', '{{Contact}}'));
    }

    public function onBeforePostProjectID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'ProjectID');
        return $this->getLinkedValues('{Project}', $id, 
                array('{{Account}}', '{{Object}}', '{{Contact}}'));
    }

    public function onBeforePostIssueID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'IssueID');
        return $this->getLinkedValues('{Issue}', $id, 
                array('{{Product}}'));
    }

    public function onBeforePostBugID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'BugID');
        $result = $this->getLinkedValues('{Bug}', $id, 
                array('{{Project}}', '{{Issue}}'));

        $id = $this->fieldValue($result, 'ProjectID');
        $this->mergeFields($result, $this->getLinkedValues('{Project}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Object}}')), 
                false, true);

        $id = $this->fieldValue($result, 'IssueID');
        $this->mergeFields($result, $this->getLinkedValues('{Issue}', $id, 
                array('{{Product}}')), 
                false, true);

        return $result;
    }

    public function onBeforePostIncidentID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'IncidentID');
        return $this->getLinkedValues('{Incident}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Object}}',
                '{{Product}}', '{{Issue}}', '{{Marketing}}', '{{Space}}',
                '{{Project}}', '{{Offer}}', '{{Pact}}', '{{Invoice}}',
                '{{Payment}}', '{{FactInvoice}}', '{{Document}}'));
    }

    public function onBeforePostOfferID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'OfferID');
        $result = $this->getLinkedValues('{Offer}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}'));

        $id = $this->fieldValue($result, 'ProjectID');
        $this->mergeFields($result, $this->getLinkedValues('{Project}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Object}}')), 
                false, true);

        return $result;
    }

    public function onBeforePostPactID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'PactID');
        $result = $this->getLinkedValues('{Pact}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}'));

        $id = $this->fieldValue($result, 'ProjectID');
        $this->mergeFields($result, $this->getLinkedValues('{Project}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Object}}')), 
                false, true);

        return $result;
    }

    public function onBeforePostInvoiceID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'InvoiceID');
        $result = $this->getLinkedValues('{Invoice}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}',
                '{{Pact}}', '{{Offer}}'));

        $id = $this->fieldValue($result, 'PactID');
        $this->mergeFields($result, $this->getLinkedValues('{Pact}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}')), 
                false, true);

        $id = $this->fieldValue($result, 'ProjectID');
        $this->mergeFields($result, $this->getLinkedValues('{Project}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Object}}')), 
                false, true);

        return $result;
    }

    public function onBeforePostPaymentID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'PaymentID');
        $result = $this->getLinkedValues('{Payment}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}',
                '{{Pact}}', '{{Invoice}}'));

        $id = $this->fieldValue($result, 'InvoiceID');
        $this->mergeFields($result, $this->getLinkedValues('{Invoice}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}', '{{Pact}}')), 
                false, true);

        $id = $this->fieldValue($result, 'PactID');
        $this->mergeFields($result, $this->getLinkedValues('{Pact}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}')), 
                false, true);

        $id = $this->fieldValue($result, 'ProjectID');
        $this->mergeFields($result, $this->getLinkedValues('{Project}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Object}}')), 
                false, true);

        return $result;
    }

    public function onBeforePostFactInvoiceID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'FactInvoiceID');
        $result = $this->getLinkedValues('{FactInvoice}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}',
                '{{Pact}}', '{{Invoice}}'));

        $id = $this->fieldValue($result, 'InvoiceID');
        $this->mergeFields($result, $this->getLinkedValues('{Invoice}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}', '{{Pact}}')), 
                false, true);

        $id = $this->fieldValue($result, 'PactID');
        $this->mergeFields($result, $this->getLinkedValues('{Pact}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}')), 
                false, true);

        $id = $this->fieldValue($result, 'ProjectID');
        $this->mergeFields($result, $this->getLinkedValues('{Project}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Object}}')), 
                false, true);

        return $result;
    }

    public function onBeforePostDocumentID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'DocumentID');
        $result = $this->getLinkedValues('{Document}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}', '{{Pact}}'));

        $id = $this->fieldValue($result, 'PactID');
        $this->mergeFields($result, $this->getLinkedValues('{Pact}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Project}}')), 
                false, true);

        $id = $this->fieldValue($result, 'ProjectID');
        $this->mergeFields($result, $this->getLinkedValues('{Project}', $id, 
                array('{{Account}}', '{{Contact}}', '{{Object}}')), 
                false, true);

        return $result;
    }

    public function onBeforePostTaskResultID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'TaskResultID');
        $task = $this->_DB->getRecordById($id, '{TaskResult}', 'code');

        $result = null;
        if ($task['code'] == 'Completed') {
            $this->getValuesFromTables($result, array(
                '{TaskState}' => 'Finished',
            ));
            $date = $this->_Local->dbDateTimeToLocal($this->_DB->datetime());
            $this->mergeFields($result, $this->formatField('FinishDate', $date));
            $this->mergeFields($result, $this->formatField('IsRemind', 0));
        }

        return $result;
    }

    public function onBeforePostNextTaskTargetID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'NextTaskTargetID');
        $target = $this->_DB->getRecordById($id, '{TaskTarget}', 
                array('days', 'hours', 'minutes'));

        $next_date = $this->_Local->timeToLocalDateTime(
                $this->_Local->dbDateToTime($this->_DB->datetime()) + 
                60 * 60 * 24 * $target['days'] +
                60 * 60 * $target['hours'] +
                60 * $target['minutes']);
        return $this->formatField('NextStartDate', $next_date);
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            $cnt = $this->nextTasksCount($params['rec_id']);
            $this->addParameter($result, 'HaveNextTask', $cnt);
            return $result;
        }

        $result = $this->prepareDetail($params);

        // Значения справочников
        $this->getValuesFromTables($result, array(
            '{TaskState}' => 'Plan',
            '{TaskImportance}' => 'Normal',
            '{TaskType}' => 'Execute',
        ));

        // Дата
        $date = $this->_Local->dbDateTimeToLocal($this->_DB->datetime());
        $this->mergeFields($result, $this->formatField('StartDate', $date));

        // Ответственный
        $User = IrisUser::getInstance();
        $this->mergeFields($result, $this->formatField('OwnerID', 
                $User->property('id'), $User->property('name')));
        // Автор
        $this->mergeFields($result, $this->formatField('CreateID', 
                $User->property('id'), $User->property('name')));

        $card_params = null;
        if (isset($params['card_params'])) {
            if ($params['card_params'] == 'undefined') {
                $params['card_params'] = null;
            }
            $card_params = json_decode($params['card_params'], true);
        }

        if ($card_params != null) {
            // если карточку открыли через кнопку позвонить, то заполним поля компания, контакт, телефон, тип
            if ($card_params['mode'] == 'open_outcoming_call') {
                $sql  = "select T0.id as cid, T0.name as cname, T0.accountid as aid, T1.name as aname, null as oid, null as oname from iris_contact T0 ";
                $sql .= "left join iris_account T1 on T0.accountid = T1.id ";
                $sql .= "where ((T0.phone1=:phone or (T0.phone1 is null and :phone='')) and (T0.phone1addl=:addl or (T0.phone1addl is null and :addl=''))) ";
                $sql .= "or (T0.phone2=:phone)";
                $cmd = $this->connection->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => true));
                $cmd->execute(array(":phone" => $card_params['phone'], ":addl" => $card_params['phoneaddl']));
                $contactinfo = $cmd->fetchAll(PDO::FETCH_ASSOC); // получим контакт и его компанию по номеру телефона
                if ($contactinfo == null) {
                    // если не нашли по контакту, то ищем по объекту
                    $sql  = "select T2.id as cid, T2.name as cname, T1.id as aid, T1.name as aname, T0.id as oid, T0.name as oname from iris_object T0 ";
                    $sql .= "left join iris_account T1 on T0.accountid = T1.id left join iris_contact T2 on T0.contactid = T2.id ";
                    $sql .= "where (((T0.phone1=:phone or (T0.phone1 is null and :phone='')) and (T0.phone1addl=:addl or (T0.phone1addl is null and :addl=''))) ";
                    $sql .= "or ((T0.phone2=:phone or (T0.phone2 is null and :phone='')) and (T0.phone2addl=:addl or (T0.phone2addl is null and :addl=''))) ";
                    $sql .= "or ((T0.phone3=:phone or (T0.phone3 is null and :phone='')) and (T0.phone3addl=:addl or (T0.phone3addl is null and :addl=''))))";
                    $cmd = $this->connection->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => true));
                    $cmd->execute(array(":phone" => $card_params['phone'], ":addl" => $card_params['phoneaddl']));
                    $contactinfo = $cmd->fetchAll(PDO::FETCH_ASSOC); // получим компанию, контакт, объект по номеру телефона
                }
                if ($contactinfo == null) {
                    // если не нашли по контакту или объекту, то ищем по компании
                    $sql  = "select null as cid, null as cname, T0.id as aid, T0.name as aname, null as oid, null as oname from iris_account T0 ";
                    $sql .= "where (((T0.phone1=:phone or (T0.phone1 is null and :phone='')) and (T0.phone1addl=:addl or (T0.phone1addl is null and :addl=''))) ";
                    $sql .= "or ((T0.phone2=:phone or (T0.phone2 is null and :phone='')) and (T0.phone2addl=:addl or (T0.phone2addl is null and :addl=''))) ";
                    $sql .= "or ((T0.phone3=:phone or (T0.phone3 is null and :phone='')) and (T0.phone3addl=:addl or (T0.phone3addl is null and :addl=''))))";
                    $cmd = $this->connection->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => true));
                    $cmd->execute(array(":phone" => $card_params['phone'], ":addl" => $card_params['phoneaddl']));
                    $contactinfo = $cmd->fetchAll(PDO::FETCH_ASSOC); // получим компанию по номеру телефона
                }
                
                if ($contactinfo != null) {
                    $this->mergeFields($result, $this->formatField('ContactID', 
                            $contactinfo[0]['cid'], $contactinfo[0]['cname']));
                    $this->mergeFields($result, $this->formatField('AccountID', 
                            $contactinfo[0]['aid'], $contactinfo[0]['aname']));
                    $this->mergeFields($result, $this->formatField('ObjectID', 
                            $contactinfo[0]['oid'], $contactinfo[0]['oname']));
                }
                $this->mergeFields($result, $this->formatField('Phone', 
                        $card_params['phone']));
                $this->mergeFields($result, $this->formatField('PhoneAddl', 
                        $card_params['phoneaddl']));

                $this->getValuesFromTables($result, array(
                    '{TaskType}' => 'Call',
                ));
            }
            else
            if ($card_params['mode'] == 'addFromCalendar') {
                $this->mergeFields($result, $this->formatField('StartDate', 
                        $card_params['start']));
                $this->mergeFields($result, $this->formatField('FinishDate', 
                        $card_params['end']));
                if (!empty($card_params['user'])) {
                    $this->mergeFields($result, $this->formatField('OwnerID', 
                            $card_params['user'], $card_params['username']));
                }
            }
        }
        else {
            $finish_date = $this->_Local->timeToLocalDateTime(
                    $this->_Local->localDateTimeToTime($date) + 60 * 60 * 2);
            $this->mergeFields($result, $this->formatField('FinishDate', 
                    $finish_date));
        }

        return $result;
    }

    // Перед сохранением карточки
    public function onBeforePost(&$parameters) {
        $this->removeField($parameters['new_data'], 'CreateID');
        return $parameters['new_data'];
    }

    // После сохранения карточки
    public function onAfterPost($table, $id, $old_data, $new_data)
    {
        $old_owner_id = $this->fieldValue($old_data, 'ownerid');
        $new_owner_id = $this->fieldValue($new_data, 'ownerid');

        // Если еще нет дочерних дел, и указана новая цель, то создадим
        $cnt = $this->nextTasksCount($id);
        $targetid = $this->fieldValue($new_data, 'NextTaskTargetID');
        if ($cnt == 0 && $targetid) {
            $data = $new_data;
            $this->removeField($data, array(
                'TaskResultID',
                'NextTaskTargetID',
                'NextStartDate',
                'IsRemind',
                'RemindDate',
                'ModifyID',
                'ModifyDate',
                'CreateDate',
                'CreateID',
                'ID',
            ));
            $data_prepare = $this->onPrepare(array('mode' => 'insert'));
            $this->mergeFields($data, $data_prepare);

            $target = $this->_DB->getRecord($targetid, '{TaskTarget}', 
                    array('name', 'termhours', 'termminutes'));
            $nextstartdate = $this->fieldValue($new_data, 'NextStartDate');
            $nextfinishdate = $this->_Local->timeToDBDateTime(
                    $this->_Local->dbDateToTime($nextstartdate) + 
                    60 * 60 * $target['termhours'] +
                    60 * $target['termminutes']);
            $nextstartdate = $this->_Local->dbDateTimeToLocal($nextstartdate);
            $nextfinishdate = $this->_Local->dbDateTimeToLocal($nextfinishdate);

            $task_name = $target['name'];
            $account_id = $this->fieldValue($new_data, 'accountid');
            if ($account_id) {
                $account = $this->_DB->getRecordByID($account_id, '{Account}', 'name');
                if ($account && $account['name']) {
                    $task_name = $account['name'] . ': ' . $task_name;
                }
            }

            $this->mergeFields($data, $this->formatField('Name', $task_name));
            $this->mergeFields($data, $this->formatField('TaskTargetID', $targetid));
            $this->mergeFields($data, $this->formatField('PrevRecordID', $id));
            $this->mergeFields($data, $this->formatField('StartDate', $nextstartdate));
            $this->mergeFields($data, $this->formatField('FinishDate', $nextfinishdate));

            // Создаем новое дело с вызывом обработчиков и добавлением прав
            $this->saveRecord($data);
        }

        // Если требуется переключить стадию заказа, то переключаем
        if (!$old_data) {
            $this->switchProjectStage($new_data);
        }

        if ($old_owner_id != $new_owner_id) {
            return record_chown($table, $id, $old_owner_id, $new_owner_id, 
                    array("showMessage" => 1));
        }
    }

    public function nextTasksCount($id) {
        $res = $this->_DB->exec(
                'select count(id) as cnt ' 
                . 'from iris_task '
                . 'where prevrecordid = :id',
                array(':id' => $id));
        return $res[0]['cnt'];
    }

    public function switchProjectStage(&$new_data)
    {
        $projectid = $this->fieldValue($new_data, 'ProjectID');
        if ($projectid) {
            $currenttargetid = $this->fieldValue($new_data, 'TaskTargetID');
            $currenttarget = $this->_DB->getRecord(
                    $currenttargetid, '{TaskTarget}', 
                    array('ProjectStageID', 'dostagechange', 'onlyforward'));
            if ($currenttarget['dostagechange']) {
                $project = $this->_DB->getRecord(
                        $projectid, '{Project}', 'ProjectStageID');
                if ($project['projectstageid'] != 
                        $currenttarget['projectstageid']) {
                    $change = true;
                    if ($currenttarget['onlyforward']) {
                        $projectstage = $this->_DB->getRecord(
                                $project['projectstageid'], 
                                '{ProjectStage}', 'Number');
                        $newprojectstage = $this->_DB->getRecord(
                                $currenttarget['projectstageid'], 
                                '{ProjectStage}', 'Number');
                        if ($newprojectstage['number'] <=
                                $projectstage['number']) {
                            $change = false;
                        }
                    }
                    if ($change) {
                        $filter = array(
                            ':stageid' => $currenttarget['projectstageid'],
                            ':id' => $projectid,
                        );
                        $this->_DB->exec('update iris_project ' 
                                . 'set projectstageid = :stageid '
                                . 'where id = :id', $filter);
                    }
                }
            }
        }
    }

}
