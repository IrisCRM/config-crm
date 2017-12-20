<?php

namespace Iris\Config\CRM\sections\User;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки пользователя
 */
class s_User extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            "common/Lib/lib.php",
        ));
    }

    function onBeforePost($parameters) {
        $password = GetArrayValueByName($parameters['new_data']['FieldValues'], 
            'Password');
        if (!$parameters['old_data'] && !$password) {
            return array('Error' => 'Необходимо указать пароль');
        } 

        return $parameters['new_data'];
    }
}
