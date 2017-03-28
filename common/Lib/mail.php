<?php

/**********************************************************************
 * Функции для работы с email
 **********************************************************************/

 
if (!session_id()) {
  @session_start();
  if (!session_id()) {
    //TODO: позаботиться о правильном выводе ошибки
    echo 'Невозможно создать сессию!';
  }
}

include_once Loader::getLoader()->corePath() . 'core/engine/printform.php';


//Создание исходящего письма
//p_templateid - ID шаблона письма (таблица iris_email)
//p_toid - ID контакта-получателя (таблица iris_contact)
//p_tomail - email получателя. Если передано пустое значение, то выберается email из карточки контакта p_toid
//p_replacetags - ассоциативный массив для автоподстановок. Ключ - поле для замены. Значение - значение для замены.
//p_fromemail - email пользователя, отправляющего письмо. Если не указан, то email текущего пользователя
//p_emailtypecode - тип создаваемого письма. По умолчанию - исходящее.
//p_con - соединение с базой. Если не указано, то будет создано новое соединение
function CreateEmail($p_templateid, $p_toid, $p_tomail, 
$p_replacetags = null, $p_fromemail = null, $p_emailtypecode = 'Outbox', $p_con = null) 
{
	$con = GetConnection($p_con);

	//Информация о получателе - email и компания
  list($email, $accountid, $ownerid) = GetFieldValuesByFieldValue('Contact', 'ID', $p_toid, 
    array('email', 'accountid', 'ownerid'), $con);
  if (!IsEmptyValue($p_tomail)) {
    $email = $p_tomail;
  }
    
  list ($fromid, $fromname) = GetShortUserInfo(GetUserName(), $con);

  $fromemail = $p_fromemail;
  if (IsEmptyValue($p_fromemail)) {
    list($fromemail) = GetFieldValuesByFieldValue('Contact', 'ID', $fromid, 
      array('email'), $con);
  }
  list($fromemailaccountid) = GetFieldValuesByFieldValue('EmailAccount', 'email', $fromemail, 
    array('id'), $con);

  //Тема и тело письма
  list($subject, $body) = GetFieldValuesByFieldValue('Email', 'ID', $p_templateid, 
    array('subject', 'body'), $con);
	$subject = FillFormFromText($subject, 'Contact', $p_toid);
	$body = FillFormFromText($body, 'Contact', $p_toid);
  //Дополнительные замены
  foreach($p_replacetags as $key => $value) {
    $body = iris_str_replace($key, $value, $body);
  }

  //ID нового письма
  $new_id = create_guid();
  
	//Создание исходящего
  $sql = "insert into iris_email (id, e_from, emailaccountid, e_to, contactid, accountid, ownerid, emailtypeid, subject, body) ".
    "values (:id, :e_from, :emailaccountid, :e_to, :contactid, :accountid, :ownerid, ".
    "(select id from iris_emailtype where code = '$p_emailtypecode'), :subject, :body)";
	$ins_cmd = $con->prepare($sql);
  if ($ins_cmd->execute(array(
    ":id" => $new_id,
    ":e_from" => $fromemail,
    ":emailaccountid" => $fromemailaccountid,
    ":e_to" => $email,
    ":contactid" => $p_toid,
    ":accountid" => $accountid,
    ":ownerid" => $fromid,
    ":subject" => $subject,
    ":body" => $body
  )) == 0) {
    return array('errno' => 1, 'errm' => json_convert('ошибка при добавлении письма'));
  }
  
  //Права доступа на письмо (по умолчанию)
  SetDefaultPermissions('iris_email', $new_id, $fromid, $con);

  //Дадим доступ на чтение ответственному за контакта
  GetUserRecordPermissions('iris_email', $new_id, $ownerid, $contact_perm, $con);
  if ($contact_perm['r'] == 0) {
    $add_perm[] = array('userid' => $ownerid, 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
    ChangeRecordPermissions('iris_email', $new_id, $add_perm, $con);
  }

	return array('errno' => 0, 'errm' => 'ok');	
}

?>
