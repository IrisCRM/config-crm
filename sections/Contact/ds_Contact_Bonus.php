<?php

namespace Iris\Config\CRM\sections\Contact;

use Config;
use Iris\Iris;

/**
 * Серверная логика вкладки Бонусы карточки контакта
 */
class ds_Contact_Bonus extends Config
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
        }

        // Дата
        return FieldValueFormat('Date', GetCurrentDBDate(null));
    }

}
