<?php
//********************************************************************
// Раздел E-mail. серверная логика вкладки "Файлы"
//********************************************************************

namespace Iris\Config\CRM\sections\Email;

use Config;
use Iris\Iris;
use PDO;

class dg_Email_File extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    public function renderSelectFileDialog($params) {
        // Описание колонок, которые будут отображаться в таблице
        $columns = array(
            'name' => array(
                'caption' => 'Файл',
                'type' => 'string',
                'width' => '25%',
            ),
            'type' => array(
                'caption' => 'Тип',
                'type' => 'string',
                'width' => '15%',
            ),
            'state' => array(
                'caption' => 'Состояние',
                'type' => 'string',
                'width' => '15%',
            ),
            'created' => array(
                'caption' => 'Создан',
                'type' => 'datetime',
                'width' => '20%',
                'sort' => 'asc',
            ),
        );
        // Выбираем данные для отображеия в таблице
        $sql = $this->_DB->considerAccess('{file}',
                "select t0.id as id, 
                t0.file_filename as name,
                t1.name as type,
                t2.name as state,
                _iris_datetime_to_string[t0.createdate] as created
                from " . $this->_DB->tableName('{file}') . " t0
                left join " . $this->_DB->tableName('{filetype}') . " t1 
                  on t1.id = t0.filetypeid
                left join " . $this->_DB->tableName('{filestate}') . " t2 
                  on t2.id = t0.filestateid",
                "where (T0.emailid<>:emailid or T0.emailid is null) 
                  and T0.id not in (select EF.fileid from iris_email_file EF where EF.emailid = :emailid)")
            . "order by t0.file_filename";
        $filter = array(
            ':emailid' => $params['emailId'],
        );
        $values = $this->_DB->exec($sql, $filter);

        // Выбранная по умолчанию запись - либо следующая либо текущая цель
        $parameters = array(
            'grid_id' => 'custom_grid_'. md5(time() . rand(0, 10000)),
        );

        // Подготовка данных для представления таблицы
        $data = $this->getCustomGrid($columns, $values, $parameters);

        // Построение представления таблицы
        $result = array(
            'Card' => $this->renderView('grid', $data),
            'GridId' => $parameters['grid_id'],
        );
        return $result;
    }

    public function attachFile($params) {
        $con = $this->connection;
        $emailId = $params['emailId'];
        $fileId = $params['fileId'];

        // проверим, не прикреплен ли уже файл
        $cmd = $con->prepare("select id from iris_email_file where fileid=:fileid and emailid = :emailid");
        $cmd->execute(array(":fileid" => $fileId, ":emailid" => $emailId));
        $file_exists = current($cmd->fetchAll(PDO::FETCH_ASSOC));
        if ($file_exists['id'] != '') {
            return array('isSuccess' => false, 'message' => json_convert('Этот файл уже прикреплен'));
        }

        // прикрепим файл
        $ins_cmd = $con->prepare("insert into iris_email_file (id, fileid, emailid) values (iris_genguid(), :fileid, :emailid)");
        $ins_cmd->execute(array(":fileid" => $fileId, ":emailid" => $emailId));
        if ($ins_cmd->errorCode() != '00000') {
            return array('isSuccess' => false, 'message' => json_convert('Не удалось прикрепить файл'));
        }

        return array('isSuccess' => true);
    }

    public function deattachFile($params) {
        $con = $this->connection;

        // открепим файл
        $cmd = $con->prepare("delete from iris_email_file where fileid = :fileid and emailid = :emailid");
        $cmd->execute(array(":fileid" => $params['fileId'], ":emailid" => $params['emailId']));
        if ($cmd->errorCode() != '00000') {
            return array('isSuccess' => false, 'message' => json_convert('Не удалось открепить файл'));
        }

        return array('isSuccess' => true);
    }
}