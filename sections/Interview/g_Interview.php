<?php

namespace Iris\Config\CRM\sections\Interview;

use Config;
use PDO;

/**
 * Раздел "Интервью" (вкладка в разделе "Опросы"). Таблица
 */
class g_Interview extends Config
{
	public function __construct($Loader)
	{
		parent::__construct($Loader, [
			'common/Lib/lib.php',
            'common/Lib/report.php',
			'common/Lib/access.php',
		]);
	}

	function addFromReport($params)
	{
		$p_reportid = isset($params['report_id']) ? $params['report_id'] : null;
		$p_reportcode = $params['report_code'];
		$p_filters = $params['filters'];
		$p_pollid = $params['poll_id'];

		if ($p_reportcode != '') {
			$con = db_connect();
			$cmd = $con->prepare("select id from iris_report where code=:code");
			$cmd->execute([":code" => $p_reportcode]);
			$res = $cmd->fetchAll(PDO::FETCH_ASSOC);
			$p_reportid = $res[0]['id'];
		}

		$p_filters = json_decode(stripslashes($p_filters));

		//Подготовка отчета
		list($sql, $params, $show_info) = BuildReportSQL($p_reportid, $p_filters);
		$report_info = GetReportInfo($p_reportid);
		list($contacts, $tmp) = BuildReportData($show_info, $sql, $params);
		if (count($contacts) == 0) {
			return ["errno" => 2, "errm" => json_convert('Не найдено контактов, удовлетворяющих условиям поиска')];
		}


		$sql = '';
		$con = db_connect();

		list ($userid, $username) = GetShortUserInfo(GetUserName(), $con);

		$cmd = $con->prepare("select id from iris_interview where pollid = :pollid and contactid = :contactid");
		foreach ($contacts as $contact) {
			$cmd->execute([
				":pollid"    => $p_pollid,
				":contactid" => $contact['id']
			]);
			$exists_id = current($cmd->fetchAll(PDO::FETCH_ASSOC));

			// если этого контакта еще нет в списке рассылки, то добавим его
			if ($exists_id == '') {
				$sql .= "insert into iris_interview (id, pollid, contactid, phone, phoneaddl, accountid, InterviewStateID, ownerid," .
					"createdate, modifydate, createid, modifyid) " .
					"values (iris_genguid(), '" . $p_pollid . "', '" . $contact['id'] . "', " .
					"(select (case length(phone2)>5 when true then phone2 else phone1 end) from iris_contact where id='" . $contact['id'] . "'), " .
					"(select (case length(phone2)>5 when true then '' else Phone1addl end) from iris_contact where id='" . $contact['id'] . "'), " .
					"(select accountid from iris_contact where id='" . $contact['id'] . "'), " .
					"(select id from iris_interviewstate where code='plan'), " .
					"'" . $userid . "', " .
					"now(), now(), '" . $userid . "', '" . $userid . "' " .
					"); " . chr(10);
				//TODO: назначать права при добавлении записей
			}
		}
		if ($sql == '') {
			return ["errno" => 3, "errm" => json_convert('Данные контакты уже добавлены в опрос')];
		}

		if ($con->exec($sql) == 0) {
			return ["errno" => 11, "errm" => json_convert('Не удалось добавить контактные лица в опрос')];
		}

		return ["errno" => 0, "errm" => ''];
	}
}