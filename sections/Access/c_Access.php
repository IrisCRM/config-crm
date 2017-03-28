<?php

namespace Iris\Config\CRM\sections\Access;

use Config;
use Iris\Iris;
use PDO;

/**
 * Карточка счета
 */
class c_Access extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    public function applyAccess($params) {
        $tableId = $params['tableId'];
        $ids = $params['ids'];
        $access = $params['access'];

        $con = $this->connection;

        // Только админ
        $info = GetUserAccessInfo($con);
        if ($info['userrolecode'] != 'admin')
            return array("success" => 0, "message" => json_convert('Данная функция доступна только администратору'));

        // если такой таблицы нет или у нее не включен доступ по записям, то "Для данной таблицы эта функция невозможа"
        $cmd = $con->prepare("select id from iris_table where code=:code and is_access = '1'");
        $cmd->execute(array(":code" => strtolower($tableId)));
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
        if ($res[0][0] != '') {
            return array("success" => 0, "message" => json_convert('У данной таблицы не включен доступ по запиям'));
        }


        // проверим, что задан что то одно: userId или accessroleId
        $acr_flag = (strlen($access['accessroleId']) == 36);
        $usr_flag = (strlen($access['userId']) == 36);
        if (($acr_flag xor $usr_flag) == false)
            return array("success" => 0, "message" => json_convert('Необходимо указать или роль или пользователя'));

        if ($acr_flag) {
            $column_name = 'accessroleId';
            $value = $access['accessroleId'];
        } else {
            $column_name = 'contactid';
            $value = $access['userId'];
        }

        // удалим старые записи
        $del_sql = "delete from ".$tableId."_access where ".$column_name." =:column_id and recordid in (select * from iris_explode_str(',', :id_list))";
        $del_cmd = $con->prepare($del_sql);
        $del_cmd->execute(array(":column_id" => $value, ":id_list" => implode(',', $ids)));
        if ($del_cmd->errorCode() != '00000')
            return array("success" => 0, "message" => json_convert('<b>Внимание!</b> Возникла ошибка при удалении старых значений прав доступа'));

        if ( !(($access['mode'] == 'soft') and ($access['r'] == 0) and ($access['w'] == 0) and ($access['d'] == 0) and ($access['a'] == 0)) ) {
            // добавим новые записи
            $ins_sql  = "insert into ".$tableId."_access (id, recordid, ".$column_name.", r, w, d, a) ";
            $ins_sql .= "select iris_genguid(), iris_explode_str, :value, :r, :w, :d, :a from iris_explode_str(',', :id_list)";
            $ins_cmd = $con->prepare($ins_sql);
            $ins_cmd->execute(array(":value" => $value, ":r" => $access['r'], ":w" => $access['w'], ":d" => $access['d'], ":a" => $access['a'], ":id_list" => implode(',', $ids)));
            if ($ins_cmd->errorCode() != '00000')
                return array("success" => 0, "message" => json_convert('<b>Внимание!</b> Возникла ошибка при добавлении новых значений прав доступа'));
        }

        return array("success" => 1, "message" => json_convert('Доступ успешно изменен'));
    }
}
