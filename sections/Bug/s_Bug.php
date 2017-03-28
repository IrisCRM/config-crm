<?php

namespace Iris\Config\CRM\sections\Bug;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки замечания
 */
class s_Bug extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, [ 'common/Lib/lib.php' ]);
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        //Значения справочников
        $result = GetDictionaryValues(
            array(
                array('Dict' => 'BugType', 'Code' => 'Error'),
                array('Dict' => 'BugState', 'Code' => 'Found'),
                array('Dict' => 'BugImportance', 'Code' => 'Normal')
                ), $this->connection);

        //Ответственный    
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);

        //Автор
        $owner = $result['FieldValues'][count($result['FieldValues'])-1];
        $result = FieldValueFormat('FindID', $owner['Value'], 
                $owner['Caption'], $result);
        
        //Номер
        $Number = GenerateNewNumber('BugNumber', null, $this->connection);        
        $result = FieldValueFormat('Number', $Number, null, $result);
        
        //Дата
        $Date = GetCurrentDBDate($this->connection);
        $result = FieldValueFormat('FindDate', $Date, null, $result);

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data)
    {
        // Если создаём запись
        if (!$old_data) {
            UpdateNumber('Bug', $id, 'BugNumber');
        }
    }

}
