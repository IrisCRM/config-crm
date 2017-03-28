<?php
//********************************************************************
// Раздел "Мой кошелек (для клиентов)". нестандартный раздел
//********************************************************************

namespace Iris\Config\CRM\sections\Mycash;

use Config;
use Iris\Iris;
use Language;
use PDO;

class u_Mycash extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
        ));
    }

    public function getSectionHTML($params) {
        $instanceName = $params['instanceName'];
        $con = $this->connection;
        $T = Language::getInstance();

        $user_id = GetUserID($con);
        $res = $con->query("select balance from iris_contact where id='".$user_id."'")->fetchAll();
        $balance = $res[0][0];

        if ($balance <= 0) {
            $class_add = ' mycash_balancezero';
        }

        $result  = '<div class="mycash_div">';
        $result .= '<span class="mycash_balancecaption">Ваш баланс: <span id="mycash_balancevalue" class="mycash_balancevalue '.$class_add.'">'.$balance.'</span> р.</span>';
        $result .= '<input type="button" class="button button_refresh mycash_refresh" onclick="' . $instanceName . '.refreshBalance(this);"/>';
        $result .= '<input type="button" class="button mycash_balancebutton" style="margin-left: 10px" value="'.$T->t('Пополнить').'" onclick="' . $instanceName . '.increaseBalance()"/>';
        $result .= '</div>';


        list ($UserID, $UserName) = GetShortUserInfo(GetUserName(), $con);
        $select_sql = "select t0.ID as id, t0.Name as name, t0.Number as number, ";
        $select_sql .= "t0.amount as amount, t0.paymentdate as paymentdate, ";
        $select_sql .= "t0.invoiceid as invoiceid, t0.iscash as iscash, ";
        $select_sql .= "t1.code as statecode, t2.code as typecode ";
        $select_sql .= "from iris_Payment t0 ";
        $select_sql .= "left join iris_PaymentState t1 on t1.ID=t0.PaymentStateID ";
        $select_sql .= "left join iris_PaymentType t2 on t2.ID=t0.PaymentTypeID ";
        $select_sql .= "where t0.contactid=:p_contactid ";
        $select_sql .= "order by t0.number desc ";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_contactid', $UserID);
        $statement->execute();
        $res = $statement->fetchAll();


        $result .= '<h3>'.$T->t('История операций').'</h3>';

        $result .= '<table><tbody>';
        $result .= '<tr style="border-bottom: 1px solid #d0d0d0">';
        $result .= '<th>';
        $result .= 'Дата';
        $result .= '</th>';
        $result .= '<th>';
        $result .= 'Платеж';
        $result .= '</th>';
        $result .= '<th>';
        $result .= 'Описание';
        $result .= '</th>';
        $result .= '<th>';
        $result .= 'Сумма';
        $result .= '</th>';
        $result .= '<th>';
        $result .= 'Баланс';
        $result .= '</th>';
        $result .= '</tr>';

        foreach ($res as $row) {
            $amount = $row['amount'];
            $descr = '';
            $balance = '';

            //Для прихода
            if ($row['typecode']=='In') {
                if (IsEmptyValue($row['invoiceid']) && ($row['iscash']!=1)) {
                    $balance = '+'.$amount;
                    $descr = 'Пополнение баланса';
                }
                else
                    if (IsEmptyValue($row['invoiceid']) && ($row['iscash']==1)) {
                        $balance = '-'.$amount;
                        $descr = 'Вычет';
                    }
                    else
                        if (!IsEmptyValue($row['invoiceid']) && ($row['iscash']!=1)) {
                            $balance = '';
                            $descr = 'Оплата счета минуя личный кабинет';
                        }
                        else
                            if (!IsEmptyValue($row['invoiceid']) && ($row['iscash']==1)) {
                                $balance = '-'.$amount;
                                $descr = 'Оплата счета через личный кабинет';
                            }
            }

            //Для расхода
            else {
                if (IsEmptyValue($row['invoiceid']) && ($row['iscash']!=1)) {
                    $balance = '';
                    $descr = 'Оплата Вам';
                }
                else
                    if (IsEmptyValue($row['invoiceid']) && ($row['iscash']==1)) {
                        $balance = '+'.$amount;
                        $descr = 'Начисление на баланс';
                    }
                    else
                        if (!IsEmptyValue($row['invoiceid']) && ($row['iscash']!=1)) {
                            $balance = '';
                            $descr = 'Оплата выставленного от Вас счета';
                        }
                        else
                            if (!IsEmptyValue($row['invoiceid']) && ($row['iscash']==1)) {
                                $balance = '+'.$amount;
                                $descr = 'Бонусный платеж';
                            }
            }



            $result .= '<tr style="border-bottom: 1px solid #d0d0d0">';
            $result .= '<td>';
            $result .= date('d.m.Y', strtotime($row['paymentdate']));
            $result .= '</td>';
            $result .= '<td>';
            $result .= $row['name'];
            $result .= '</td>';
            $result .= '<td>';
            $result .= $descr;
            $result .= '</td>';
            $result .= '<td align="right">';
            $result .= $row['amount'];
            $result .= '</td>';
            $result .= '<td align="right">';
            $result .= $balance;
            $result .= '</td>';
            $result .= '</tr>';
        }

        $result .= '</tbody></table>';

        return array("html" => $result);
    }

    public function refreshValue() {
        $con = $this->connection;
        $cmd = $con->prepare("select balance from iris_contact where id=:id");
        $cmd->execute(array(":id" => GetUserID($con)));
        $res = $cmd->fetchAll();
        return array("balance" => $res[0][0]);
    }
}
