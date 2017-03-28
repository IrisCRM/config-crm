<?php

namespace Iris\Config\CRM\sections\users;

use Config;
use Iris\Iris;

/**
 * Таблица раздела пользователи
 */
class g_users extends Config
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
}
