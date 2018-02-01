<?php

namespace Iris\Config\CRM\sections\User;

use Config;
use Iris\Iris;

/**
 * Таблица раздела пользователи
 */
class g_User extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
        ));
    }

    public function forcedLogout($params) {
        $userId = $params['userId'];

        return array("isSuccess" => forcedLogout($userId));
    }

    function renderIncomingMessagesDialog($params)
    {
        // Описание колонок, которые будут отображаться в таблице
        $columns = array(
            'date' => array(
                'caption' => 'Дата',
                'type' => 'datetime',
                'width' => '10%',
                'sort' => 'asc',
            ),
            'chatId' => array(
                'caption' => 'ID чата',
                'type' => 'int',
                'width' => '15%',
            ),

            'userName' => array(
                'caption' => 'Пользователь',
                'type' => 'string',
                'width' => '15%',
            ),
            'lastName' => array(
                'caption' => 'Фамилия',
                'type' => 'string',
                'width' => '15%',
            ),
            'firstName' => array(
                'caption' => 'Имя',
                'type' => 'string',
                'width' => '15%',
            ),
            'message' => array(
                'caption' => 'Сообщение',
                'type' => 'string',
                'width' => '30%',
                'sort' => 'asc',
            ),
        );

        $parameters = array(
            'grid_id' => 'custom_grid_'. md5(time() . rand(0, 10000)),
            'lines' => 2,
        );

        $values = $this->getTelegramIncomingMessages();

        // Подготовка данных для представления таблицы
        $data = $this->getCustomGrid($columns, $values, $parameters);

        // Построение представления таблицы
        $result = array(
            'Card' => $this->renderView('grid', $data),
            'GridId' => $parameters['grid_id'],
        );
        return $result;

    }

    function getTelegramIncomingMessages()
    {
        $api_key = GetSystemVariableValue('telegram_bot_api_key');

        if (!$this->_User->isAdmin()) {
            return array("Error" => "Функция доступна только администраторам");
        }

        if ($api_key === "<укажите_токен>" or empty($api_key)) {
            return array("Error" => "Системный параметр " .
                "telegram_bot_api_key не инициализирован");
        }

        $updates = $this->getTelegramUpdates($api_key);

        if (isset($updates["Error"])) {
            return $updates;
        }

        $result = $this->getIncomingMessages($updates);

        return $result;
    }

    function getTelegramUpdates($api_key)
    {
        $url = "https://api.telegram.org/bot" . $api_key . '/getUpdates';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);

        if ($data["ok"] === false) {
            return array("Error" => $data["error_code"] . ": " .
                $data["description"]);
        }

        return $data;
    }

    function getIncomingMessages($updates)
    {
        $result = array();
        $message = array();

        foreach ($updates["result"] as $update) {
            $message = $update["message"];
            $result[] = array(
                "id" => $message["chat"]["id"],
                "date" => date("d.m.Y H:i:s", $message["date"]),
                "chatId" => $message["chat"]["id"],
                "userName" => $message["chat"]["username"],
                "lastName" => $message["chat"]["last_name"],
                "firstName" => $message["chat"]["first_name"],
                "message" => $message["text"],
            );
        }

        return $result;
    }
}
