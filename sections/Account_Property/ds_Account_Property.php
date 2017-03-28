<?php

namespace Iris\Config\CRM\sections\Account_Property;

use Config;
use Iris\Iris;
use PDO;

/**
 * Серверная логика карточки реквезитов
 */
class ds_Account_Property extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $result = null;
        $result = FieldValueFormat('DirectorPosition',
                'Генеральный директор', null, $result);
        $result = FieldValueFormat('DirectorPositionRP', 
                'Генерального директора', null, $result);
        $result = FieldValueFormat('reason', 'Устава', null, $result);
        $result = FieldValueFormat('ismain', true, null, $result);

        return $result;
    }

    function onAfterPost($p_table, $p_id, $OldData, $NewData) 
    {
        $account_id = GetArrayValueByName($NewData['FieldValues'], 'accountid');
        $ismain = GetArrayValueByName($NewData['FieldValues'], 'ismain');
        if ($account_id != null) {
            $con = db_connect();
            // если у этой компани есть только одни реквезиты, то сделаем их основными
            $cmd = $con->prepare("select count(id) as cnt from iris_account_property where accountid = :accountid");
            $cmd->execute(array(":accountid" => $account_id));
            $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
            if ($res[0]['cnt'] == 1) {
                $cmd = $con->prepare("update iris_account_property set ismain=1 where id=:id");
                $cmd->execute(array(":id" => $p_id));
            }
            
            if ($ismain == 1) {
                // miv 29.07.2011: сбросим галочку "Основные реквезиты" у остальных реквизитов компании, если это основные
                $cmd = $con->prepare("update iris_Account_Property set ismain = 0 where accountid = :accountid and id <> :rec_id");
                $cmd->execute(array(":rec_id" => $p_id, ":accountid" => $account_id));
            }
        }
    }
}
