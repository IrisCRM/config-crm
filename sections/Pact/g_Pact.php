<?php

namespace Iris\Config\CRM\sections\Pact;

use Config;
use Iris\Iris;
use PDO;

/**
 * Таблица договора
 */
class g_Pact extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    /**
     * Создание счета из договора
     */
    public function createInvoice($params)
    {
        $p_id = $params['id'];
        $con = $this->connection;

		$pact_info = GetFormatedFieldValuesByFieldValue('Pact', 'ID', $p_id, array(
			'AccountID', 'ContactID', 'CurrencyID', 'ProjectID', 'OfferID', 'Account_PropertyID', 'Your_PropertyID'), $con);

        $className = $this->_Loader->getActualClassName('sections\\Invoice\\s_Invoice');
        $Invoice = new $className($this->_Loader);
        $invoice_values = $Invoice->onPrepare(array('mode' => 'insert'), $pact_info);
		//$invoice_values = Invoice_GetDefaultValues($pact_info);
		$invoice_id = create_guid();
		$invoice_values = FieldValueFormat('ID', $invoice_id, null, $invoice_values);
		$invoice_values = FieldValueFormat('PactID', $p_id, null, $invoice_values);
		//print_r($invoice_values);

		// miv 07.12.2011: заполняются реквезиты клиента из компании заказа
		$AccountID = GetArrayValueByName($pact_info['FieldValues'], 'AccountID');
		$ap_sql  = "select ap.ID as id, ap.Name as name from iris_Account_Property ap ";
		$ap_sql .= "where ap.AccountID='".$AccountID."' and ap.IsMain=1";
		$AP = current($con->query($ap_sql)->fetchAll(PDO::FETCH_ASSOC));
		if ($AP['id'] != '') {
			$act_values = FieldValueFormat('Account_PropertyID', $AP['id'], $AP['name'], $act_values);
		}

		// miv 07.12.2011: в название акта добавляется название компании
		$account_result = GetValuesFromTable('Account', $AccountID, array('Name'), $con);
		foreach ($invoice_values['FieldValues'] as $key => $value) {
			if ($value['Name'] == 'Name') {
				$invoice_values['FieldValues'][$key]['Value'] = GetArrayValueByName($invoice_values['FieldValues'], 'Number').' - '.UtfDecode(GetArrayValueByName($account_result['FieldValues'], 'Name'));
				break;
			}
		}

		//Вставка счета
		InsertRecord('Invoice', $invoice_values['FieldValues'], $con);
		$number = UpdateNumber('Invoice', $invoice_id, 'InvoiceNumber', 'InvoiceNumberDate');

		//Вставим продукты в счет (цикл)
		$select_sql = "select pp.ID as id, pp.ProductID as productid, pp.Count as count, pp.UnitID as unitid, ";
		$select_sql .= "pp.Price as price, pp.Amount as amount, pp.Number as number, pp.description as description ";
		$select_sql .= "from iris_Pact_Product pp where pp.PactID=:p_pact_id order by pp.Number";
		$statement = $con->prepare($select_sql);
		$statement->bindParam(':p_pact_id', $p_id);
		$statement->execute();
		$res = $statement->fetchAll();

		$i=1;
		$Amount = 0;
		foreach ($res as $row) {
			//Посчитаем уже существующие в счетах продукты
			$have_sql = "select SUM(m.Count) ";
			$have_sql .= "from iris_Invoice_Product m ";
			$have_sql .= "inner join iris_Invoice i on m.InvoiceID=i.ID ";
			$have_sql .= "left join iris_InvoiceState ist on ist.ID=i.InvoiceStateID ";
			$have_sql .= "left join iris_InvoiceType it on it.ID=i.InvoiceTypeID ";
			$have_sql .= "where i.PactID=:p_pact_id ";
			$have_sql .= "and m.ProductID=:p_product_id ";
			$have_sql .= "and i.ID<>:p_invoice_id ";
			$have_sql .= "and it.Code='In' ";
			$have_sql .= "and ist.Code in ('Plan', 'Submited', 'Payment', 'Payed', 'Part') ";
			$have_sql .= "and ((m.UnitID in (select UnitID from iris_Pact_Product where ID=:p_ppid))";
			$have_sql .= "or (m.UnitID is null and (select UnitID from iris_Pact_Product where ID=:p_ppid1) is null))";
			$have_statement = $con->prepare($have_sql);
			$have_statement->bindParam(':p_pact_id', $p_id);
			$have_statement->bindParam(':p_invoice_id', $invoice_id);
			$have_statement->bindParam(':p_product_id', $row['productid']);
			$have_statement->bindParam(':p_ppid', $row['id']);
			$have_statement->bindParam(':p_ppid1', $row['id']);
			$have_statement->execute();
			$have_statement->bindColumn(1, $have_count);
			$have_res = $have_statement->fetch();		
	//echo $have_sql.$p_id.'---'.$have_count.'---';

			//Если выставлено в счетах меньше, чем есть в договоре, то добавим в счет
			if ($row['count']-$have_count > 0) {
				$price = $row['price'];
				$count = $row['count']-$have_count;
				$product_values = null;
				$product_values = FieldValueFormat('InvoiceID', $invoice_id, null);
				$product_values = FieldValueFormat('ID', create_guid(), null, $product_values);
				$product_values = FieldValueFormat('ProductID', $row['productid'], null, $product_values);
				$product_values = FieldValueFormat('Count', $count, null, $product_values);
				$product_values = FieldValueFormat('UnitID', $row['unitid'], null, $product_values);
				$product_values = FieldValueFormat('Price', $price, null, $product_values);
				$product_values = FieldValueFormat('Amount', $price*$count, null, $product_values);
				$product_values = FieldValueFormat('Number', $i, null, $product_values);
				$product_values = FieldValueFormat('Description', $row['description'], null, $product_values);
				//Вставка продукта в счет
				InsertRecord('Invoice_Product', $product_values['FieldValues'], $con, true);
				$i++;
				$Amount += $price*$count;//$row['amount'];
			}
		}

		$invoice_update = FieldValueFormat('Amount', $Amount);
		UpdateRecord('Invoice', $invoice_update['FieldValues'], $invoice_id, $con);

		//miv 30.03.2010 Проставим права доступа к счету (как у родительской записи)
		GetRecordPermissions('iris_Pact', $p_id, $permissions, $con);
		ChangeRecordPermissions('iris_Invoice', $invoice_id, $permissions, $con);

		// miv 06.09.2011: добавим клиента в доступ на чтение, если это необходимо
		$isclientaccess = GetSystemVariableValue('InvoiceClientAccess', $con);
		if ($isclientaccess == 1) {
			$ContactID = GetArrayValueByName($pact_info['FieldValues'], 'ContactID');
			$perm[] = array('userid' => $ContactID, 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
			ChangeRecordPermissions('iris_invoice', $invoice_id, $perm, $con);
		}

		return $number;
	}

    /**
     * Создание акта из договора
     */
    public function createAct($params)
    {
        $p_pact_id = $params['id'];
        $con = $this->connection;

		$act_info = GetFormatedFieldValuesByFieldValue('Pact', 'ID', $p_pact_id, array(
			'AccountID', 'ContactID', 'Account_PropertyID', 'CurrencyID', 'ProjectID', 'Amount'), $con);

        $className = $this->_Loader->getActualClassName('sections\\Document\\s_Document');
        $Document = new $className($this->_Loader);
        $act_values = $Document->onPrepare(array('mode' => 'insert'), $act_info);
	    //$act_values = Document_GetDefaultValues($act_info);
		$act_id = create_guid();
		$act_values = FieldValueFormat('ID', $act_id, null, $act_values);

		$act_values = FieldValueFormat('PactID', $p_pact_id, null, $act_values);
		$act_values = FieldValueFormat('DocumentTypeID', GetFieldValueByFieldValue('documenttype', 'code', 'Act', 'id', $con), null, $act_values);
		$act_values = FieldValueFormat('DocumentStateID', GetFieldValueByFieldValue('documentstate', 'name', 'Составляется', 'id', $con), null, $act_values);

		// miv 07.12.2011: заполняются реквезиты клиента из компании
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
		GetRecordPermissions('iris_Pact', $p_pact_id, $permissions, $con);
		ChangeRecordPermissions('iris_Document', $act_id, $permissions, $con);

	    // скопируем продукты из договора в документ
	    $ins_sql  = "insert into iris_document_product (id, documentid, number, productid, unitid, price, count, amount, url, description) ";
	    $ins_sql .= "(select iris_genguid(), :documentid, number, productid, unitid, price, count, amount, url, description from iris_pact_product where pactid = :pactid)";
	    $cmd = $con->prepare($ins_sql);
	    $cmd->execute(array(":documentid" => $act_id, ":pactid" => $p_pact_id));
	    if ($cmd->errorCode() == '00000')
	        return $number;

	    return '';
	}
}
