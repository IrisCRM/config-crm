<?php

namespace Iris\Config\CRM\sections\Answer;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки решения
 */
class s_Answer extends Config
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

        $result = null;

        //Ответственный    
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);

        //Автор
        $owner = $result['FieldValues'][count($result['FieldValues'])-1];
        $result = FieldValueFormat('AuthorID', $owner['Value'], 
                $owner['Caption'], $result);

        //Номер
        $Number = GenerateNewNumber('AnswerNumber', null, $this->connection);
        $result = FieldValueFormat('Number', $Number, null, $result);
        
        //Дата
        $Date = GetCurrentDBDate($this->connection);
        $result = FieldValueFormat('Date', $Date, null, $result);

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data)
    {
        // Если создаём запись
        if (!$old_data) {
            UpdateNumber('Answer', $id, 'AnswerNumber');
        }
    }

}
