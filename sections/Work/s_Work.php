<?php

namespace Iris\Config\CRM\sections\Work;

use Config;
use Iris\Iris;
use PDO;

/**
 * Серверная логика карточки работы
 */
class s_Work extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
        $this->_section_name = substr(__CLASS__, 2);
    }

    public function onPrepare($params)
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $result = array();

        // Значения справочников
        $result = GetDictionaryValues(
            array (
                array ('Dict' => 'WorkType', 'Code' => 'Work'),
                array ('Dict' => 'WorkState', 'Code' => 'Plan')
            ),
            $this->connection, $result);

        //Ответственный
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $this->connection, $result);

        return $result;
    }

    public function getParentInfo($params) {
        $parentId = $params['parentId'];
        $projectId = $params['projectId'];
        $con = $this->connection;
        $work = array();

        if (empty($parentId) and !empty($projectId)) {
            $cmd = $con->prepare("select count(id) as \"workCount\" from iris_work where projectid = :projectid and parentworkid is null");
            $cmd->execute(array(":projectid" => $projectId));
            $count = current($cmd->fetchAll(PDO::FETCH_ASSOC));
            $workCount = $count['workCount'];
            return array("workCount" => !empty($workCount) ? $workCount : 0);
        }

        if (!empty($parentId)) {
            $cmd = $con->prepare("SELECT id, number FROM iris_work WHERE id = :id");
            $cmd->execute(array(":id" => $parentId));
            $work = current($cmd->fetchAll(PDO::FETCH_ASSOC));
        }

        $cmd = $con->prepare("select count(id) as \"workCount\" from iris_work where parentworkid = :id");
        $cmd->execute(array(":id" => $parentId));
        $count = current($cmd->fetchAll(PDO::FETCH_ASSOC));
        $workCount = $count['workCount'];

        return array("params" => $params, "number" => $work["number"], "workCount" => !empty($workCount) ? $workCount : 0);
    }

    public function getWorkDiagramData($params) {
        $projectId = $params['projectId'];
        $con = $this->connection;

        // считаем даты заказа
        $sql = "select to_char(planstartdate, 'MM/DD/YYYY') as planstartdate, planfinishdate - planstartdate as days from iris_project where id=:id";
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":id" => $projectId));
        $project = current($cmd->fetchAll(PDO::FETCH_ASSOC));

        $works = $this->getWorksTree($con, $projectId);

        return array("works" => json_convert_array($works), "project" => $project);
    }

    public function getWorksTree($con, $projectId, $parentWorkId = null, $command = null) {
        if ($command == null) {
            $sql  = "select T0.id as id, T0.number, T0.name, T0.planstartdate, T0.planfinishdate, T0.planstartdate - T1.planstartdate as startday, T0.planfinishdate + 1 - T0.planstartdate as days ";
            $sql .= "from iris_work T0 left join iris_project T1 on T0.projectid = T1.id ";
            $sql .= "where T0.projectid = :projectid ";
            $sql .= " and ((T0.parentworkid = :parentworkid) or (T0.parentworkid is null and :parentworkid is NULL))";
            $sql .= "order by T0.planstartdate, T0.number";
            $command = $con->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => true));
        }

        $command->bindParam(":projectid", $projectId);
        if ($parentWorkId == null) {
            $command->bindParam(":parentworkid", $parentWorkId, PDO::PARAM_NULL);
        } else {
            $command->bindParam(":parentworkid", $parentWorkId);
        }
        $command->execute();
        $works = $command->fetchAll(PDO::FETCH_ASSOC);

        $result = array();
        if (count($works) < 1) {
            return null;
        }

        foreach ($works as $work) {
            $childs = $this->getWorksTree($con, $projectId, $work['id'], $command);

            $result[] = $work;
            foreach ($childs as $child) {
                $result[] = $child;
            }
        }
        return $result;
    }
}
