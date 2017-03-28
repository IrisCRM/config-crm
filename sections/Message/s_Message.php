<?php


namespace Iris\Config\CRM\sections\Message;

use Config;

/**
 * Серверная логика карточки опроса
 */
class s_Message extends Config
{
    /**
     * Функция вызывается перед сохранением карточки
     */
    function onBeforePost($params) {
        // запрещаем изменять сохраненные сообщения
        if (isset($params['old_data'])) {
            return [
                'Error' => 'Нельзя изменять сохраненные сообщения'
            ];
        }
    }

}
