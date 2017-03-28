<?php

namespace Iris\Config\CRM\sections\Incident;

use Config;
use Iris\Iris;
use PDO;

/**
 * Серверная логика карточки инцидента
 */
class s_Incident extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
        ));
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $result = $this->prepareDetail($params);

        //Значения справочников
        $result = GetDictionaryValues(
            array (
                array ('Dict' => 'IncidentState', 'Code' => 'Accepted'),
                array ('Dict' => 'IncidentLevel', 'Code' => 'Level1')
            ), $this->connection, $result);

        //Ответственный    
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);

        //Номер
        $Number = GenerateNewNumber('IncidentNumber', null, $this->connection);
        $result = FieldValueFormat('Number', $Number, null, $result);
        
        //Дата
        $Date = GetCurrentDBDateTime($this->connection);
        $result = FieldValueFormat('Date', $Date, null, $result);
        
        if ($params['card_params'] != '') {
            $card_params = json_decode($params['card_params'], true);
            if ($card_params['mode'] == 'incident_from_email') {

                list($subject, $body, $account_id, $contact_id) = 
                    GetFieldValuesByFieldValue('email', 'id', 
                    $card_params['emailid'], 
                    array('subject', 'body', 'accountid', 'contactid'), 
                    $this->connection);
                // тема
                $result = FieldValueFormat('Name', $subject, null, $result);
                
                // содержимое письма
                $body = iris_str_replace(chr(13).chr(10), '', $body);
                $body = iris_str_replace(chr(10).chr(13), '', $body);
                $body = iris_str_replace('<br>', chr(10), $body);
                $body = iris_str_replace('<BR>', chr(10), $body);
                $body = strip_tags($body);
                $result = FieldValueFormat('Description', $body, null, $result);
                
                // компания
                $account_name = GetFieldValueByFieldValue('account', 'id', 
                        $account_id, 'name', $this->connection);
                $result = FieldValueFormat('AccountID', $account_id, 
                        $account_name, $result);
                
                // контакт
                $contact_name = GetFieldValueByFieldValue('contact', 'id', 
                        $contact_id, 'name', $this->connection);
                $result = FieldValueFormat('ContactID', $contact_id, 
                        $contact_name, $result);        

                // сообщил
                $result = FieldValueFormat('InformID', $contact_id, 
                        $contact_name, $result);        
            }
        }

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data)
    {
        // Если создаём запись
        if (!$old_data) {
            UpdateNumber('Incident', $id, 'IncidentNumber');
        }

        $params = $_POST['_params'];
        if ($params != '') {
            $card_params = json_decode($params, true);
            if ($card_params['mode'] == 'incident_from_email') {
                $res = $this->connection->query(
                        "select id from iris_incident where id = '" . $id . "'")
                        ->fetchAll(PDO::FETCH_ASSOC);
                if (count($res) == 0) {
                    return array("Error" => 'Не удалось сохранить инцидент. '
                            . 'Проверьте, чтобы в поле <b>Описание</b> текст '
                            . 'не превышал 1000 символов');
                }

                $cmd = $this->connection->prepare("update iris_email "
                        . "set incidentid = :incidentid where id = :emailid");
                $cmd->execute(array(
                    ":incidentid" => $id, 
                    ":emailid" => $card_params['emailid'],
                ));
                if ($cmd->errorCode() != '00000') {
                    return array("Error" => 
                            'Не удалось привязать письмо к инциденту');
                }
            }
        }
    }

}
