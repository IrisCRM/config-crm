<?php

namespace Iris\Config\CRM\sections\Offer;

use Config;
use Iris\Iris;

/**
 * Таблица КП
 */
class g_Offer extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    /**
     * Создание счета из КП
     */
    public function createInvoice($params)
    {
        $p_id = $params['id'];
        $con = $this->connection;

        $offer_info = GetFormatedFieldValuesByFieldValue('Offer', 'ID', $p_id, array(
            'AccountID', 'ContactID', 'CurrencyID', 'ProjectID'), $con);

        $className = $this->_Loader->getActualClassName('sections\\Invoice\\s_Invoice');
        $Invoice = new $className($this->_Loader);
        $invoice_values = $Invoice->onPrepare(array('mode' => 'insert'), $offer_info);
//        $invoice_values = Invoice_GetDefaultValues($offer_info);
        $invoice_id = create_guid();
        $invoice_values = FieldValueFormat('ID', $invoice_id, null, $invoice_values);
        $invoice_values = FieldValueFormat('OfferID', $p_id, null, $invoice_values);
        //print_r($invoice_values);
        //Вставка счета
        InsertRecord('Invoice', $invoice_values['FieldValues'], $con);
        $number = UpdateNumber('Invoice', $invoice_id, 'InvoiceNumber', 'InvoiceNumberDate');


        //Вставим продукты в счет (цикл)
        $select_sql = "select pp.ID as id, pp.ProductID as productid, pp.Count as count, pp.UnitID as unitid, ";
        $select_sql .= "pp.Price as price, pp.Amount as amount, pp.Number as number, pp.description as description ";
        $select_sql .= "from iris_Offer_Product pp where pp.OfferID=:p_offer_id order by pp.Number";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_offer_id', $p_id);
        $statement->execute();
        $res = $statement->fetchAll();

        $i = 1;
        $Amount = 0;
        foreach ($res as $row) {
            //Посчитаем уже существующие в счетах продукты
            $have_sql = "select SUM(m.Count) ";
            $have_sql .= "from iris_Invoice_Product m ";
            $have_sql .= "inner join iris_Invoice i on m.InvoiceID=i.ID ";
            $have_sql .= "left join iris_InvoiceState ist on ist.ID=i.InvoiceStateID ";
            $have_sql .= "left join iris_InvoiceType it on it.ID=i.InvoiceTypeID ";
            $have_sql .= "where i.OfferID=:p_offer_id ";
            $have_sql .= "and m.ProductID=:p_product_id ";
            $have_sql .= "and i.ID<>:p_invoice_id ";
            $have_sql .= "and it.Code='In' ";
            $have_sql .= "and ist.Code in ('Plan', 'Submited', 'Payment', 'Payed', 'Part') ";
            $have_sql .= "and ((m.UnitID in (select UnitID from iris_Offer_Product where ID=:p_ppid))";
            $have_sql .= "or (m.UnitID is null and (select UnitID from iris_Offer_Product where ID=:p_ppid1) is null))";
            $have_statement = $con->prepare($have_sql);
            $have_statement->bindParam(':p_offer_id', $p_id);
            $have_statement->bindParam(':p_invoice_id', $invoice_id);
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
                $product_values = FieldValueFormat('InvoiceID', $invoice_id, null);
                $product_values = FieldValueFormat('ID', create_guid(), null, $product_values);
                $product_values = FieldValueFormat('ProductID', $row['productid'], null, $product_values);
                $product_values = FieldValueFormat('Count', $count, null, $product_values);
                $product_values = FieldValueFormat('UnitID', $row['unitid'], null, $product_values);
                $product_values = FieldValueFormat('Price', $price, null, $product_values);
                $product_values = FieldValueFormat('Amount', $price*$count, null, $product_values);
                $product_values = FieldValueFormat('Number', $i, null, $product_values);
                $product_values = FieldValueFormat('Description', $row['description'], null, $product_values);
                InsertRecord('Invoice_Product', $product_values['FieldValues'], $con, true);
                $i++;
                $Amount += $row['amount'];
            }
        }

        $invoice_update = FieldValueFormat('Amount', $Amount);
        UpdateRecord('Invoice', $invoice_update['FieldValues'], $invoice_id, $con);

        //miv 29.03.2010 Проставим права доступа к счету (как у родительской записи)
        //include_once GetPath().'/config/common/Lib/access.php';
        GetRecordPermissions('iris_Offer', $p_id, $permissions, $con);
        ChangeRecordPermissions('iris_Invoice', $invoice_id, $permissions, $con);
        
        return $number;
    }

    /**
     * Создание договора из КП
     */
    public function createPact($params)
    {
        $p_id = $params['id'];
        $con = $this->connection;
        
        $offer_info = GetFormatedFieldValuesByFieldValue('Offer', 'ID', $p_id, array(
            'AccountID', 'ContactID', 'CurrencyID', 'ProjectID'), $con);

        $className = $this->_Loader->getActualClassName('sections\\Pact\\s_Pact');
        $Pact = new $className($this->_Loader);
        $pact_values = $Pact->onPrepare(array('mode' => 'insert'), $offer_info);
//        $pact_values = Pact_GetDefaultValues($offer_info);
        $pact_id = create_guid();
        $pact_values = FieldValueFormat('ID', $pact_id, null, $pact_values);
        $pact_values = FieldValueFormat('OfferID', $p_id, null, $pact_values);
    //    print_r($pact_values);
        //Вставка записи
        InsertRecord('Pact', $pact_values['FieldValues'], $con);
        $number = UpdateNumber('Pact', $pact_id, 'PactNumber', 'PactNumberDate');


        //Вставим продукты (цикл)
        $select_sql = "select pp.ProductID as productid, pp.Count as count, pp.UnitID as unitid, ";
        $select_sql .= "pp.Price as price, pp.Amount as amount, pp.Number as number, pp.description as description ";
        $select_sql .= "from iris_Offer_Product pp where pp.OfferID=:p_offer_id order by pp.Number";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_offer_id', $p_id);
        $statement->execute();
        $res = $statement->fetchAll();

        $i = 1;
        $Amount = 0;
        foreach ($res as $row) {
            $product_values = null;
            $product_values = FieldValueFormat('PactID', $pact_id, null);
            $product_values = FieldValueFormat('ID', create_guid(), null, $product_values);
            $product_values = FieldValueFormat('ProductID', $row['productid'], null, $product_values);
            $product_values = FieldValueFormat('Count', $row['count'], null, $product_values);
            $product_values = FieldValueFormat('UnitID', $row['unitid'], null, $product_values);
            $product_values = FieldValueFormat('Price', $row['price'], null, $product_values);
            $product_values = FieldValueFormat('Amount', $row['amount'], null, $product_values);
            $product_values = FieldValueFormat('Number', $i, null, $product_values);
            $product_values = FieldValueFormat('Description', $row['description'], null, $product_values);
            InsertRecord('Pact_Product', $product_values['FieldValues'], $con, true);
            $i++;
            $Amount += $row['amount'];
        }

        $pact_update = FieldValueFormat('Amount', $Amount);
        UpdateRecord('Pact', $pact_update['FieldValues'], $pact_id, $con);

        //miv 29.03.2010 Проставим права доступа к договору (как у родительской записи)
        GetRecordPermissions('iris_Offer', $p_id, $permissions, $con);
        ChangeRecordPermissions('iris_Pact', $pact_id, $permissions, $con);
        
        return $number;
    }
}
