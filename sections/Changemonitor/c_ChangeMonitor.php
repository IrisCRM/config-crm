<?php

namespace Iris\Config\CRM\sections\ChangeMonitor;

use Config;
use Iris\Iris;
use PDO;

/**
 *  Changemonitor карточка
 */
class c_ChangeMonitor extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
        ));
    }

    public function getCardInfo($params) {
        $con = $this->connection;
        $sql = $con->prepare("select T1.dictionary as dictionary, T1.detail as detail, T2.code as section from iris_table T1 left join iris_section T2 on T1.SectionID = T2.ID where T1.id = :id");
        $sql->execute(array(":id" => $params['tableId']));
        return current($sql->fetchAll(PDO::FETCH_ASSOC));
    }
}
