<?php
/**********************************************************************
Общие функции для всей конфигурации
**********************************************************************/

use Iris\Iris;

$coreDir = Iris::$app->getCoreDir();

include_once $coreDir . 'core/engine/auth.php';
include_once $coreDir . 'core/engine/applib.php';

////////////// Контстанты ////////////////////////
$dec_point = ".";
$thousands_sep = "'";
global $table_prefix;// = "iris_";
$table_prefix = "iris_";


function IsEmptyValue($Value)
{
	return $Value==null || $Value=="";
}

//Пустое ли значение у поля массива параметров (передавать ['FieldValues'])
function IsEmptyRecordValue($Value, $FieldName)
{
	$val = GetArrayValueByParameter($Value, 'Name', $FieldName, 'Value');
	return (null==$val) || (""==$val);
}


//Преобразование строки для JSON
function json_encode_str($str)
{
	//TODO: перенести в applib
	return UtfEncode($str);
}

function json_decode_str($str)
{
	//TODO: перенести в applib
	return UtfDecode($str);
}

function FiledValuesToAssoc($FiledValues)
{
	$Values = Array();
	for ($i=0; $i<count($FiledValues); $i++) {
		$Values[$i]['Name'] = $FiledValues[$i]->Name;
		$Values[$i]['Value'] = $FiledValues[$i]->Value;
	}
	return $Values;
}


//Возвратить соединение
function GetConnection($p_con = null) {
	if ($p_con != null) {
		$con = $p_con;
	}
	else {
		$con = db_connect();
	}
	return $con;
}
	

//Получить значения связанных полей по id базового поля
//deprecated: Заменять на this->getLinkedValues()
function GetLinkedValues($p_Source, $p_RecordID, $p_Dest, $p_con=null, $p_result=null, $p_rewrite=false)
{
	$con = GetConnection($p_con);

	$select_sql_fields = '';
	$select_sql_join = '';
	for ($i=1; $i<count($p_Dest); $i++) {
		$select_sql_fields .= ", iris_".$p_Source.".".$p_Dest[$i]."ID, iris_".$p_Dest[$i].".Name ";
		$select_sql_join .= "left join iris_".$p_Dest[$i]." on iris_".$p_Dest[$i].".ID=iris_".$p_Source.".".$p_Dest[$i]."ID ";
	}
	
	//продукт по id выпуска
	$select_sql = "select iris_".$p_Source.".".$p_Dest[0]."ID, iris_".$p_Dest[0].".Name ";
	$select_sql .= $select_sql_fields;
	$select_sql .= "from iris_".$p_Source." ";
	$select_sql .= "left join iris_".$p_Dest[0]." on iris_".$p_Dest[0].".ID=iris_".$p_Source.".".$p_Dest[0]."ID ";
	$select_sql .= $select_sql_join;
	$select_sql .= "where iris_".$p_Source.".ID=:p_id";
	$statement = $con->prepare($select_sql);
	$statement->bindParam(':p_id', $p_RecordID);
	$statement->execute();
	$j = 1;
	for ($i=0; $i<count($p_Dest); $i++) {
		$statement->bindColumn($j, $l_query_result[$i]['ID']);
		$j++;
		$statement->bindColumn($j, $l_query_result[$i]['Name']);
		$j++;
	}
	$res = $statement->fetch();

	//TODO: проверять, нет ли уже в FieldValues этого поля. И если есть, то перезаписать или нет в зависимости от $p_rewrite.
	for ($i=0; $i<count($p_Dest); $i++) {
		$p_result = FieldValueFormat($p_Dest[$i].'ID', $l_query_result[$i]['ID'], $l_query_result[$i]['Name'], $p_result);
	}
	return $p_result;
}


/**
 * Получить название таблицы
 * @deprecated use DB::tableName() instead
 */
function GetTableName($table) {
	$table_name = $table;
	if (strlen($table_name) > 2 &&
			$table[0] == '{' && $table[strlen($table)-1] == '}') {
		$table_name = 'iris_' . substr($table, 1, strlen($table)-2);
	}
	return $table_name;
}


/**
 * Получить описание колонки
 */
function GetColumnDescription($column) {
	$result = array('Field' => $column);
	if (strlen($column) > 4 &&
			substr($column, 0, 2) == '{{' && substr($column, -2) == '}}') {
		$name = substr($column, 2, strlen($column) - 4);
		$result = array(
			'Field' => $name . 'ID',
			'GetField' => 'Name',
			'GetTable' => 'iris_' . $name,
		);
	}
	return $result;
}


//Получить значения связанных полей по id базового поля и названию таблиц
//Формат $p_Dest - Array();
//Формат элемента $p_Dest[]:
//		Array('Field' => 'ProjectStateID',
//			['GetField' => 'Code',]
//			['GetTable' => 'iris_ProjectState')],
//		)
//Если не указан GetField, то left join не выполняется и возвращается просто значение колонки
function GetLinkedValuesDetailed($p_Source, $p_RecordID, $p_Dest, $p_con=null, $p_result=null, $p_rewrite=false)
{
	$con = GetConnection($p_con);
	$table_name = GetTableName($p_Source);

	$select_sql_fields = '';
	$select_sql_join = '';
	for ($i=0; $i<count($p_Dest); $i++) {
		if (!is_array($p_Dest[$i])) {
			$p_Dest[$i] = GetColumnDescription($p_Dest[$i]);
		}
	}
	for ($i=1; $i<count($p_Dest); $i++) {
		$select_sql_fields .= ", T.".$p_Dest[$i]['Field']." ";
		if (!empty($p_Dest[$i]['GetField'])) {
			$select_sql_fields .= ", T".$i.".".$p_Dest[$i]['GetField']." ";
			$select_sql_join .= "left join ".$p_Dest[$i]['GetTable']." T".$i." on T".$i.".ID=T.".$p_Dest[$i]['Field']." ";
		}
	}

	//продукт по id выпуска
	$select_sql = "select T.".$p_Dest[0]['Field']." ";
	if (!empty($p_Dest[0]['GetField'])) {
		$select_sql .= ", T0.".$p_Dest[0]['GetField']." ";
	}
	$select_sql .= $select_sql_fields;
	$select_sql .= "from ".$table_name." T ";
	if (!empty($p_Dest[0]['GetField'])) {
		$select_sql .= "left join ".$p_Dest[0]['GetTable']." T0 on T0.ID=T.".$p_Dest[0]['Field']." ";
	}
	$select_sql .= $select_sql_join;
	$select_sql .= "where T.ID=:p_id";
	$statement = $con->prepare($select_sql);
	$statement->bindParam(':p_id', $p_RecordID);
	$statement->execute();
	$j = 1;
	for ($i=0; $i<count($p_Dest); $i++) {
		$statement->bindColumn($j, $l_query_result[$i]['ID']);
		$j++;
		if (!empty($p_Dest[$i]['GetField'])) {
			$statement->bindColumn($j, $l_query_result[$i]['Name']);
			$j++;
		}
	}
	$res = $statement->fetch();

	//TODO: проверять, нет ли уже в FieldValues этого поля. И если есть, то перезаписать или нет в зависимости от $p_rewrite.
	for ($i=0; $i<count($p_Dest); $i++) {
		$p_result = FieldValueFormat($p_Dest[$i]['Field'], $l_query_result[$i]['ID'], !empty($l_query_result[$i]['Name']) ? $l_query_result[$i]['Name'] : null, $p_result);
	}
  //file_put_contents('c:\test.log', $select_sql);     

	return $p_result;
}



//Получить значения связанных полей по id базового поля
function GetValuesFromTable($p_Source, $p_RecordID, $p_Dest, $p_con=null, $p_result=null)
{
	$con = GetConnection($p_con);

	$select_sql = "select iris_".$p_Source.".".$p_Dest[0]." ";
	for ($i=1; $i<count($p_Dest); $i++) {
		$select_sql .= ", iris_".$p_Source.".".$p_Dest[$i]." ";
	}
	$select_sql .= "from iris_".$p_Source." ";
	$select_sql .= "where iris_".$p_Source.".ID=:p_id";
	$statement = $con->prepare($select_sql);
	$statement->bindParam(':p_id', $p_RecordID);
	$statement->execute();
	for ($i=0; $i<count($p_Dest); $i++) {
		$statement->bindColumn($i+1, $l_query_result[$i]);
	}
	$res = $statement->fetch();

	//TODO: проверять, нет ли уже в FieldValues этого поля. И если есть, то перезаписать или нет в зависимости от $p_rewrite.
	for ($i=0; $i<count($p_Dest); $i++) {
		$p_result = FieldValueFormat($p_Dest[$i], $l_query_result[$i], null, $p_result);
	}
	return $p_result;
}


//Получить значение массива по названию параметра
function GetArrayValueByName($p_Array, $p_ParamNameValue, $p_FindParamName='Value')
{
	return GetArrayValueByParameter($p_Array, 'Name', $p_ParamNameValue, $p_FindParamName);
}

//Получить значение массива по значению одного из параметров
function GetArrayValueByParameter($p_Array, $p_ParamName, $p_ParamValue, $p_FindParamName='Value') {
	for ($i=0; $i<count($p_Array); $i++) {
		if ($p_Array[$i][$p_ParamName] == $p_ParamValue) {
			return !empty($p_Array[$i][$p_FindParamName]) ? $p_Array[$i][$p_FindParamName] : null;
		}
	}
	return null;
}


//Задать значение параметра массива (навигация по значению одного из параметров)
function SetArrayValueByParameter(&$p_Array, $p_ParamName, $p_ParamValue, $p_SetParamName, $p_SetParamValue) {
	for ($i=0; $i<count($p_Array); $i++) {
		if ($p_Array[$i][$p_ParamName] == $p_ParamValue) {
			$p_Array[$i][$p_SetParamName] = $p_SetParamValue;
			return true;					
		}
	}
	return false;
}


//Получить значение справочника по коду
function GetDictionaryValue($p_Dict, $p_Code, $p_con = null) {
	$con = GetConnection($p_con);
		
	$select_sql = "select ID, Name from iris_".$p_Dict." where Code=:p_code";
	$statement = $con->prepare($select_sql);
	$statement->bindParam(':p_code', $p_Code);
	$statement->execute();
	$statement->bindColumn(1, $ID);
	$statement->bindColumn(2, $Name);
	$res = $statement->fetch();
	
	return array($ID, $Name);
}


//Получить значения справочников по коду
function GetDictionaryValues($p_CodeArray, $p_con=null, $p_result=null) {
	$con = GetConnection($p_con);
	
	for ($i=0; $i<count($p_CodeArray); $i++) {
		list ($ID, $Name) = GetDictionaryValue($p_CodeArray[$i]['Dict'], $p_CodeArray[$i]['Code'], $con);
		$p_result = FieldValueFormat($p_CodeArray[$i]['Dict'].'ID', $ID, $Name, $p_result);
	}
	
	return $p_result;
}

//Получить значение справочника по умолчнаию
function GetDefaultDictionaryValue($p_Dict, $p_con = null) {
	$con = GetConnection($p_con);
	
	$select_sql = "select ID, Name from iris_".$p_Dict." where isdefault='1'";
	$statement = $con->prepare($select_sql);
	$statement->execute();
	$statement->bindColumn(1, $ID);
	$statement->bindColumn(2, $Name);
	$res = $statement->fetch();
	
	return array($ID, $Name);
}

//Получить значения справочников по умолчанию
function GetDefaultDictionaryValues($p_CodeArray, $p_con=null, $p_result=null) {
	$con = GetConnection($p_con);
	
	for ($i=0; $i<count($p_CodeArray); $i++) {
		list ($ID, $Name) = GetDefaultDictionaryValue($p_CodeArray[$i], $con = null);
		$p_result = FieldValueFormat($p_CodeArray[$i].'ID', $ID, $Name, $p_result);
	}
	
	return $p_result;
}



//Получить значение справочника по ID
function GetFieldValueByID($p_Table, $p_ID, $p_Field, $p_con = null) {
	list ($Value) = GetFieldValuesByID($p_Table, $p_ID, array($p_Field), $p_con = null);
	return $Value;
}


//Получить значения справочника (несколько колонок) по коду
function GetFieldValuesByID($p_Table, $p_ID, $p_Fields, $p_con = null) {
	return GetFieldValuesByFieldValue($p_Table, 'ID', $p_ID, $p_Fields, $p_con = null);
}


//Получить значение таблицы (одной колоноки) по значению одного из полей справочника
function GetFieldValueByFieldValue($p_Table, $p_FieldName, $p_FieldValue, $p_Field, $p_con = null)
{
	list($Value) = GetFieldValuesByFieldValue($p_Table, $p_FieldName, $p_FieldValue, array($p_Field), $p_con);
	return $Value;	
}

	
//Получить значение таблицы (несколько колонок) по значению одного из полей справочника
function GetFieldValuesByFieldValue($p_Table, $p_FieldName, $p_FieldValue, $p_Fields, $p_con = null) {
	$con = GetConnection($p_con);
		
	$select_sql = "select ".$p_Fields[0];
	for ($i=1; $i<count($p_Fields); $i++) {
		$select_sql .= ", ".$p_Fields[$i];
	}
	$select_sql .= " from iris_".$p_Table." where ".$p_FieldName."=:p_value";
	$statement = $con->prepare($select_sql);
	$statement->bindParam(':p_value', $p_FieldValue);
	$statement->execute();
	for ($i=0; $i<count($p_Fields); $i++) {
		$statement->bindColumn($i+1, $Values[$i]);
//		$statement->bindColumn($i+1, $Values[$p_Fields[$i]]);
	}
	$res = $statement->fetch();
	
	return $Values;
}


//Получить значение таблицы (несколько колонок) по значению одного из полей справочника
function GetFormatedFieldValuesByID($p_Table, $p_ID, $p_Fields, $p_con = null)
{
	return GetFormatedFieldValuesByFieldValue($p_Table, 'ID', $p_ID, $p_Fields, $p_con);
}


//Получить значение таблицы (несколько колонок) по значению одного из полей справочника
function GetFormatedFieldValuesByFieldValue($p_Table, $p_FieldName, 
		$p_FieldValue, $p_Fields, $p_con = null) {
	$con = GetConnection($p_con);
	$result = null;

	$select_sql = "select ".$p_Fields[0];
	for ($i=1; $i<count($p_Fields); $i++) {
		$select_sql .= ", ".$p_Fields[$i];
	}
	$select_sql .= " from iris_" . $p_Table 
			. " where " . $p_FieldName . "=:p_value";
	$statement = $con->prepare($select_sql);
	$statement->execute(array(':p_value' => $p_FieldValue));
	for ($i=0; $i<count($p_Fields); $i++) {
		$statement->bindColumn($i+1, $Values[$i]);
	}
	$res = $statement->fetch();

	for ($i=0; $i<count($p_Fields); $i++) {
		$result = FieldValueFormat($p_Fields[$i], $Values[$i], null, $result);
	}

	return $result;
}


//Получить значение системной переменной
function GetSystemVariableValue($p_Code, $p_con = null) {
	$con = GetConnection($p_con);
		
	$select_sql = "select vt.Code, sv.IntValue, sv.FloatValue, sv.StringValue, sv.DateValue, sv.GUIDValue "; 
	$select_sql .= "from iris_SystemVariable sv ";
	$select_sql .= "left join iris_VariableType vt on vt.ID=sv.VariableTypeID ";
	$select_sql .= "where sv.Code=:p_code";
	$statement = $con->prepare($select_sql);
	$statement->bindParam(':p_code', $p_Code);
	$statement->execute();
	$statement->bindColumn(1, $vt_Code);
	$statement->bindColumn(2, $sv_IntValue);
	$statement->bindColumn(3, $sv_FloatValue);
	$statement->bindColumn(4, $sv_StringValue);
	$statement->bindColumn(5, $sv_DateValue);
	$statement->bindColumn(6, $sv_GUIDValue);
	$res = $statement->fetch();
	
	switch ($vt_Code) {
		case 'Int':
			$l_Value = $sv_IntValue;
			break;
		case 'Float':
			$l_Value = $sv_FloatValue;
			break;
		case 'String':
			$l_Value = $sv_StringValue;
			break;
		case 'Date':
			$l_Value = $sv_DateValue;
			break;
		case 'GUID':
			$l_Value = $sv_GUIDValue;
			break;
		default: 
			return null;
	}
	
	return $l_Value;
}


//Установить значение системной переменной
function SetSystemVariableValue($p_Code, $p_Value, $p_con = null) {
	$con = GetConnection($p_con);
		
	$select_sql = "select sv.ID, vt.Code "; 
	$select_sql .= "from iris_SystemVariable sv ";
	$select_sql .= "left join iris_VariableType vt on vt.ID=sv.VariableTypeID ";
	$select_sql .= "where sv.Code=:p_code";
	$statement = $con->prepare($select_sql);
	$statement->bindParam(':p_code', $p_Code);
	$statement->execute();
	$statement->bindColumn(1, $sv_ID);
	$statement->bindColumn(2, $vt_Code);
	$res = $statement->fetch();
	
	switch ($vt_Code) {
		case 'Int':
			$FieldName = 'IntValue';
			break;
		case 'Float':
			$FieldName = 'FloatValue';
			break;
		case 'String':
			$FieldName = 'StringValue';
			break;
		case 'Date':
			$FieldName = 'DateValue';
			break;
		case 'GUID':
			$FieldName = 'GUIDValue';
			break;
		default: 
			return false;
	}
	
	//запись
	$update_sql = "update iris_SystemVariable set ".$FieldName."=:p_value ";
	$update_sql .= "where ID=:p_id";
	$statement = $con->prepare($update_sql);
	$statement->bindParam(':p_value', $p_Value);
	$statement->bindParam(':p_id', $sv_ID);
	$statement->execute();	
	
	return true;
}


//Сформатировать значение поля в спец. формат
function FieldValueFormat($p_Name, $p_Value, $p_Caption=null, $p_result=null, $doencode = true) {
	$j = count(isset($p_result['FieldValues']) ? $p_result['FieldValues'] : []);
	$p_result['FieldValues'][$j]['Name'] = $p_Name;
	$p_result['FieldValues'][$j]['Value'] = $doencode ? json_encode_str($p_Value) : $p_Value;
	if ($p_Caption != null) {
		$p_result['FieldValues'][$j]['Caption'] = $doencode ? json_encode_str($p_Caption): $p_Caption;
	}

	return $p_result;
}


//Получить id, имя пользователя
function GetShortUserInfo($p_Login, $p_con = null) {
	$Session = IrisSession::getInstance();
	if ($p_Login == $Session->userLogin()) {
		$User = IrisUser::getInstance();
		return array($User->property('id'), $User->property('name'));
	}

	$con = GetConnection($p_con);
		
	$select_sql = "select ID, Name from iris_Contact where Login=:p_login";
	$statement = $con->prepare($select_sql);
	$statement->bindParam(':p_login', $p_Login);
	$statement->execute();
	$statement->bindColumn(1, $ID);
	$statement->bindColumn(2, $Name);
	$res = $statement->fetch();
	
	return array($ID, $Name);
}


//Получить ответственного по умолчанию
function GetDefaultOwner($p_Login, $p_con = null, $p_result = null) {
	$con = GetConnection($p_con);
	
	list ($ID, $Name) = GetShortUserInfo(GetUserName(), $con);
	$p_result = FieldValueFormat('OwnerID', $ID, $Name, $p_result);
	return $p_result;
}


//Получить ответственного пользователя
function GetCurrentUserOwner($p_UserID, $p_con=null)
{
	$con = GetConnection($p_con);
	$result = GetLinkedValuesDetailed('iris_Contact', $p_UserID, array(
		array(
			'Field' => 'OwnerID', 
			'GetTable' => 'iris_Contact',
			'GetField' => 'Name')
		), $con);
	return $result;
}


//Получить текущее время
function GetCurrentDBDateTime($p_con=null) {
	$DB = DB::getInstance();
	$Local = Local::getInstance();
	return $Local->dbDateTimeToLocal($DB->datetime());
}


//Получить текущую дату
function GetCurrentDBDate($p_con=null) {
	$DB = DB::getInstance();
	$Local = Local::getInstance();
	return $Local->dbDateToLocal($DB->datetime());
}

//Сгенерировать новый номер
function GenerateNewNumber($p_NumberCode, $p_DateCode=null, $p_con=null, $p_format=0)
{
	$con = GetConnection($p_con);

	if ($p_DateCode != null) {
		$Date = GetSystemVariableValue($p_DateCode, $con);
		$Date = date('d.m.Y', strtotime($Date));
	}
	else {
		$Date = date('d.m.Y');
	}

	$date_now = date('d.m.Y');
	
	if ($Date != $date_now) {
		$Number = 1;
	}
	else {
		//номер
		$Number = GetSystemVariableValue($p_NumberCode, $con);	
		$Number++;
	}

	// miv 24.12.2010: номер теперь всегда трехзначный
	if ($Number <= 9)
		$Number = '00'.$Number;
	if (($Number >= 10) and ($Number <= 99))
		$Number = '0'.$Number;

	if ($p_format == 0) {
		$Number = date("ymd").'-'.$Number;
		return $Number;
	}
	else
	if ($p_format == 1) {
		return $Number;
	}
	else
	if ($p_format == 2) {
		return array ($Number, date("ymd").'-'.$Number);
	}
}


//Обновить запись по ID
function UpdateRecord($p_Table, $p_Fields, $p_ID, $p_con=null, $unconvert=false) {
	$con = GetConnection($p_con);
	$v = null;

  //Добавляем поля - дата изменения, изменил в том случае, если их ещё нет в $p_Fields
  $modifyid = null;
  $modifydate = null;
  foreach ($p_Fields as $value) {
    if (strtolower($value['Name']) == 'modifyid') {
      $modifyid = 1;
    }
    if (strtolower($value['Name']) == 'modifydate') {
      $modifydate = 1;
    }
  }
  
  $userid = GetUserID($con);
  $date = GetCurrentDBDateTime($con);
  $v = FieldValueFormat('modifyid', $userid, null, $v);
  $v = FieldValueFormat('modifydate', $date, null, $v);
  
  if (!$modifyid) {
    $p_Fields[] = $v['FieldValues'][0];
  }
  if (!$modifydate) {
    $p_Fields[] = $v['FieldValues'][1];
  }
  
  
  //Формируем запрос
	$update_sql = "update iris_".$p_Table." set ".$p_Fields[0]['Name']."=:p_value0 ";
	for ($i=1; $i<count($p_Fields); $i++) {
		$update_sql .= ", ".$p_Fields[$i]['Name']."=:p_value".$i;
	}
	$update_sql .= " where ID=:p_id";

	$statement = $con->prepare($update_sql, array(PDO::ATTR_EMULATE_PREPARES => true));
	$params = array();
	$params[':p_id'] = $p_ID;
	for ($i=0; $i<count($p_Fields); $i++) {
    	if ($p_Fields[$i]['Value'] == '') {
			$p_Fields[$i]['Value'] = null;
		}
		$params[':p_value' . $i] = $unconvert && $p_Fields[$i]['Value'] 
				? json_decode_str($p_Fields[$i]['Value']) 
				: $p_Fields[$i]['Value'];
	}
	$statement->execute($params);

	if ($statement->errorCode() != '00000') {
		return false;
	}	
	return true;
}


//Вставить запись (в $p_Fields должен быть только 'FieldValues')
function InsertRecord($p_Table, $p_Fields, $p_con=null, $unconvert=false) {
	$con = GetConnection($p_con);
	$v = null;

	$p_Table = GetTableName($p_Table);
	if (substr($p_Table, 0, 5) != 'iris_') {
		$p_Table = 'iris_' . $p_Table;
	}
  
  //Добавляем поля - дата создания, изменения, автор, изменил в том случае, если их ещё нет в $p_Fields
  $createid = null;
  $modifyid = null;
  $createdate = null;
  $modifydate = null;
  foreach ($p_Fields as &$value) {
    if (strtolower($value['Name']) == 'createid') {
      $createid = 1;
    }
    if (strtolower($value['Name']) == 'modifyid') {
      $modifyid = 1;
    }
    if (strtolower($value['Name']) == 'createdate') {
      $createdate = 1;
    }
    if (strtolower($value['Name']) == 'modifydate') {
      $modifydate = 1;
    }
  }
  
  $userid = GetUserID($con);
  $date = GetCurrentDBDateTime($con);
  $v = FieldValueFormat('createid', $userid, null, $v);
  $v = FieldValueFormat('modifyid', $userid, null, $v);
  $v = FieldValueFormat('createdate', $date, null, $v);
  $v = FieldValueFormat('modifydate', $date, null, $v);
  
  if (!$createid) {
    $p_Fields[] = $v['FieldValues'][0];
  }
  if (!$modifyid) {
    $p_Fields[] = $v['FieldValues'][1];
  }
  if (!$createdate) {
    $p_Fields[] = $v['FieldValues'][2];
  }
  if (!$modifydate) {
    $p_Fields[] = $v['FieldValues'][3];
  }

  
  //Создаём запрос на вставку
	$insert_sql = "insert into " . $p_Table . " (";
	$count = 0;
	for ($i=0; $i<count($p_Fields); $i++) {
		
		if (($p_Fields[$i]['Value'] == '') || $p_Fields[$i]['Value'] == null) {
			continue;
		}
		for ($j=0; $j<$i; $j++) {
			if ($p_Fields[$j]['Name'] == $p_Fields[$i]['Name']) {
				continue 2;
			}
		}
		
		$insert_sql .= $count == 0 ? "" : ", ";
		$insert_sql .= $p_Fields[$i]['Name'];
		$count++;
	}
	$insert_sql .= ") values (";
	$count = 0;
	for ($i=0; $i<count($p_Fields); $i++) {

		if (($p_Fields[$i]['Value'] == '') || $p_Fields[$i]['Value'] == null) {
			continue;
		}
		for ($j=0; $j<$i; $j++) {
			if ($p_Fields[$j]['Name'] == $p_Fields[$i]['Name']) {
				continue 2;
			}
		}
		
		$insert_sql .= $count == 0 ? "" : ", ";
		$insert_sql .= ":p_value".$i;
		$count++;
	}
	$insert_sql .= ")";

	$statement = $con->prepare($insert_sql);
	$params = array();
	for ($i=0; $i<count($p_Fields); $i++) {

		if (($p_Fields[$i]['Value'] == '') || $p_Fields[$i]['Value'] == null) {
			continue;
		}
		for ($j=0; $j<$i; $j++) {
			if ($p_Fields[$j]['Name'] == $p_Fields[$i]['Name']) {
				continue 2; 
			}
		}
//		echo '/'.$p_Fields[$i]['Value'];
		//if (!$unconvert) {
			$params[':p_value' . $i] = $p_Fields[$i]['Value'];
			//$statement->bindParam(':p_value' . $i, (string)$p_Fields[$i]['Value']);
		//}
		//else {
		//	$val = json_decode_str($p_Fields[$i]['Value']);
		//	$statement->bindParam(':p_value'.$i, json_decode_str($p_Fields[$i]['Value']));
		//}
	}
	$statement->execute($params);
	if ($statement->errorCode() != '00000') {
		return false;
	}	
	
	return true;
}


//Обновить номер
function UpdateNumber($p_Table, $p_id, $p_NumberCode, $p_NumberCodeDate=null, $p_con=null)
{
	$con = GetConnection($p_con);

	list($Number, $FullNumber) = GenerateNewNumber($p_NumberCode, $p_NumberCodeDate, $con, 2);
	$Date = GetCurrentDBDate($con);
	
	SetSystemVariableValue($p_NumberCode, $Number, $con);
	SetSystemVariableValue($p_NumberCodeDate, $Date, $con);

	list($rec_umber, $rec_name) = GetFieldValuesByID($p_Table, $p_id, array('number', 'name'), $con);
	
	// Обновим номер записи
	$RecName = iris_str_replace($rec_umber, $FullNumber, $rec_name);
	if ($rec_umber != $FullNumber) {
		$Fields = FieldValueFormat('Number', $FullNumber);
		$Fields = FieldValueFormat('Name', $RecName, null, $Fields);
		UpdateRecord($p_Table, $Fields['FieldValues'], $p_id, $con);
	}

	// Результат не используется
	$result['UpdateNumber']['Number'] = json_encode_str($FullNumber);
	$result['UpdateNumber']['Date'] = json_encode_str($Date);
	$result['UpdateNumber']['Name'] = json_encode_str($RecName);
	$result['UpdateNumber']['ID'] = $p_id;
	return $result;	
}


//Загрузка шаблона отчета html
function LoadTemplate($p_file_name) {
	$l_fh = fopen($p_file_name, 'r') or die('Файл шаблона '.$p_file_name.' не найден');
	$template_html = '';
	while (!feof($l_fh)) {
		$template_html .= fgets($l_fh);
	}
	$l_b = iris_strpos($template_html, '<!--begin_template-->');
	$l_e = iris_strpos($template_html, '<!--end_template-->');
	if ($l_e != false) {
		$template_html = iris_substr($template_html, $l_b, $l_e + strlen('<!--end_template-->'));
	}
	return $template_html;	
}
