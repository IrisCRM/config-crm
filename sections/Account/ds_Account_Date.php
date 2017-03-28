<?php

namespace Iris\Config\CRM\sections\Account;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки компании
 */
class ds_Account_Date extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }


    function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        };

        $con = GetConnection();

        $result = null;

        // Дата
        $Date = GetCurrentDBDate($con);
        $result = FieldValueFormat('Date', $Date, null, $result);

        return $result;
    }
}
