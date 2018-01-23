<?php
//********************************************************************
// Раздел E-mail. карточка
//********************************************************************

namespace Iris\Config\CRM\sections\Email;

use Config;
use Iris\Iris;

include_once Iris::$app->getCoreDir() . 'core/engine/printform.php';

class c_Email extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php'
        ));
    }

    function FillTemplate($params, $result = null) {
        $contactId = $params['contactId'];
        $templateId = $params['templateId'];
        $isFillSubject = $params['isFillSubject'];
        $isFillBody = $params['isFillBody'];
        $address = $params['address'];

        $con = $this->connection;

        list($subject, $body) = GetFieldValuesByID('email', $templateId, array('subject', 'body'), $con);

        if ($isFillSubject) {
            $subject = FillFormFromText($subject, 'Contact', $contactId);
            $result = FieldValueFormat('Subject', $subject, '', $result);
        }

        if ($isFillBody) {
            $body = FillFormFromText($body, 'Contact', $contactId);
            $result = FieldValueFormat('body', $body, '', $result);
        }

        //Также заполним при необходимости адрес email
        if (!$address && $contactId) {
            $address = GetFieldValueByID('contact', $contactId, 'email', $con);
            $result = FieldValueFormat('e_to', $address, '', $result);
        }

        return $result;
    }

    function getReplyFields($params) {
        $replyEmailId = $params['replyEmailId'];
        $replyToAll = isset($params['replyToAll']) ? $params['replyToAll'] : false;
        $con = $this->connection;
        $matches = [];

        $currentEmailAccountID = GetFieldValueByID("Email", $replyEmailId, "EmailAccountID");
        $currentEmailAddress = GetFieldValueByID("Emailaccount", $currentEmailAccountID, "email");

        $result = GetFormatedFieldValuesByFieldValue('Email', 'ID', $replyEmailId, array('e_from', 'e_cc', 'Subject', 'body'), $con);
        $result['FieldValues'][0]['Name'] = 'e_to';

        if ($replyToAll) {
            $to = GetFieldValueByID('Email', $replyEmailId, 'e_to', $con);
            $addresses = $result['FieldValues'][0]['Value'] . ', ' . $to;
            $result['FieldValues'][0]['Value'] = $this->filterAddress(
                $addresses, $currentEmailAddress);
            $result['FieldValues'][1]['Value'] = $this->filterAddress(
                $result['FieldValues'][1]['Value'], $currentEmailAddress);
        } else {
            $result['FieldValues'][1]['Value'] = "";
        }

        // miv 02.08.2010: если письмо привязано к инциденту, то в теме письма долен быть его номер
        $subject = $result['FieldValues'][2]['Value'];
        list($incident_id) = GetFieldValuesByFieldValue('email', 'id', $replyEmailId, array('incidentid'), $con);
        if ($incident_id != '') {
            list($incident_number) = GetFieldValuesByFieldValue('incident', 'id', $incident_id, array('number'), $con);
            $result = FieldValueFormat('IncidentID', $incident_id, $incident_number, $result);
            if (iris_preg_match("/\\[\\d{6}-\\d+\\]/", $subject, $matches, PREG_OFFSET_CAPTURE) == 0) {
                // если в теме письма не обнаружили инцидента
                $result['FieldValues'][2]['Value'] = 'Re: ['.$incident_number.'] '.$result['FieldValues'][2]['Value'];
            }
        } else {
            if (iris_substr($subject, 0, 3) != 'Re:')
                $result['FieldValues'][2]['Value'] = 'Re: '.$subject;
        }

        // подставим текст письма как шаблон ответа + текст старого письма
        $templateid = GetFieldValueByFieldValue('contact', 'id', GetUserId(), 'emailtemplateid', $con);
        $templatebody = GetFieldValueByFieldValue('email', 'id', $templateid, 'body', $con);
        $contactid = GetFieldValueByFieldValue('email', 'id', $replyEmailId, 'contactid', $con);
        $templatebody = FillFormFromText($templatebody, 'Contact', $contactid);

        $parentbody = '<br><br>Вы писали:<br><BLOCKQUOTE style="margin: 5px 0 0 5px; padding: 0 0 0 5px; border-left: 2px solid #484F9E">'.GetFieldValueByFieldValue('email', 'id', $replyEmailId, 'body', $con).'</BLOCKQUOTE>';
        $result['FieldValues'][3]['Value'] = json_convert($templatebody).json_convert($parentbody);
        $result['FieldValues'][] = array("Name" => '_parent_body', "Value" => json_convert($parentbody));

        $result = GetLinkedValues('Email', $replyEmailId, array('Account', 'Contact'), $con, $result);

        return $result;
    }

    function filterAddress($address, $filterEmail) {
        $addresses = explode(",", $address);
        $result = [];
        $isExists = false;

        $result = array_filter($addresses, function($item) use ($filterEmail) {
            $isNotExists = strpos($item, $filterEmail) === false;
            return $isNotExists;
        });

        return implode(",", $result);
    }

    function getForwardFields($params) {
        $replyEmailId = $params['forwardEmailId'];

        $result = GetFormatedFieldValuesByFieldValue('Email', 'ID', $replyEmailId, ['Subject', 'body']);

        $subject = $result['FieldValues'][0]['Value'];
        list($incidentId) = GetFieldValuesByFieldValue('email', 'id', $replyEmailId, ['incidentid']);
        if ($incidentId != '') {
            list($incidentNumber) = GetFieldValuesByFieldValue('incident', 'id', $incidentId, ['number']);
            $result = FieldValueFormat('IncidentID', $incidentId, $incidentNumber, $result);
            if (iris_preg_match("/\\[\\d{6}-\\d+\\]/", $subject, $matches, PREG_OFFSET_CAPTURE) == 0) {
                // если в теме письма не обнаружили инцидента
                $result['FieldValues'][0]['Value'] = 'Fwd: ['.$incidentNumber.'] '.$result['FieldValues'][0]['Value'];
            }
        } else {
            if (iris_substr($subject, 0, 3) != 'Re:') {
                $result['FieldValues'][0]['Value'] = 'Fwd: ' . $subject;
            }
        }

        // подставим текст письма как шаблон ответа + текст старого письма
        $templateid = GetFieldValueByFieldValue('contact', 'id', GetUserId(), 'emailtemplateid');
        $templatebody = GetFieldValueByFieldValue('email', 'id', $templateid, 'body');
        $templatebody = FillFormFromText($templatebody, 'Contact', null);

        $parentbody = '<br><br>Пересылаемое сообщение:<br>'
            . '<BLOCKQUOTE style="margin: 5px 0 0 5px; padding: 0 0 0 5px; border-left: 2px solid #484F9E">'
            . GetFieldValueByFieldValue('email', 'id', $replyEmailId, 'body')
            . '</BLOCKQUOTE>';
        $result['FieldValues'][1]['Value'] = json_convert($templatebody).json_convert($parentbody);
        $result['FieldValues'][] = [
            "Name" => '_parent_body',
            "Value" => json_convert($parentbody),
        ];

        return $result;
    }
}
