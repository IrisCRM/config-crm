<?php
//********************************************************************
// Раздел "Мои счета". таблица записей
//********************************************************************

namespace Iris\Config\CRM\sections\Myinvoice;

use Config;
use Iris\Iris;
use PDO;

class g_Myinvoice extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php'
        ));
    }

    public function checkBalance($params) {
        $invoiceId = $params['invoiceId'];
        $con = $this->connection;

        $cmd = $con->prepare("select T0.id as id, T0.number as number, T0.Amount as amount, T0.contactid as contactid, T0.ownerid as ownerid, T0.projectid as projectid, T1.code as code from iris_invoice T0 left join iris_invoicestate T1 on T0.invoicestateid = T1.id where T0.id=:id");
        $cmd->execute(array(":id" => $invoiceId));
        $inv_res = $cmd->fetchAll(PDO::FETCH_ASSOC);

        if ($inv_res[0]['code'] == 'Payed') {
            $result['errm'] = json_convert('Данный счет уже оплачен');
            return $result;
        }
        $user_id = GetUserID($con);
        if ($inv_res[0]['contactid'] != $user_id) {
            return array("errm" => json_convert('Данный счет может оплатить только тот клиент, который указан в счете'));
        }

        $contact_res = $con->query("select balance from iris_contact where id='".$user_id."'")->fetchAll();
        $balance = $contact_res[0][0];
        if ($balance < $inv_res[0]['amount']) {
            $result['isOk'] = false;
        } else {
            $result['isOk'] = true;
        }

        $result['amount'] = (float) $inv_res[0]['amount'];
        $result['balance'] = $balance;
        $result['invoiceid'] = $inv_res[0]['id'];

        // требуются при оплате счета
        $result['number'] = $inv_res[0]['number'];
        $result['contactid'] = $inv_res[0]['contactid'];
        $result['ownerid'] = $inv_res[0]['ownerid'];
        $result['projectid'] = $inv_res[0]['projectid'];

        return $result;
    }

    public function payInvoice($params) {
        $invoiceId = $params['invoiceId'];
        $check = $this->checkBalance(array("invoiceId" => $invoiceId));

        if ($check['errm'] != '') {
            return array('message' => $check['errm']);
        }

        if ($check['isOk'] == 0) {
            return array('message' => json_convert('Средств баланса не достаточно для оплаты счета'));
        }

        $con = $this->connection;

        // уменьшим баланс клиента на сумму счета
        $cmd = $con->prepare("update iris_contact set balance = balance - :amount where id=:id");
        $cmd->execute(array(":id" => GetUserID($con), ":amount" => $check['amount']));
        if ($cmd->errorCode() != '00000')
            return array('message' => json_convert('Не удалось изменить баланс'));

        // создадим платеж
        $payment_id = create_guid();
        $cmd = $con->prepare("insert into iris_payment (id, Number, Name, PaymentTypeID, PaymentStateID, ContactID, OwnerID, PaymentDate, CurrencyID, Amount, InvoiceID, ProjectID, iscash) 
	values (:id, :Number, :Name, (select id from iris_PaymentType where code='In'), (select id from iris_PaymentState where code='Completed'), :ContactID, :OwnerID, now(), (select id from iris_Currency where code='RUB'), :Amount, :InvoiceID, :ProjectID, 1)");
        $cmd->bindParam(":id", $payment_id);
        $number = GenerateNewNumber('PaymentNumber', 'PaymentNumberDate', $con);
        $cmd->bindParam(":Number", $number);
        $name = $number.' - оплата счета '.$check['number'];
        $cmd->bindParam(":Name", $name);
        $cmd->bindParam(":ContactID", $check['contactid']);
        $cmd->bindParam(":OwnerID", $check['ownerid']);
        $cmd->bindParam(":Amount", $check['amount']);
        $cmd->bindParam(":InvoiceID", $invoiceId);
        $cmd->bindParam(":ProjectID", $check['projectid']);
        $cmd->execute();
        if ($cmd->errorCode() == '00000') {
            UpdateNumber('Payment', stripslashes($invoiceId), 'PaymentNumber', 'PaymentNumberDate');
        }
        else {
            return array('message' => json_convert('Внимание! Баланс изменен, но платеж не был создан. Обратитесь к своему менеджеру'));
        }

        // добавим права на созданый платеж
        $role_res = array_pop($con->query("select id from iris_accessrole where code='leader'")->fetchAll(PDO::FETCH_ASSOC));
        $permissions[] = array('userid' => $check['contactid'], 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
        $permissions[] = array('userid' => $check['ownerid'], 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
        $permissions[] = array('userid' => '', 'roleid' => $role_res['id'], 'r' => 1, 'w' => 1, 'd' => 1, 'a' => 1);
        $res = ChangeRecordPermissions('iris_payment', $payment_id, $permissions);

        return array('message' => json_convert('Счет был успешно оплачен'));
    }
}
