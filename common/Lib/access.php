<?php
/*
--------------------------------------------------------------------------------------------------------
Функции для удобной работы с правами доступа. позволяют добавлять и модифицировать права доступа [1.0.2]
последнее изменение: miv 08.09.2010 (добавлена функции record_chown)
--------------------------------------------------------------------------------------------------------

ChangeRecordPermissions 	- "применяет" указанные права доступа
GetRecordPermissions		- возвращает текущие права доступа для записи
GetDefaultRecordPermissions - возвращает права доступа по умолчанию для записи (для пользователя, создавшему запись даются все права на запись)
SetDefaultPermissions		- устанавливает для записи права по умолчанию
DeleteAllRecordPermissions	- удаляет все права доступа у записи

Пример:
$permissions[] = array('userid' => $GLOBALS["g_contact_id"], 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
$res = ChangeRecordPermissions('iris_message', $GLOBALS["g_message_id"], $permissions);

*/

// изменяет существующие права доступа для записи в таблице p_table_name с id=p_record_id 
// если в p_record_id указана строка, то для изменятся права одной записи, если в p_record_id указан массив строк, то изменются права нескольких записей, id которых являются элементаим массива p_record_id
// permissions: ассоциативный массив с ключами [userid roleid r w d a]
function ChangeRecordPermissions($p_table_name, $p_record_id, &$p_permissions, $p_conn = null) {
	
	if (is_array($p_permissions) == false) {
		return 1; // если массив не передан, то выйдем
	}

	$i = 0;
	foreach ($p_permissions as $row) {
		// если указана пустая строка, то преобразуем значение в null (null нужно в bindparam)
		if ($p_permissions[$i]['userid'] == '') $p_permissions[$i]['userid'] = null;
		if ($p_permissions[$i]['roleid'] == '') $p_permissions[$i]['roleid'] = null;
		$i++;

		if (($row['userid'] == null and $row['roleid'] == null) or ($row['userid'] != null and $row['roleid'] != null)) {
			return 1; // нельзя одновременно задавать userid и roleid
		}
		if ((!(($row['r'] == 0) or ($row['r'] == 1))) or (!(($row['w'] == 0) or ($row['w'] == 1))) or (!(($row['d'] == 0) or ($row['d'] == 1))) or (!(($row['a'] == 0) or ($row['a'] == 1)))) {
			return 1; // r w d a должны быть 1 или 0
		}
	}

	// если указали таблицу прав доступа то вернем ошибку
	if (substr($p_table_name, -7) == '_access') {
		return 1;
	}

	if (is_array($p_record_id) == false) {
		// преобразуем в массив id и бегаем по нему и устанавливаем права
		$p_record_id = array($p_record_id);
	}

	foreach ($p_record_id as $id) {
		// если id не соответствуют guid, то вернем ошибку
		if (strlen($id) < 36) {
			return 1;
		}
	}
	
	if (!$p_conn) {
		$p_conn = db_connect();
	}

	$error_flag = 0;
	// проходим по id тех записей, для которых нужно поменять права доступа
	foreach ($p_record_id as $current_id) {
		// проходим по каждой строке массива и делаем или update или insert. если новые права 0000 то удаляем старую строку
		foreach ($p_permissions as $perm) {
			$perm_delete_flag = 0;
			$perm_update_flag = 0;
			$perm_insert_flag = 0;

			// получаем id записи существующих прав	
			$exists_sql = "select id from ".$p_table_name."_access where recordid=:recordid and (accessroleid=:accessroleid or contactid=:contactid)";
			$ex_query = $p_conn->prepare($exists_sql);
			$ex_query->bindParam(":recordid", $current_id); 
			$ex_query->bindParam(":accessroleid", $perm['roleid']); 
			$ex_query->bindParam(":contactid", $perm['userid']); 
			if (!($ex_query->execute())) {
				$error_flag = 1;
				continue;
			}
			$ex_res = $ex_query->fetchAll();
			// id строки таблицы прав доступа, содержащая права на текущую запись. если пусто, то записи нет и нужно ее вставить
			$permission_id = $ex_res ? $ex_res[0][0] : null;
			
			if ($permission_id != '') {
				// если запись уже есть, то обновим права для нее
				if (($perm['r'] == 0) and ($perm['w'] == 0) and ($perm['d'] == 0) and ($perm['a'] == 0)) {
					$acess_command = "delete from ".$p_table_name."_access where id=:id";
					$perm_delete_flag = 1;
				}
				else {
					$acess_command = "update ".$p_table_name."_access set r=:r, w=:w, d=:d, a=:a where recordid=:recordid and ((accessroleid=:accessroleid and accessroleid is not null) or (contactid=:contactid and contactid is not null))";
					$perm_update_flag = 1;
				}
			}
			else {
				// иначе вставим новые права
				if (($perm['r'] == 0) and ($perm['w'] == 0) and ($perm['d'] == 0) and ($perm['a'] == 0)) {
					continue; // если требуется вставить пустые права, вставлять не будем
				}
				$acess_command = "insert into ".$p_table_name."_access (id, recordid, accessroleid, contactid, r, w, d, a) values (:id, :recordid, :accessroleid, :contactid, :r, :w, :d, :a)";
				$perm_insert_flag = 1;
			}

			$perm_query = $p_conn->prepare($acess_command);
			
			if ($perm_delete_flag == 1) {
				$perm_query->bindParam(":id", $permission_id); // если удаление, то id - это id существующей записи с правами
			}
			if ($perm_insert_flag == 1) {
				$guid = create_guid();
				$perm_query->bindParam(":id", $guid); // если вставка, то id - это новый сгенерированый guid
			}
			if (($perm_insert_flag == 1) or ($perm_update_flag == 1)) {
				$perm_query->bindParam(":recordid", $current_id);
				$perm_query->bindParam(":accessroleid", $perm['roleid']);
				$perm_query->bindParam(":contactid", $perm['userid']);
				$perm_query->bindParam(":r", $perm['r']);
				$perm_query->bindParam(":w", $perm['w']);
				$perm_query->bindParam(":d", $perm['d']);
				$perm_query->bindParam(":a", $perm['a']);
			}
			$perm_query->execute();
			$error_info = $perm_query->errorInfo();
		}
	}
	return 0;
}

// в массив p_permissions помещает текущие права для записи с id p_record_id
// permissions: ассоциативный массив с ключами [userid roleid r w d a]
function GetRecordPermissions($p_table_name, $p_record_id, &$p_permissions, $p_conn = null) {
	$p_permissions = null;
	if (substr($p_table_name, -7) == '_access')
		return 1; // если указали таблицу прав доступа то вернем ошибку

	if (!$p_conn) {
		$p_conn = db_connect();
	}
		
	$select_sql = "select accessroleid as roleid, contactid as userid, r as r, w as w, d as d, a as a from ".$p_table_name."_access where recordid=:recordid";
	$query = $p_conn->prepare($select_sql);
	$query->bindParam(":recordid", $p_record_id); 
	if (!($query->execute())) {
		return 1; // если запрос неудачен, то вернем ошибку
	}
	$p_permissions = $query->fetchAll(PDO::FETCH_ASSOC);
	return 0;	
}

// в массив p_permissions помещает права по умолчанию (права, проставляющиеся при создании записи) для записи, id создающего=p_user_id
// permissions: ассоциативный массив с ключами [userid roleid r w d a]
function GetDefaultRecordPermissions($p_table_name, $p_user_id, &$p_permissions, $p_conn = null) {
	$p_permissions = null;
	if (substr($p_table_name, -7) == '_access')
		return 1; // если указали таблицу прав доступа то вернем ошибку

	if (!$p_conn) {
		$p_conn = db_connect();
	}

	// по id пользователя получаем id его роли
	// TODO: сделать универсально, чтобы имя таблицы бралось из $l_xml->LOGIN->TABLE['name']
	$role_sql = "select accessroleid from iris_contact where id=:userid";
	$role_query = $p_conn->prepare($role_sql);
	$role_query->bindParam(":userid", $p_user_id);
	$role_query->execute();
	$role_query_res = $role_query->fetchAll();
	$role_id = $role_query_res[0][0];

	$def_access_sql = "select accessroleid as roleid, null as userid, r as r, w as w, d as d, a as a from iris_table_accessdefault T0 left join iris_table T1 on T0.TableID = T1.ID where T1.Code = :table and T0.CreatorRoleID = :roleid";
	$def_access_query = $p_conn->prepare($def_access_sql);
	$def_access_query->bindParam(":table", $p_table_name);
	$def_access_query->bindParam(":roleid", $role_id);
	if (!($def_access_query->execute()))
		return 1;

	$p_permissions = $def_access_query->fetchAll(PDO::FETCH_ASSOC);
	$p_permissions[] = array('userid' => $p_user_id, 'roleid' => '', 'r' => 1, 'w' => 1, 'd' => 1, 'a' => 1); // даем права пользователю, создавшему запись
	return 0;	
}

// применяет права по умолчнию для записи с id=p_record_id и id создающего=p_user_id
function SetDefaultPermissions($p_table_name, $p_record_id, $p_user_id, $p_conn = null) {
	$permissions = null;
	$res = GetDefaultRecordPermissions($p_table_name, $p_user_id, $permissions, $p_conn); // получили права по умолчанию
	if ($res == 1)
		return 1;
		
	if (DeleteAllRecordPermissions($p_table_name, $p_record_id, $p_conn) == 1)
		return 1;

	return ChangeRecordPermissions($p_table_name, $p_record_id, $permissions, $p_conn); // применили права к записи (записям)
}

// удаляет все права у записи с id=p_record_id
function DeleteAllRecordPermissions($p_table_name, $p_record_id, $p_conn = null) {
	if (substr($p_table_name, -7) == '_access')
		return 1; // если указали таблицу прав доступа то вернем ошибку

	if (!$p_conn) {
		$p_conn = db_connect();
	}

	$delete_sql = "delete from ".$p_table_name."_access where recordid=:recordid";
	$del_query = $p_conn->prepare($delete_sql);
	$del_query->bindParam(":recordid", $p_record_id);
	if (!($del_query->execute()))
		return 1;

	return 0;
}

// в массив p_permissions помещает права пользователя p_user_id для записи с id p_record_id
// если пользователь в группе администраторов, то вернет все права
// permissions: ассоциативный массив с ключами [r w d a]
function GetUserRecordPermissions($p_table_name, $p_record_id, $p_user_id, &$p_permissions, $p_conn = null) {
	$p_permissions = null;
	if (substr($p_table_name, -7) == '_access')
		return 1; // если указали таблицу прав доступа то вернем ошибку

	if (!$p_conn) {
		$p_conn = db_connect();
	}
		
	$access_cmd = $p_conn->prepare("select T1.id as id, T1.code as code from iris_contact T0 left join iris_accessrole T1 on T0.accessroleid = T1.id where T0.id=:id");
	$access_cmd->execute(array(":id" => $p_user_id));
	$access = current($access_cmd->fetchAll(PDO::FETCH_ASSOC));
	if ($access['code'] == 'admin') {
		$p_permissions = array('r' => 1, 'w' => 1, 'd' => 1, 'a' => 1);
		return 0;
	}	
		
	$select_sql = "select accessroleid as roleid, contactid as userid, r as r, w as w, d as d, a as a from ".$p_table_name."_access where recordid=:recordid and (accessroleid=:accessroleid or contactid=:contactid) order by contactid";
	$query = $p_conn->prepare($select_sql);
	if (!($query->execute(array(":recordid" => $p_record_id, ":accessroleid" => $access['id'], ":contactid" => $p_user_id)))) {
		return 1; // если запрос неудачен, то вернем ошибку
	}
	$query_res = $query->fetchAll(PDO::FETCH_ASSOC);

	if (count($query_res) == 0) {
		// если нет записей о правах доступа, то вернем нули
		$p_permissions = array('r' => 0, 'w' => 0, 'd' => 0, 'a' => 0);
	} else {
		// так как сортируем по contactid, то в любом случае, 1 или 2 записи вернулось, сверху будут приоритетные права доступа
		$p_permissions = array('r' => $query_res[0]['r'], 'w' => $query_res[0]['w'], 'd' => $query_res[0]['d'], 'a' => $query_res[0]['a']);
	}
	return 0;	
}

// в массив p_permissions помещает права текущего пользователя для записи с id p_record_id
// если пользователь в группе администраторов, то вернет все права
// permissions: ассоциативный массив с ключами [r w d a]
function GetCurrentUserRecordPermissions($p_table_name, $p_record_id, &$p_permissions, $p_conn = null) {
	if (!$p_conn) {
		$p_conn = db_connect();
	}
		
	if (GetUserRecordPermissions($p_table_name, $p_record_id, GetUserID($p_conn), $p_permissions, $p_conn) == 0) {
		return 0;
	}
	return 1;
}

// функция для изменения доступа у записи, при смене ответсвенного
// Если $p_new_owner_id не имеет доступ на чтение и запись данной записи, то он его получает
function record_chown($p_table, $p_id, $p_old_owner_id, $p_new_owner_id, $p_flags) {
	$con = db_connect();
	$cmd = $con->prepare("select id, name, accessroleid from iris_contact where id=:id");
	$cmd->execute(array(":id" => $p_new_owner_id));
	$contact = current($cmd->fetchAll(PDO::FETCH_ASSOC));

	if ($contact['accessroleid'] == '')
		return; // если это не пользователь системы, то не назачаем права
		
	GetUserRecordPermissions($p_table, $p_id, $p_new_owner_id, $user_perm, $con);
	if (($user_perm['r'] == 1) and ($user_perm['w'] == 1))
		return; // если пользователь уже имеет данные права, то выйдем
		
	$permissions[] = array('userid' => $p_new_owner_id, 'roleid' => '', 'r' => 1, 'w' => 1, 'd' => 0, 'a' => 0);
	$res = ChangeRecordPermissions($p_table, $p_id, $permissions);
	
	if (($res == 0) and ($p_flags['showMessage'] == 1))
		return array('Message' => '"'.$contact['name'].'" добавлен в доступ к записи');
}

?>