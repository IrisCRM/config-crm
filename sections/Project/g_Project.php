<?php

namespace Iris\Config\CRM\sections\Project;

use Config;
use Iris\Iris;
use PDO;

/**
 * Таблица проекта
 */
class g_Project extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    /**
     * Копирует указанный заказ
     */
    public function copyProject($params) {
        $project_id = $params['id'];
        $con = db_connect();
        
        // проверим права доступа
        $userid = getUserID($con);
        GetUserRecordPermissions('iris_project', $project_id, $userid, $permissions, $con);
        if ($permissions['w'] != 1) 
            return array("success" => 0, "message" => json_convert('Для копирования заказа пользователь должен иметь право на его редактирование'));
            
        $project_columns = $this->getTableFileds('iris_project', 'id,createid,createdate,modifyid,modifydate,planstartdate,planfinishdate');
        $new_id = create_guid();
        $sql  = "insert into iris_project ";
        $sql .= "(id,createid,createdate,".$project_columns.",planstartdate,planfinishdate) ";
        $sql .= "select ";
        $sql .= ":newid as id, '".$userid."', now(), ".$project_columns.", planfinishdate + interval '1 day', planfinishdate + (round((planfinishdate - planstartdate)::float/30)||' month')::interval ";
        $sql .= "from iris_project where id=:oldid";
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":oldid" => $project_id, ":newid" => $new_id));
        if ($cmd->errorCode() != '00000')
            return array("success" => 0, "message" => json_convert('Не удалось скопировать заказ'));
        
        // изменим номер заказа
        UpdateNumber('Project', $new_id, 'ProjectNumber', 'ProjectNumberDate');

        // изменим описание нового заказа (заменим старый номер на новый если он есть)
        $cmd = $con->prepare("select number as num from iris_project where id=:id");
        $cmd->execute(array(":id" => $project_id));
        $old = current($cmd->fetchAll(PDO::FETCH_ASSOC));
        
        $cmd = $con->prepare("select number, name from iris_project where id=:id");
        $cmd->execute(array(":id" => $new_id));
        $new = current($cmd->fetchAll(PDO::FETCH_ASSOC));

        $new['name'] = iris_str_replace($old['num'], $new['number'], $new['name']);
        $cmd = $con->prepare("update iris_project set name=:name where id=:id");
        $cmd->execute(array(":id" => $new_id, ":name" => $new['name']));
        
        // изменим некоторые поля заказа
        $update_sql  = "update iris_project set income=0, expense=0, profit=0, ownerid=:ownerid, isremind=0, reminddate=null, ";
        $update_sql .= "projectstateid = (select id from iris_projectstate where code='Plan'), ";
        $update_sql .= "projectstageid = (select id from iris_projectstage where code='Sale_Info'), ";
        $update_sql .= "probability = (select probability from iris_projectstage where code='Sale_Info'), ";
        $update_sql .= "startdate=null, finishdate=null ";
        $update_sql .= "where id=:id";
        $cmd = $con->prepare($update_sql);
        $cmd->execute(array(":id" => $new_id, ":ownerid" => $userid));
        if ($cmd->errorCode() != '00000')
            return array("success" => 0, "message" => json_convert('Не удалось изменить параметры заказа'));
        
        // вставим права доступа для заказа
        if (SetDefaultPermissions('iris_project', $new_id, $userid, $con) <> 0)
            return array("success" => 1, "message" => json_convert('Заказ скопирован, но права доступа не были просталвены'));
        
        // скопируем вкладку продукты заказа
        $project_product_columns = $this->getTableFileds('iris_project_product', 'id,createid,createdate,modifyid,modifydate,projectid');
        $sql  = "insert into iris_project_product ";
        $sql .= "(id,createid,createdate,modifyid,modifydate,".$project_product_columns.", projectid) ";
        $sql .= "select iris_genguid() as id,'".$userid."', now(), null, null,".$project_product_columns.", :newid from iris_project_product ";
        $sql .= "where projectid= :oldid";
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":oldid" => $project_id, ":newid" => $new_id));
        if ($cmd->errorCode() != '00000')
            return array("success" => 1, "message" => json_convert('Не удалось скопировать продукты заказа'));

        // изменим продолжительность и описание у скопированных продуктов
        $cmd = $con->prepare("select id,to_char(startdate, 'DD.MM.YYYY') as dts,to_char(finishdate, 'DD.MM.YYYY') as dte,".$project_product_columns." from iris_project_product where projectid=:newprojectid");
        $cmd->execute(array(":newprojectid" => $new_id));
        $newproducts = $cmd->fetchAll(PDO::FETCH_ASSOC);
        foreach($newproducts as $product) {
            if (($product['dte'] == '') or ($product['duration'] == '') or ($product['timeunit'] == ''))
                continue;
            switch ($product['timeunit']) {
                case 'd':
                    $timeunit = 'day';
                    break;
                case 'm':
                    $timeunit = 'month';
                    break;
                case 'y':
                    $timeunit = 'year';
                    break;
            }
            $interval = $product['duration'].' '.$timeunit;
            $update_sql  = "update iris_project_product set startdate = to_date(:dte, 'DD.MM.YYYY') + interval '1 day', finishdate = to_date(:dte, 'DD.MM.YYYY') + interval '".$interval."', description = 'Период с '|| to_char(to_date(:dte, 'DD.MM.YYYY') + interval '1 day', 'DD.MM.YYYY') ||' по '|| to_char(to_date(:dte, 'DD.MM.YYYY') + interval '".$interval."', 'DD.MM.YYYY') ";
            $update_sql .= "where id = :id";
            $upd_cmd = $con->prepare($update_sql, array(PDO::ATTR_EMULATE_PREPARES => true));
            $upd_cmd->execute(array(":id" => $product['id'], ":dte" => $product['dte']));        
        }
        
        return array("success" => 1, "message" => json_convert('Заказ скопирован'));
    }

    /**
     * Создание счета из проекта
     */
    public function createInvoice($params)
    {
        $p_id = $params['id'];
        $con = GetConnection();
        
        //Вставим карточку счета
        //Получим информацию из карточки проекта
        $project_info = GetFormatedFieldValuesByFieldValue('Project', 'ID', $p_id, array(
            'AccountID', 'ContactID', 'CurrencyID'), $con);
        
        //Значения в карточке счета по умолчанию
        $className = $this->_Loader->getActualClassName('sections\\Invoice\\s_Invoice');
        $Invoice = new $className($this->_Loader);
        $invoice_values = $Invoice->onPrepare(array('mode' => 'insert'), $project_info);
        //$invoice_values = Invoice_GetDefaultValues($project_info);
        
        // miv 19.08.2011: если пользователь из группы клиентов, то ответсвенный за счет будет тот, кто ответственный за заказ
        $userinfo = GetUserAccessInfo($con);
        if ($userinfo['userrolecode'] == 'Client') {
            foreach ($invoice_values['FieldValues'] as $key => $value) {
                if ($value['Name'] == 'OwnerID') {
                    $cmd = $con->prepare("select T1.id as id, T1.name as name from iris_project T0 left join iris_contact T1 on T0.ownerid = T1.id where T0.id=:id");
                    $cmd->execute(array(":id" => $p_id));
                    $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                    $invoice_values['FieldValues'][$key]['Value'] = $res[0]['id'];
                    $invoice_values['FieldValues'][$key]['Caption'] = $res[0]['name'];
                    break;
                }
            }
        }
        
        $invoice_id = create_guid();
        //Добавим в колонки счета ID счета и ID проекта
        $invoice_values = FieldValueFormat('ID', $invoice_id, null, $invoice_values);
        $invoice_values = FieldValueFormat('ProjectID', $p_id, null, $invoice_values);

        // miv 21.12.2010: заполняются реквезиты клиента из компании заказа
        $AccountID = GetArrayValueByName($project_info['FieldValues'], 'AccountID');
        $ap_sql = "select ap.ID as id, ap.Name as name from iris_Account_Property ap ";
        $ap_sql .= "where ap.AccountID='".$AccountID."' and ap.IsMain=1";
        $AP = current($con->query($ap_sql)->fetchAll(PDO::FETCH_ASSOC));
        if ($AP['id'] != '') {
            $invoice_values = FieldValueFormat('Account_PropertyID', $AP['id'], $AP['name'], $invoice_values);
        }
        
        // miv 23.03.2011: в название счета добавляется название компании
        $account_result = GetValuesFromTable('Account', $AccountID, array('Name'), $con);
        foreach ($invoice_values['FieldValues'] as $key => $value) {
            if ($value['Name'] == 'Name') {
                $invoice_values['FieldValues'][$key]['Value'] = GetArrayValueByName($invoice_values['FieldValues'], 'Number').' - '.UtfDecode(GetArrayValueByName($account_result['FieldValues'], 'Name'));
                break;
            }
        }
        
        //Вставка счета
        InsertRecord('Invoice', $invoice_values['FieldValues'], $con);
        //И обновим номер счета для верности
        $number = UpdateNumber('Invoice', $invoice_id, 'InvoiceNumber', 'InvoiceNumberDate');


        //Вставим продукты в счет (цикл)
        //Выбор всех продуктов из проекта
        $select_sql = "select pp.ID as id, pp.ProductID as productid, pp.Count as count, pp.UnitID as unitid, ";
        $select_sql .= "pp.Price as price, pp.Amount as amount, pp.Number as number, pp.Discount as discount, pp.description as description ";
        $select_sql .= "from iris_Project_Product pp where pp.ProjectID=:p_project_id order by pp.Number";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_project_id', $p_id);
        $statement->execute();
        $res = $statement->fetchAll();

        $i = 1;
        $Amount = 0;
        $HaveProduct = false;
        foreach ($res as $row) {
            //Посчитаем уже существующие в счетах продукты
            $have_sql = "select SUM(m.Count) ";
            $have_sql .= "from iris_Invoice_Product m ";
            $have_sql .= "inner join iris_Invoice i on m.InvoiceID=i.ID ";
            $have_sql .= "left join iris_InvoiceState ist on ist.ID=i.InvoiceStateID ";
            $have_sql .= "left join iris_InvoiceType it on it.ID=i.InvoiceTypeID ";
            $have_sql .= "where i.ProjectID=:p_project_id ";
            $have_sql .= "and m.ProductID=:p_product_id ";
            $have_sql .= "and i.ID<>:p_invoice_id ";
            $have_sql .= "and it.Code='In' ";
            $have_sql .= "and ist.Code in ('Plan', 'Submited', 'Payment', 'Payed', 'Part') ";
            $have_sql .= "and ((m.UnitID in (select UnitID from iris_Project_Product where ID=:p_ppid))";
            $have_sql .= "or (m.UnitID is null and (select UnitID from iris_Project_Product where ID=:p_ppid1) is null))";
            $have_statement = $con->prepare($have_sql);
            $have_statement->bindParam(':p_project_id', $p_id);
            $have_statement->bindParam(':p_invoice_id', $invoice_id);
            $have_statement->bindParam(':p_product_id', $row['productid']);
            $have_statement->bindParam(':p_ppid', $row['id']);
            $have_statement->bindParam(':p_ppid1', $row['id']);
            $have_statement->execute();
            $have_statement->bindColumn(1, $have_count);
            $have_res = $have_statement->fetch();        
            
            //Если выставлено в счетах меньше, чем есть в проекте, то добавим в счет
            if ($row['count']-$have_count > 0) {
                $price = $row['price']*(1-$row['discount']/100);
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
                $HaveProduct = true;
                $i++;
                $Amount += $price*$count;//$row['amount'];
            }
        }

        if ($HaveProduct) {
            //Обновим сумму счета, она равна сумме сумм продуктов в счете
            $invoice_update = FieldValueFormat('Amount', $Amount);
        }
        else {
            //Обновим сумму счета, она равна сумме, на которую еще не выставлены счета

            //Сумма проекта
            $PlanIncome = GetFieldValueByID('Project', $p_id, 'PlanIncome', $con);
            
            //Посчитаем уже существующие счета
            $have_sql = "select SUM(i.Amount) ";
            $have_sql .= "from iris_Invoice i ";
            $have_sql .= "left join iris_InvoiceState ist on ist.ID=i.InvoiceStateID ";
            $have_sql .= "left join iris_InvoiceType it on it.ID=i.InvoiceTypeID ";
            $have_sql .= "where i.ProjectID=:p_project_id ";
            $have_sql .= "and i.ID<>:p_invoice_id ";
            $have_sql .= "and it.Code='In' ";
            $have_sql .= "and ist.Code in ('Plan', 'Submited', 'Payment', 'Payed', 'Part') ";
            
            $have_statement = $con->prepare($have_sql);
            $have_statement->bindParam(':p_project_id', $p_id);
            $have_statement->bindParam(':p_invoice_id', $invoice_id);
            $have_statement->execute();
            $have_statement->bindColumn(1, $have_amount);
            $have_res = $have_statement->fetch();        

            //Если выставлено в счетах меньше, чем есть в проекте, то добавим в счет
            $invoice_update = FieldValueFormat('Amount', $PlanIncome-$have_amount);
        }
        UpdateRecord('Invoice', $invoice_update['FieldValues'], $invoice_id, $con);

        //Проставим права доступа к счету (скопирууем их из проекта)
        GetRecordPermissions('iris_Project', $p_id, $permissions, $con);
        ChangeRecordPermissions('iris_Invoice', $invoice_id, $permissions, $con);
        
        // miv 06.09.2011: добавим клиента в доступ на чтение, если это необходимо
        $isclientaccess = GetSystemVariableValue('InvoiceClientAccess', $con);
        if ($isclientaccess == 1) {
            $ContactID = GetArrayValueByName($project_info['FieldValues'], 'ContactID');
            $perm[] = array('userid' => $ContactID, 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
            ChangeRecordPermissions('iris_invoice', $invoice_id, $perm, $con);
        }
        
        return $number;
    }


    /**
     * Создание КП из проекта
     */
    public function createOffer($params)
    {
        $p_id = $params['id'];
        $con = GetConnection();
        
        $project_info = GetFormatedFieldValuesByFieldValue('Project', 'ID', $p_id, array(
            'AccountID', 'ContactID', 'CurrencyID'), $con);

        $className = $this->_Loader->getActualClassName('sections\\Offer\\s_Offer');
        $Offer = new $className($this->_Loader);
        $offer_values = $Offer->onPrepare(array('mode' => 'insert'), $project_info);
//        $offer_values = Offer_GetDefaultValues($project_info);
        $offer_id = create_guid();
        $offer_values = FieldValueFormat('ID', $offer_id, null, $offer_values);
        $offer_values = FieldValueFormat('ProjectID', $p_id, null, $offer_values);
        
        //Вставка записи
        InsertRecord('Offer', $offer_values['FieldValues'], $con);
        $number = UpdateNumber('Offer', $offer_id, 'OfferNumber', 'OfferNumberDate');


        //Вставим продукты (цикл)
        $select_sql = "select pp.ProductID as productid, pp.Count as count, pp.UnitID as unitid, ";
        $select_sql .= "pp.Price as price, pp.Amount as amount, pp.Number as number, pp.Discount as discount, pp.description as description ";
        $select_sql .= "from iris_Project_Product pp where pp.ProjectID=:p_project_id order by pp.Number";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_project_id', $p_id);
        $statement->execute();
        $res = $statement->fetchAll();

        $i=1;
        $Amount = 0;
        foreach ($res as $row) {
            $product_values = null;
            $product_values = FieldValueFormat('OfferID', $offer_id, null);
            $product_values = FieldValueFormat('ID', create_guid(), null, $product_values);
            $product_values = FieldValueFormat('ProductID', $row['productid'], null, $product_values);
            $product_values = FieldValueFormat('Count', $row['count'], null, $product_values);
            $product_values = FieldValueFormat('UnitID', $row['unitid'], null, $product_values);
            $product_values = FieldValueFormat('Price', $row['price']*(1-$row['discount']/100), null, $product_values);
            $product_values = FieldValueFormat('Amount', $row['amount'], null, $product_values);
            $product_values = FieldValueFormat('Number', $i, null, $product_values);
            $product_values = FieldValueFormat('Description', $row['description'], null, $product_values);
            InsertRecord('Offer_Product', $product_values['FieldValues'], $con, true);
            $i++;
            $Amount += $row['amount'];
        }

        $offer_update = FieldValueFormat('Amount', $Amount);
        UpdateRecord('Offer', $offer_update['FieldValues'], $offer_id, $con);

        //Проставим права доступа к КП (скопирууем их из проекта)
        GetRecordPermissions('iris_Project', $p_id, $permissions, $con);
        ChangeRecordPermissions('iris_Offer', $offer_id, $permissions, $con);
            
        return $number;
    }


    /**
     * Создание Договор из проекта
     */
    public function createPact($params)
    {
        $p_id = $params['id'];
        $con = GetConnection();
        
        $project_info = GetFormatedFieldValuesByFieldValue('Project', 'ID', $p_id, array(
            'AccountID', 'ContactID', 'CurrencyID'), $con);

        $className = $this->_Loader->getActualClassName('sections\\Pact\\s_Pact');
        $Pact = new $className($this->_Loader);
        $pact_values = $Pact->onPrepare(array('mode' => 'insert'), $project_info);
//        $pact_values = Pact_GetDefaultValues($project_info);
        $pact_id = create_guid();
        $pact_values = FieldValueFormat('ID', $pact_id, null, $pact_values);
        $pact_values = FieldValueFormat('ProjectID', $p_id, null, $pact_values);
        
        // miv 21.12.2010: заполняются реквезиты клиента из компании заказа
        $AccountID = GetArrayValueByName($project_info['FieldValues'], 'AccountID');
        $ap_sql = "select ap.ID as id, ap.Name as name from iris_Account_Property ap ";
        $ap_sql .= "where ap.AccountID='".$AccountID."' and ap.IsMain=1";
        $AP = current($con->query($ap_sql)->fetchAll(PDO::FETCH_ASSOC));
        if ($AP['id'] != '') {
            $pact_values = FieldValueFormat('Account_PropertyID', $AP['id'], $AP['name'], $pact_values);
        }
        
        // miv 23.03.2011: в название счета добавляется название компании
        $account_result = GetValuesFromTable('Account', $AccountID, array('Name'), $con);
        foreach ($pact_values['FieldValues'] as $key => $value) {
            if ($value['Name'] == 'Name') {
                $pact_values['FieldValues'][$key]['Value'] = GetArrayValueByName(
                    $pact_values['FieldValues'], 'Number').' - '.UtfDecode(
                    GetArrayValueByName($account_result['FieldValues'], 'Name'));
                break;
            }
        }

        //Вставка записи
        InsertRecord('Pact', $pact_values['FieldValues'], $con);
        $number = UpdateNumber('Pact', $pact_id, 'PactNumber', 'PactNumberDate');


        //Вставим продукты (цикл)
        $select_sql = "select pp.ProductID as productid, pp.Count as count, pp.UnitID as unitid, ";
        $select_sql .= "pp.Price as price, pp.Amount as amount, pp.Number as number, pp.Discount as discount, pp.description as description ";
        $select_sql .= "from iris_Project_Product pp where pp.ProjectID=:p_project_id order by pp.Number";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_project_id', $p_id);
        $statement->execute();
        $res = $statement->fetchAll();

        $i=1;
        $Amount = 0;
        foreach ($res as $row) {
            $product_values = null;
            $product_values = FieldValueFormat('PactID', $pact_id, null);
            $product_values = FieldValueFormat('ID', create_guid(), null, $product_values);
            $product_values = FieldValueFormat('ProductID', $row['productid'], null, $product_values);
            $product_values = FieldValueFormat('Count', $row['count'], null, $product_values);
            $product_values = FieldValueFormat('UnitID', $row['unitid'], null, $product_values);
            $product_values = FieldValueFormat('Price', $row['price']*(1-$row['discount']/100), null, $product_values);
            $product_values = FieldValueFormat('Amount', $row['amount'], null, $product_values);
            $product_values = FieldValueFormat('Number', $i, null, $product_values);
            $product_values = FieldValueFormat('Description', $row['description'], null, $product_values);
            InsertRecord('Pact_Product', $product_values['FieldValues'], $con, true);
            $i++;
            $Amount += $row['amount'];
        }

        $pact_update = FieldValueFormat('Amount', $Amount);
        UpdateRecord('Pact', $pact_update['FieldValues'], $pact_id, $con);

        //Проставим права доступа к КП (скопирууем их из проекта)
        GetRecordPermissions('iris_Project', $p_id, $permissions, $con);
        ChangeRecordPermissions('iris_Pact', $pact_id, $permissions, $con);
        
        return $number;
    }

    /**
     * Возвращает строку, содержащую через запятую список колонок талицы
     */
    protected function getTableFileds($p_table_name, $p_exlude_columns) {
        $sql  = "select T0.code as column from iris_table_column T0 ";
        $sql .= "left join iris_table T1 on T0.tableid = T1.id ";
        $sql .= "where T1.code=:table and T0.code not in (select * from iris_explode_str(',', :columns))";
        $con = db_connect();
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":table" => $p_table_name, ":columns" => $p_exlude_columns));
        $columns = $cmd->fetchAll(PDO::FETCH_ASSOC);
        $res = array();
        foreach ($columns as $column) {
            $res[] = $column['column'];
        }
        return implode(',', $res);
    }
}
