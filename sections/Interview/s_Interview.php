<?php

namespace Iris\Config\CRM\sections\Interview;

use Config;

/**
 * Серверная логика карточки интервью
 */
class s_Interview extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }


    function onPrepare($params) 
    {
        if ($params['mode'] != 'insert') {
            return null;
        };

        $result = null;

        //Значения справочников
        $result = GetDictionaryValues(array(
                    array ('Dict' => 'InterviewState', 'Code' => 'plan')
                ), $this->connection, $result);

        //Ответственный
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $this->connection, $result);
      
        list ($ID, $Name) = GetShortUserInfo($UserName, $this->connection);
        $result = FieldValueFormat('OperatorID', $ID, $Name, $result);

        return $result;
    }

    public function onBeforePostContactID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'ContactID');
        $result = $this->getLinkedValues('{Contact}', $id, array('{{Account}}'));
        $phone = GetFieldValueByID('Contact', $id, 'Phone1', $this->connection);
        $this->mergeFields($result, $this->formatField('Phone', $phone));
        return $result;
    }

    public function onBeforePostAccountID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'AccountID');
        $result = [];
        $result = GetLinkedValuesDetailed('iris_Account', $id, [[
            'Field' => 'PrimaryContactID',
            'GetTable' => 'iris_Contact',
            'GetField' => 'Name',
        ]], $this->connection, $result);
        $result['FieldValues'][count($result['FieldValues'])-1]['Name'] = 'ContactID';

        return $result;
    }

    public function onBeforePostInterviewResultID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'InterviewResultID');
        $ResultCode = GetFieldValueByID('InterviewResult', $id, 'Code', $this->connection);
        $result = null;
        if ($ResultCode == 'finished') {
            //Дата последней попытки
            $date = GetCurrentDBDate($this->connection);
            $this->mergeFields($result, $this->formatField('LastDate', $date));
            //Состояние
            $result = GetDictionaryValues([
                ['Dict' => 'InterviewState', 'Code' => 'finished']
            ], $this->connection, $result);
            //TODO: подсчёт результата - тут или в ответах
        }
        return $result;
    }
}