<?php

namespace Iris\Config\CRM\sections\Myfile;

use Config;
use Iris\Iris;
use PDO;

/**
 * Серверная логика карточки файла
 */
class s_Myfile extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
            'common/Lib/project.php',
        ));
        $this->_section_name = substr(__CLASS__, 2);
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return $result;
        }

        $result = $this->prepareDetail($params);

        //Значения справочников
        $result = GetDictionaryValues(
            array(
                array('Dict' => 'FileState', 'Code' => 'Active'),
            ), $this->connection, $result);

        //Ответственный    
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);

        //Дата
        $Date = GetCurrentDBDateTime($this->connection);
        $result = FieldValueFormat('Date', $Date, null, $result);


        $qry = $this->connection->query(
                "select id, name from iris_account " 
                . "where id = (select accountid from iris_contact " 
                . "where id = '".GetUserID($con)."')")->fetchAll();
        $result = FieldValueFormat('AccountID', $qry[0][0], 
                json_convert($qry[0][1]), $result);
        
        if (empty($params['detail_name']) 
                || $params['detail_name'] != 'd_Project_File') {
            $result = GetRecentProject(GetUserID($this->connection), 
                    $this->connection, $result);
        }


        return $result;
    }

    public function onAfterPost($p_table, $p_id, $old_data, $new_data)
    {
        $res = GetUserAccessInfo($this->connection);

        // Если роль текущего пользователя - Клиент
        if ($res['userrolecode'] == 'Client') {
            $user_id = GetUserID($con);

            // Ответсвенный по заказу
            $sql_arr[] = "select ownerid from iris_project " 
                    . "where id = (select projectid from iris_file " 
                    . "where id = '" . $p_id . "')";

            // Ответственный
            $sql_arr[] = "select ownerid from iris_contact " 
                    . "where id = '" . $user_id . "'";

            // Ответсвенный ответсвенного
            $sql_arr[] = "select ownerid from iris_contact " 
                    . "where id = (select ownerid from iris_contact " 
                    . "where id = '" . $user_id . "')";

            foreach ($sql_arr as $sql_l) {
                $res = $this->connection->query($sql_l)->fetchAll(PDO::FETCH_ASSOC);
                if ($res[0]['ownerid'] != '') {
                    $permissions[] = array(
                        'userid' => $res[0]['ownerid'], 
                        'roleid' => '', 
                        'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0
                    );
                }
            }

            // Клиент, создавший файл
            $permissions[] = array(
                'userid' => $user_id, 
                'roleid' => '', 
                'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0
            );

            // Применим права
            $res = ChangeRecordPermissions('iris_file', $p_id, $permissions);
        }

    }

}
