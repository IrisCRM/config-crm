<?php

namespace Iris\Config\CRM\sections\Bug;

use Config;
use Iris\Iris;

/**
 * Карточка замечания
 */
class c_Bug extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, [ 'common/Lib/lib.php' ]);
    }

    public function onChangeFindDate($params)
    {
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);
        $len = count($result['FieldValues']);
        $result['FieldValues'][$len-1]['Name'] = 'FindID'; 
        return $result;
    }

    public function onChangeEditDate($params)
    {
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);
        $len = count($result['FieldValues']);
        $result['FieldValues'][$len-1]['Name'] = 'EditID'; 
        return $result;
    }

    public function onChangeVerifyDate($params)
    {
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);
        $len = count($result['FieldValues']);
        $result['FieldValues'][$len-1]['Name'] = 'VerifyID'; 
        return $result;
    }

    public function onChangeFindID($params)
    {
        return FieldValueFormat('FindDate', GetCurrentDBDate($this->connection));
    }

    public function onChangeEditID($params)
    {
        return FieldValueFormat('EditDate', GetCurrentDBDate($this->connection));
    }

    public function onChangeVerifyID($params)
    {
        return FieldValueFormat('VerifyDate', GetCurrentDBDate($this->connection));
    }

}




/**********************************************************************
Раздел "Замечания". Карточка.
**********************************************************************/
/*

//Обработка изменения значения поля
function Bug_FieldOnChange($p_FieldName, $p_FieldValue)
{
    $con = GetConnection();        
        
    switch ($p_FieldName) {

        //Получить id текущего пользователя
        case 'FindDate':
            $result = GetDefaultOwner(GetUserName(), $con, $result);
            $len = count($result['FieldValues']);
            $result['FieldValues'][$len-1]['Name'] = 'FindID'; 
            break;
            
        //Получить id текущего пользователя
        case 'EditDate':
            $result = GetDefaultOwner(GetUserName(), $con, $result);
            $len = count($result['FieldValues']);
            $result['FieldValues'][$len-1]['Name'] = 'EditID'; 
            break;
            
        //Получить id текущего пользователя
        case 'VerifyDate':
            $result = GetDefaultOwner(GetUserName(), $con, $result);
            $len = count($result['FieldValues']);
            $result['FieldValues'][$len-1]['Name'] = 'VerifyID'; 
            break;


        //Получить текущую дату
        case 'FindID':
            $Date = GetCurrentDBDate($con);
            $result = FieldValueFormat('FindDate', $Date, null, $result);
            break;
            
        //Получить id текущего пользователя
        case 'EditID':
            $Date = GetCurrentDBDate($con);
            $result = FieldValueFormat('EditDate', $Date, null, $result);
            break;
            
        //Получить id текущего пользователя
        case 'VerifyID':
            $Date = GetCurrentDBDate($con);
            $result = FieldValueFormat('VerifyDate', $Date, null, $result);
            break;
            
        default:
            //TODO: сделать правильную посылку и обработку сообщений об ошибке
            $result = 'Неверное название поля: '.$p_FieldName;
    }

    return $result;
}

*/