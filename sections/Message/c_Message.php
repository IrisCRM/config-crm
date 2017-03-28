<?php

namespace Iris\Config\CRM\sections\Message;

use Config;
use PDO;

/**
 * Карточка сообщения
 */
class c_Message extends Config
{
	public function __construct($Loader)
	{
		parent::__construct($Loader, [
			'common/Lib/lib.php',
			'common/Lib/project.php',
            'common/Lib/access.php',
		]);
	}

	/**
	 * При сохранении карточки изменяет права доступа сообщения.
	 * Получатель может читать сообщение, а создатель только читать
	 */
	public function ChangeAccess($params)
	{
		$p_rec_id = $params['rec_id'];
		$con = $this->connection;
		list ($AuthorID, $RecipientID) =
			GetFieldValuesByID('Message', $p_rec_id, array('AutorID', 'RecipientID'), $con);

		$permissions[] = array(
			'userid' => $AuthorID,
			'roleid' => '',
			'r' => 1,
			'w' => 0,
			'd' => 0,
			'a' => 0
		);
		$permissions[] = array(
			'userid' => $RecipientID,
			'roleid' => '',
			'r' => 1,
			'w' => 0,
			'd' => 0,
			'a' => 0
		);
		$res = ChangeRecordPermissions('iris_Message', $p_rec_id, $permissions);
	}


	/**
	 * если пользователь = "кому", то установим статус сообщения в "прочитано"
	 * если пользователь = автор то его записывать в прочитавшие не будем
	 * если пользователь открыл карточку, то запомним что он ее читал
	 */
	public function SaveReaded($params)
	{
		$p_rec_id = $params['rec_id'];
		$con = $this->connection;
		$user_id = GetUserID($con);

		$query = $con->prepare("select autorid, recipientid from iris_message where id=:id");
		$query->execute(array(":id" => $p_rec_id));
		$message_res = $query->fetchAll(PDO::FETCH_ASSOC);

		// если пользователь = "кому", то установим статус сообщения в "прочитано"
		if ($message_res[0]['recipientid'] == $user_id) {
			$upd_query = $con->prepare("update iris_message set StatusID=(select id from iris_messagestatus where code='Readed') where id=:id");
			$upd_query->execute(array(":id" => $p_rec_id));

		}

		// если пользователь = автор то его записывать в прочитавшие не будем
		if ($message_res[0]['autorid'] == $user_id) {
			return;
		}

		// найдем, есть ли запись о том, что данный пользователь уже прочитал сообщение
		$ex_query = $con->prepare("select 1 from iris_message_contact where messageid=:messageid and contactid=:contactid");
		$ex_query->execute(array(":messageid" => $p_rec_id, ":contactid" => $user_id));
		$ex_query_res = $ex_query->fetchAll();

		// если записи еще нет, то вставим ее
		if (empty($ex_query_res[0][0]) || $ex_query_res[0][0] != 1) {
			$ins_query = $con->prepare("insert into iris_message_contact (id, messageid, contactid, readdate) values (:id, :messageid, :contactid, now())");
			$ins_query->execute(array(":id" =>  create_guid(), ":messageid" => $p_rec_id, ":contactid" => $user_id));
		}
		return array("success" => 1);
	}


	/**
	 * Получить получателя из проекта
	 */
	public function GetRecipientFromProject($params)
	{
		$record_id = $params['record_id'];
		$p_user_id = $params['user_id'];
		$p_user_type = $params['user_type'];

		//Возьмем из проекта Контакта и Ответственного
		$result = GetLinkedValuesDetailed('iris_Project', $record_id, array(
			array('Field' => 'OwnerID',
				  'GetField' => 'Name',
				  'GetTable' => 'iris_Contact'),
			array('Field' => 'ContactID',
				  'GetField' => 'Name',
				  'GetTable' => 'iris_Contact')
		));

		$ContactID = GetArrayValueByParameter($result['FieldValues'], 'Name', 'ContactID', 'Value');

		//Если пользователь не клиент, то Кому - клиент, иначе - ответственный
		if ($ContactID != $p_user_id) {
			$result['FieldValues'][0] = $result['FieldValues'][1];
		}
		unset($result['FieldValues'][1]);
		$result['FieldValues'][0]['Name'] = 'RecipientID';

		return $result;
	}

	/**
	 * Получить получателя из решения
	 */
	public function GetRecipientFromAnswer($params)
	{
		$record_id = $params['record_id'];
		$p_user_id = $params['user_id'];
		$p_user_type = $params['user_type'];

		//Возьмем из проекта Контакта и Ответственного
		$result = GetLinkedValuesDetailed('iris_Answer', $record_id, array(
			array('Field' => 'OwnerID',
				  'GetField' => 'Name',
				  'GetTable' => 'iris_Contact')//,
		));

		$ContactID = GetArrayValueByParameter($result['FieldValues'], 'Name', 'OwnerID', 'Value');

		//Если пользователь не клиент, то Кому - клиент, иначе - ответственный
		if ($ContactID == $p_user_id) {
			$result['FieldValues'][0]['Value'] = null;
		}
		$result['FieldValues'][0]['Name'] = 'RecipientID';

		return $result;
	}

	/**
	 * Получить получателя из замечания
	 */
	public function GetRecipientFromBug($params)
	{
		$record_id = $params['record_id'];
		$p_user_id = $params['user_id'];
		$p_user_type = $params['user_type'];

		//Возьмем из проекта Контакта и Ответственного
		$result = GetLinkedValuesDetailed('iris_Bug', $record_id, array(
			array('Field' => 'OwnerID',
				  'GetField' => 'Name',
				  'GetTable' => 'iris_Contact'),
			array('Field' => 'FindID',
				  'GetField' => 'Name',
				  'GetTable' => 'iris_Contact')
		));

		$ContactID = GetArrayValueByParameter($result['FieldValues'], 'Name', 'FindID', 'Value');

		//Если пользователь не клиент, то Кому - клиент, иначе - ответственный
		if ($ContactID == $p_user_id) {
			$result['FieldValues'][0] = $result['FieldValues'][1];
		}
		unset($result['FieldValues'][1]);
		$result['FieldValues'][0]['Name'] = 'RecipientID';

		return $result;
	}

	/**
	 * Получить получателя из контакта
	 */
	public function GetRecipientFromContact($params)
	{
		$record_id = $params['record_id'];
		$p_user_id = $params['user_id'];
		$p_user_type = $params['user_type'];

		//Возьмем Ответственного контакта
		$result = GetLinkedValuesDetailed('iris_Contact', $record_id, array(
			array('Field' => 'ID',
				  'GetField' => 'Name',
				  'GetTable' => 'iris_Contact')
		));

		$result['FieldValues'][0]['Name'] = 'RecipientID';

		return $result;
	}

	public function GenerateNewOwner($params)
	{
		return GenerateNewOwner($this->connection);
	}

	/**
	 * Получатель = ответсвенный за клиента или ответсвенный за последний активный заказ
	 */
	public function GetMessageOwner($params)
	{
		$p_user_id = $params['user_id'];
		$con = $this->connection;
		$user_id = $p_user_id;

		//Получим последний проекта пользователя
		$result = $this->SetProject(['client_id' => $user_id]);
		$project_id = GetArrayValueByParameter($result['FieldValues'], 'Name', 'ProjectID', 'Value');

		//Если есть проект, то возьмем из проекта Ответственного
		if (!IsEmptyValue($project_id)) {
			$result = GetLinkedValuesDetailed('iris_Project', $project_id, array(
				array('Field' => 'OwnerID',
					  'GetField' => 'Name',
					  'GetTable' => 'iris_Contact')
			), $con, $result);
		}
		//Иначе возьмем Ответственного за текущего пользователя
		else {
			$result = GetLinkedValuesDetailed('iris_Contact', $user_id, array(
				array('Field' => 'OwnerID',
					  'GetField' => 'Name',
					  'GetTable' => 'iris_Contact')
			), $con, $result);
		}

		//Заменим OwnerID на Recipient (Кому)
		$result['FieldValues'][1]['Name'] = 'RecipientID';
		return $result;
	}

	/**
	 * Последний активный заказ
	 */
	public function SetProject($params)
	{
		return GetRecentProject($params['client_id'], $this->connection);
	}

	/**
	 * Значения полей при ответе
	 */
	public function GetReplyFields($params)
	{
		$p_message_id = $params['message_id'];
		$con = $this->connection;
		$sql  = "select T0.subject as subject, T1.id as project_id, T1.name as project_name, T2.id as product_id, T2.name as product_name, T3.id as recipient_id, T3.name as recipient_name from iris_message T0";
		$sql .= " left join iris_project T1 on T0.projectid=T1.id";
		$sql .= " left join iris_product T2 on T0.productid=T2.id";
		$sql .= " left join iris_contact T3 on T0.autorid=T3.id";
		$sql .= " where T0.id=:id";
		$query = $con->prepare($sql);
		$query->execute(array(":id" => $p_message_id));
		$query_res = $query->fetchAll(PDO::FETCH_ASSOC);
		$query_res = $query_res[0]; // перейдем на первую строку

		$query_res['subject'] = json_convert($query_res['subject']);
		$query_res['project_name'] = json_convert($query_res['project_name']);
		$query_res['product_name'] = json_convert($query_res['product_name']);
		$query_res['recipient_name'] = json_convert($query_res['recipient_name']);

		return $query_res;
	}

}
