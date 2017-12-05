<?php

namespace Iris\Config\CRM\sections\Emailaccount;

use Config;
use Iris\Iris;

class ds_EmailAccount_mailbox extends Config
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

        $result = null;

        $result = FieldValueFormat('LastUID', 0, null, $result);

        return $result;
    }
}
