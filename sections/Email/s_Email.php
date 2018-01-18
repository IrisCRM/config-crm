<?php
//********************************************************************
// Раздел E-mail. серверная логика карточки
//********************************************************************

namespace Iris\Config\CRM\sections\Email;

use Config;
use Iris\Credentials\Permissions;
use Iris\Iris;

class s_Email extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    function onPrepare($params)
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $con = $this->connection;

        //Значения справочников
        $result = GetDictionaryValues(
            array (
                array ('Dict' => 'EmailType', 'Code' => 'Outbox')
            ),
            $con);

        //Ответственный
        $userName = GetUserName();
        $result = GetDefaultOwner($userName, $con, $result);

        $templateId = GetFieldValueByFieldValue('contact', 'login', $userName, 'EmailTemplateID', $con);
        if ($templateId) {
            $templateName = GetFieldValueByID('email', $templateId, 'subject', $con);
            $result = FieldValueFormat('EmailTemplateID', $templateId, $templateName, $result);
        }

        return $result;
    }

    function onBeforePostContactID($params)
    {
        $con = $this->connection;
        $contactId = $this->fieldValue($params['old_data'], 'ContactID');
        $result = GetLinkedValues('Contact', $contactId, array('Account'), $con);
        $email = GetFieldValueByID('Contact', $contactId, 'email');
        $result = FieldValueFormat('e_to', $email, '', $result);

        return $result;
    }

    function onBeforePostEmailTypeID($params) {
        $emailTypeID = $this->fieldValue($params['old_data'], 'EmailTypeID');
        $EmailTypeCode = GetFieldValueByID('emailtype', $emailTypeID, 'Code');
        if ('Template' == $EmailTypeCode) {
            $result['Attributes'][0]['FieldName'] = 'EmailAccountID';
            $result['Attributes'][0]['AttributeName'] = 'mandatory';
            $result['Attributes'][0]['AttributeValue'] = 'no';
            $result['Attributes'][1]['FieldName'] = 'e_to';
            $result['Attributes'][1]['AttributeName'] = 'mandatory';
            $result['Attributes'][1]['AttributeValue'] = 'no';
            $result['Attributes'][2]['FieldName'] = 'e_from';
            $result['Attributes'][2]['AttributeName'] = 'mandatory';
            $result['Attributes'][2]['AttributeValue'] = 'no';
        }
        else {
            $result['Attributes'][0]['FieldName'] = 'EmailAccountID';
            $result['Attributes'][0]['AttributeName'] = 'mandatory';
            $result['Attributes'][0]['AttributeValue'] = 'yes';
            $result['Attributes'][1]['FieldName'] = 'e_to';
            $result['Attributes'][1]['AttributeName'] = 'mandatory';
            $result['Attributes'][1]['AttributeValue'] = 'yes';
            $result['Attributes'][2]['FieldName'] = 'e_from';
            $result['Attributes'][2]['AttributeName'] = 'mandatory';
            $result['Attributes'][2]['AttributeValue'] = 'yes';
        }

        return $result;
    }

    function onBeforePost($parameters) {
        if ($parameters['new_data']  === null) {
            $emailId = $this->fieldValue($parameters['old_data'], 'id');
            $className = $this->_Loader->getActualClassName('sections\\Email\\g_Email');
            $g_Email = new $className($this->_Loader);
            $g_Email->deleteFromImapServer($emailId);
        }
    }

    function onAfterPost($tableName, $recordId, $oldData, $newData) {
        // _reply_email_id 3a79d78c-9072-4317-1a80-2fb1c6b25784
        // _params {"replyEmailId":"3a79d78c-9072-4317-1a80-2fb1c6b25784"}
        $this->setParentEmailID($recordId, isset($_POST['_reply_email_id']) ? $_POST['_reply_email_id'] : null);

        if (isset($_POST['_forward_email_id'])) {
            $this->copyAttachments($recordId, $_POST['_forward_email_id']);
        }
    }

    /**
     * Проставление ссылки на отвечаемое письмо
     */
    protected function setParentEmailID($recordId, $parentEmailID) {
        $con = $this->connection;

        if (empty($parentEmailID)) {
            return;
        }

        $cmd = $con->prepare("update iris_email set parentemailid = :parentid where id=:id");
        $cmd->execute(array(":parentid" => $parentEmailID, ":id" => $recordId));
    }

    protected function copyAttachments($recordId,  $forwardEmailID)
    {
        $cmd = $this->connection->prepare("
            insert into iris_email_file (id, createid, createdate, fileid, emailid)
            (select iris_genguid() as id, :userid as createid,  now() as createdate, id as fileid, :emailid as emailid from iris_file where emailid = :forwardemailid
            union
            select iris_genguid() as id, :userid as createid,  now() as createdate, fileid as fileid, :emailid as emailid from iris_email_file where emailid = :forwardemailid);");
        $cmd->execute([
            ':userid' => $this->_User->property('id'),
            ':emailid' => $recordId,
            ':forwardemailid' => $forwardEmailID,
        ]);
    }

    /**
     * Show email
     * @param $params
     */
    public function show($params)
    {
        /** @var Permissions $permissions */
        $permissions = Iris::$app->getContainer()->get('credentails.permissions');
        if ($permissions->canRead('{email}', $params['id'])) {
            $body = GetFieldValueByID('Email', $params['id'], 'body');
        }
        else {
            $body = 'У вас нет доступа к просмотру этого письма';
        }
        echo $body;
    }
}
