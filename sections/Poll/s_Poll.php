<?php

namespace Iris\Config\CRM\sections\Poll;

use Config;

/**
 * Серверная логика карточки опроса
 */
class s_Poll extends Config
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

        $result = null;

        //Значения справочников
        $result = GetDictionaryValues([
            ['Dict' => 'PollState', 'Code' => 'plan']
        ], $this->connection, $result);

        //Ответственный
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $this->connection, $result);

        return $result;
    }

}
