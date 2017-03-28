<?php
//********************************************************************
// Раздел "Рассылка". серверная логика карточки
//********************************************************************

namespace Iris\Config\CRM\sections\Mailing;

use Config;
use Iris\Iris;

class s_Mailing extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    function onPrepare($params)
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $con = $this->connection;

        //Ответственный
        $userName = GetUserName();
        $result = GetDefaultOwner($userName, $con, $result);

        $result['Attributes'][0]['FieldName'] = 'StartDate';
        $result['Attributes'][0]['AttributeName'] = 'disabled';
        $result['Attributes'][0]['AttributeValue'] = 'yes';

        $result['Attributes'][1]['FieldName'] = 'EndDate';
        $result['Attributes'][1]['AttributeName'] = 'disabled';
        $result['Attributes'][1]['AttributeValue'] = 'yes';

        return $result;
    }
}
