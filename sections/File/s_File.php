<?php

namespace Iris\Config\CRM\sections\File;

use Config;
use Iris\Iris;
use PDO;

/**
 * Серверная логика карточки файла
 */
class s_File extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $result = $this->prepareDetail($params);

        // Если добавляем файл во вкладку email, то заполним дополительные поля
        if (!empty($params['detail_name']) 
                && $params['detail_name'] == 'd_Email_File') {
            $sql  = "select T0.id as id, subject, " 
                    . "T0.accountid as accountid, "
                    . "T0.contactid as contactid, "
                    . "T1.name as accountcap, "
                    . "T2.name as contactcap "
                    . "from iris_email T0 "
                    . "left join iris_account T1 on T0.accountid = T1.id "
                    . "left join iris_contact T2 on T0.contactid = T2.id "
                    . "where T0.id = :id";
            $cmd = $this->connection->prepare($sql);
            $cmd->execute(array(
                ":id" => $params['detail_column_value'],
            ));
            $email = current($cmd->fetchAll(PDO::FETCH_ASSOC));

            $result = FieldValueFormat('EmailID', $email['id'], 
                    $email['subject'], $result);
            $result = FieldValueFormat('AccountID', $email['accountid'], 
                    $email['accountcap'], $result);
            $result = FieldValueFormat('ContactID', $email['contactid'], 
                    $email['contactcap'], $result);
        }

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

        return $result;
    }

    /**
     * Этот обработчик не используется. Он есть в Myfile
     */
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

    public function onFileUpload($params)
    {
        // Сохраняем файл на диске
        $res = SaveFileToPath();
        $params2 = $params;
        $params2['mode'] = 'insert';
        $params2['with_master_field'] = true;
        if ($res) {
            // Добавляем файл в базу
            foreach ($res as &$file) {
                $data = $this->onPrepare($params2);
                $this->mergeFields($data, $this->formatField(
                        'file_file', $file['sysname']));
                $this->mergeFields($data, $this->formatField(
                        'file_filename', $file['name']));
                $record = $this->saveRecord($data);
            }
        }
        $res['file']['id'] = $record[0]['record_id'];
        return $res;
    }

}
