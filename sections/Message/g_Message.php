<?php

namespace Iris\Config\CRM\sections\Message;

use Config;
use PDO;

class g_Message extends Config
{
	public function __construct($Loader)
	{
		parent::__construct($Loader, [
			'common/Lib/lib.php',
            'common/Lib/report.php',
			'common/Lib/access.php',
		]);
	}

    function highlightNewMessages($params) {
        $p_ids = $params['ids'];
        $result = null;
        $ids_arr = json_decode($p_ids, true);
        $id_str = '';
        $pattern = ".[а-яА-я\\.,!@#$%\\^&\\*() ~`_+\\\\\\[\\]\\{\\}]."; // недопустимые символы
        foreach ($ids_arr as $id) {
            if (iris_preg_match($pattern, $id))
                return null;
            $id_str .= ", '".$id."'";
        }
        $id_str = substr($id_str, 2);

        // сообщения новые, если пользователь не их автор и его нет в списке прочитавших
        $sql  = "select T0.id as msg_id, T0.autorid, T0.message, T1.contactid ";
        $sql .= "from iris_message T0 ";
        $sql .= "left join iris_message_contact T1 on T0.ID = T1.messageid and T1.contactid = :user_id ";
        $sql .= "where T0.id in (".$id_str.") ";
        $sql .= "  and T0.autorid <> :user_id ";
        $sql .= "  and T1.contactid is null";
        $con = db_connect();
        $query = $con->prepare($sql);
        $query->execute(array(":user_id" => GetUserID($con)));
        $query_res = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($query_res as $rec) {
            $result[] = $rec['msg_id'];
        }

        return json_encode($result);
    }
}