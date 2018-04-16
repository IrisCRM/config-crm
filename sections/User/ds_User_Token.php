<?php

namespace Iris\Config\CRM\sections\User;

use Config;
use Iris\Iris;

/**
 * Серверная логика вкладки Токены карточки пользователя
 */
class ds_User_Token extends Config
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

        $result = FieldValueFormat('code', $this->generateToken());
        $result = FieldValueFormat('isactive', 1, null, $result);

        return $result;
    }

    protected function generateToken()
    {
        $token_length = 20;
        $token = rtrim(base64_encode(random_bytes($token_length)), '=');
        $token = strtr($token, array(
            "+/" => "-_",
            "/" => "_",
            "|" => "_",
            "=" => "_",
            "-" => "_",
            "+" => "_"
        ));

        return $token;
    }

    // Перед сохранением карточки
    function onBeforePost($parameters) {
        $oldCode = GetArrayValueByName($parameters['old_data']['FieldValues'], 
                'code');
        $newCode = GetArrayValueByName($parameters['new_data']['FieldValues'], 
                'code');
        if ($oldCode && ($oldCode !== $newCode)) {
            return array('Error' => 'Смена токена невозможна');
        }

        return $parameters['new_data'];
    }

}
