<?php

namespace Iris\Config\CRM\sections\Invoice;

use Config;
use Iris\Iris;
use PDO;

/**
 * Таблица счетов
 */
class g_Invoice extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    /**
     * Создание накладной из счёта
     */
    public function createFactInvoice($params)
    {
        $p_id = $params['id'];
        $con = $this->connection;

        $invoice_info = GetFormatedFieldValuesByFieldValue('Invoice', 'ID', $p_id, array(
            'AccountID', 'ContactID', 'CurrencyID', 'ProjectID', 'PactID'), $con);
        
        $className = $this->_Loader->getActualClassName('sections\\FactInvoice\\s_FactInvoice');
        $FactInvoice = new $className($this->_Loader);
        $invoice_values = $FactInvoice->onPrepare(array('mode' => 'insert'), $invoice_info);
        //$invoice_values = FactInvoice_GetDefaultValues($invoice_info);
        $factinvoice_id = create_guid();
        $invoice_values = FieldValueFormat('ID', $factinvoice_id, null, $invoice_values);
        $invoice_values = FieldValueFormat('InvoiceID', $p_id, null, $invoice_values);
        //print_r($invoice_values);

        // miv 07.12.2011: в название акта добавляется название компании
        $AccountID = GetArrayValueByName($invoice_info['FieldValues'], 'AccountID');
        $account_result = GetValuesFromTable('Account', $AccountID, array('Name'), $con);
        foreach ($invoice_values['FieldValues'] as $key => $value) {
            if ($value['Name'] == 'Name') {
                $invoice_values['FieldValues'][$key]['Value'] = GetArrayValueByName($invoice_values['FieldValues'], 'Number').' - '.UtfDecode(GetArrayValueByName($account_result['FieldValues'], 'Name'));
                break;
            }
        }

        //Вставка счета-фактуры
        InsertRecord('FactInvoice', $invoice_values['FieldValues'], $con);
        $number = UpdateNumber('FactInvoice', $factinvoice_id, 'FactInvoiceNumber', 'FactInvoiceNumberDate');

        //Вставим продукты в счет-фактуру (цикл)
        $select_sql = "select pp.ID as id, pp.ProductID as productid, pp.Count as count, pp.UnitID as unitid, ";
        $select_sql .= "pp.Price as price, pp.Amount as amount, pp.Number as number, pp.description as description ";
        $select_sql .= "from iris_Invoice_Product pp where pp.InvoiceID=:p_invoice_id order by pp.Number";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_invoice_id', $p_id);
        $statement->execute();
        $res = $statement->fetchAll();

        $i=1;
        $Amount = 0;
        foreach ($res as $row) {
            //Посчитаем уже существующие в счетах-фактурах продукты
            $have_sql = "select SUM(m.Count) ";
            $have_sql .= "from iris_FactInvoice_Product m ";
            $have_sql .= "inner join iris_FactInvoice i on m.FactInvoiceID=i.ID ";
            $have_sql .= "left join iris_FactInvoiceState ist on ist.ID=i.FactInvoiceStateID ";
            $have_sql .= "left join iris_FactInvoiceType it on it.ID=i.FactInvoiceTypeID ";
            $have_sql .= "where i.InvoiceID=:p_invoice_id ";
            $have_sql .= "and m.ProductID=:p_product_id ";
            $have_sql .= "and i.ID<>:p_factinvoice_id ";
            $have_sql .= "and it.Code='Out' ";
            $have_sql .= "and ist.Code in ('Plan', 'Sended') ";
            $have_sql .= "and ((m.UnitID in (select UnitID from iris_Invoice_Product where ID=:p_ppid)) ";
            $have_sql .= "or (m.UnitID is null and (select UnitID from iris_Invoice_Product where ID=:p_ppid1) is null))";
            $have_statement = $con->prepare($have_sql);
            $have_statement->bindParam(':p_invoice_id', $p_id);
            $have_statement->bindParam(':p_factinvoice_id', $factinvoice_id);
            $have_statement->bindParam(':p_product_id', $row['productid']);
            $have_statement->bindParam(':p_ppid', $row['id']);
            $have_statement->bindParam(':p_ppid1', $row['id']);
            $have_statement->execute();
            $have_statement->bindColumn(1, $have_count);
            $have_res = $have_statement->fetch();        
            //echo $have_sql.$p_id.'---'.$have_count.'---';

            if ($row['count']-$have_count > 0) {
                $price = $row['price'];
                $count = $row['count']-$have_count;
                $product_values = null;
                $product_values = FieldValueFormat('FactInvoiceID', $factinvoice_id, null);
                $product_values = FieldValueFormat('ID', create_guid(), null, $product_values);
                $product_values = FieldValueFormat('ProductID', $row['productid'], null, $product_values);
                $product_values = FieldValueFormat('Count', $count, null, $product_values);
                $product_values = FieldValueFormat('UnitID', $row['unitid'], null, $product_values);
                $product_values = FieldValueFormat('Price', $price, null, $product_values);
                $product_values = FieldValueFormat('Amount', $price*$count, null, $product_values);
                $product_values = FieldValueFormat('Number', $i, null, $product_values);
                $product_values = FieldValueFormat('Description', $row['description'], null, $product_values);
                InsertRecord('FactInvoice_Product', $product_values['FieldValues'], $con, true);
                $i++;
                $Amount += $row['amount'];
            }
        }

        $invoice_update = FieldValueFormat('Amount', $Amount);
        UpdateRecord('FactInvoice', $invoice_update['FieldValues'], $factinvoice_id, $con);

        //miv 29.03.2010 Проставим права доступа к счету-фактуре (как у родительской записи)
        GetRecordPermissions('iris_Invoice', $p_id, $permissions, $con);
        ChangeRecordPermissions('iris_FactInvoice', $factinvoice_id, $permissions, $con);

        return $number;
    }

    /**
     * Создание платежа из счёта
     */
    public function createPayment($params)
    {
        $p_id = $params['id'];
        $con = $this->connection;

        $invoice_info = GetFormatedFieldValuesByFieldValue('Invoice', 'ID', $p_id, array(
            'AccountID', 'ContactID', 'CurrencyID', 'ProjectID', 'PactID', 'Amount'), $con);
        //Переименуем Amount -> PlanAmount
        SetArrayValueByParameter($invoice_info['FieldValues'], 'Name', 'Amount', 'Name', 'PlanAmount');

        $className = $this->_Loader->getActualClassName('sections\\Payment\\s_Payment');
        $Payment = new $className($this->_Loader);
        $invoice_values = $Payment->onPrepare(array('mode' => 'insert'), $invoice_info);
        $invoice_id = create_guid();
        $invoice_values = FieldValueFormat('ID', $invoice_id, null, $invoice_values);
        $invoice_values = FieldValueFormat('InvoiceID', $p_id, null, $invoice_values);

        // miv 07.12.2011: в название акта добавляется название компании
        $AccountID = GetArrayValueByName($invoice_values['FieldValues'], 'AccountID');
        $account_result = GetValuesFromTable('Account', $AccountID, array('Name'), $con);
        foreach ($invoice_values['FieldValues'] as $key => $value) {
            if ($value['Name'] == 'Name') {
                $invoice_values['FieldValues'][$key]['Value'] = GetArrayValueByName($invoice_values['FieldValues'], 'Number').' - '.UtfDecode(GetArrayValueByName($account_result['FieldValues'], 'Name'));
                break;
            }
        }

        //Вставка записи
        InsertRecord('Payment', $invoice_values['FieldValues'], $con);
        $number = UpdateNumber('Payment', $invoice_id, 'PaymentNumber', 'PaymentNumberDate');

        //miv 29.03.2010 Проставим права доступа к платежу (как у родительской записи)
        GetRecordPermissions('iris_Invoice', $p_id, $permissions, $con);
        ChangeRecordPermissions('iris_Payment', $invoice_id, $permissions, $con);

        // miv 06.09.2011: добавим клиента в доступ на чтение, если это необходимо
        $isclientaccess = GetSystemVariableValue('PaymentClientAccess', $con);
        if ($isclientaccess == 1) {
            $ContactID = GetArrayValueByName($invoice_info['FieldValues'], 'ContactID');
            $perm[] = array('userid' => $ContactID, 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
            ChangeRecordPermissions('iris_payment', $invoice_id, $perm, $con);
        }

        return $number;
    }

    /**
     * Создание акта из счёта
     */
    function createAct($params) {
        $p_invoice_id = $params['id'];
        $con = $this->connection;

        $act_info = GetFormatedFieldValuesByFieldValue('Invoice', 'ID', $p_invoice_id, array(
            'AccountID', 'ContactID', 'Account_PropertyID', 'CurrencyID', 'ProjectID', 'PactID', 'Amount'), $con);

        $className = $this->_Loader->getActualClassName('sections\\Document\\s_Document');
        $Document = new $className($this->_Loader);
        $act_values = $Document->onPrepare(array('mode' => 'insert'), $act_info);
        //$act_values = Document_GetDefaultValues($act_info);
        $act_id = create_guid();
        $act_values = FieldValueFormat('ID', $act_id, null, $act_values);

        $act_values = FieldValueFormat('DocumentTypeID', GetFieldValueByFieldValue('documenttype', 'code', 'Act', 'id', $con), null, $act_values);
        $act_values = FieldValueFormat('DocumentStateID', GetFieldValueByFieldValue('documentstate', 'name', 'Составляется', 'id', $con), null, $act_values);
        
        // miv 07.12.2011: заполняются реквезиты клиента из компании заказа
        $AccountID = GetArrayValueByName($act_info['FieldValues'], 'AccountID');
        $ap_sql  = "select ap.ID as id, ap.Name as name from iris_Account_Property ap ";
        $ap_sql .= "where ap.AccountID='".$AccountID."' and ap.IsMain=1";
        $AP = current($con->query($ap_sql)->fetchAll(PDO::FETCH_ASSOC));
        if ($AP['id'] != '') {
            $act_values = FieldValueFormat('Account_PropertyID', $AP['id'], $AP['name'], $act_values);
        }

        // miv 07.12.2011: в название акта добавляется название компании
        $account_result = GetValuesFromTable('Account', $AccountID, array('Name'), $con);
        foreach ($act_values['FieldValues'] as $key => $value) {
            if ($value['Name'] == 'Name') {
                $act_values['FieldValues'][$key]['Value'] = GetArrayValueByName($act_values['FieldValues'], 'Number').' - '.UtfDecode(GetArrayValueByName($account_result['FieldValues'], 'Name'));
                break;
            }
        }
        
        //Вставка акта
        InsertRecord('Document', $act_values['FieldValues'], $con);
        $number = UpdateNumber('Document', $act_id, 'DocumentNumber', 'DocumentNumberDate');

        //Проставим права доступа к платежу (как у родительской записи)
        GetRecordPermissions('iris_Invoice', $p_invoice_id, $permissions, $con);
        ChangeRecordPermissions('iris_Document', $act_id, $permissions, $con);

        // скопируем продукты из счета в документ
        $ins_sql  = "insert into iris_document_product (id, documentid, number, productid, unitid, price, count, amount, url, description) ";
        $ins_sql .= "(select iris_genguid(), :documentid, number, productid, unitid, price, count, amount, url, description from iris_invoice_product where invoiceid = :invoiceid)";
        $cmd = $con->prepare($ins_sql);
        $cmd->execute(array(":documentid" => $act_id, ":invoiceid" => $p_invoice_id));
        if ($cmd->errorCode() == '00000')
            return $number;

        return '';
    }

}
