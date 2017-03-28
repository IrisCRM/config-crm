<?php

namespace Iris\Config\CRM\sections\Import;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки импорта
 */
class s_Import extends Config
{
    function __construct()
    {
        parent::__construct(array('common/Lib/lib.php'));
    }


    function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $con = $this->connection;

        $result = null;

        //Значения справочников
        $this->getValuesFromTables($result, array(
            '{ImportType}' => 'XLS',
        ));

        //Ответственный
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $con, $result);

        //Кодировка
        $result = FieldValueFormat('Encoding', 'cp1251', null, $result);


        return $result;
    }
}
