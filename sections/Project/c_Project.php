<?php

namespace Iris\Config\CRM\sections\Project;

use Config;
use Iris\Iris;

/**
 * Карточка проекта
 */
class c_Project extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php'));
    }

    public function getEnabledFields($params)
    {
        $p_ProjectID = $params['id'];
            
        $l_PaymentCode_In = "In";
        $l_PaymentCode_Out = "Out";
        
        //Блокировать или не блокировать поля
        // - Планируемый доход. Если есть продукты, то блокировать
        $select_sql = "select count(*) from iris_Project_Product "
                . "where ProjectID = :p_ProjectID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(':p_ProjectID' => $p_ProjectID));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();
        $PlanIncome_enabled = $Number == 0 ? true : false;

        // - Доход. Если есть входящие платежи, то блокировать
        $select_sql = "select count(*) "
                . "from iris_Payment p, iris_PaymentType pt "
                . "where p.PaymentTypeID = pt.id "
                . "and pt.Code = :p_PaymentTypeCode "
                . "and ProjectID = :p_ProjectID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_ProjectID' => $p_ProjectID,
            ':p_PaymentTypeCode' => $l_PaymentCode_In,
        ));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();
        $Income_enabled = $Number == 0 ? true : false;

        // - Расходы. Если есть исходящие платежи, то блокировать
        $select_sql = "select count(*) "
                . "from iris_Payment p, iris_PaymentType pt "
                . "where p.PaymentTypeID = pt.id "
                . "and pt.Code = :p_PaymentTypeCode "
                . "and ProjectID = :p_ProjectID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_ProjectID' => $p_ProjectID,
            ':p_PaymentTypeCode' => $l_PaymentCode_Out,
        ));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();
        $Expense_enabled = $Number == 0 ? true : false;

        $l_result['ProjectEnabled']['PlanIncome'] = $PlanIncome_enabled;
        $l_result['ProjectEnabled']['Income'] = $Income_enabled;
        $l_result['ProjectEnabled']['Expense'] = $Expense_enabled;
        return $l_result;
    }
}
