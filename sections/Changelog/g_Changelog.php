<?php

namespace Iris\Config\CRM\sections\Changelog;

use Config;
use Iris\Iris;

/**
 * История изменений таблица
 */
class g_Changelog extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
        ));
    }

    public function switchMonitoring($params)
    {
        $recordId = $params['recordId'];
        $gridId = $params['gridId'];
        $mode = $params['mode'];
        $instanceName = $params['instanceName'];

        // если первый раз, то просто нарисуем чекбокс
        if ($mode == 'init') {
            return $this->drawControl($recordId, $gridId, $instanceName);
        }

        $con = db_connect();
        $monitor_info = $this->getMonitorInfo($recordId);
        if ($monitor_info['monitorstartdate'] == '') {
            $sql = $con->prepare("insert into iris_changelogmonitor (id, userid, recordid, monitorstartdate, ownerid) values (:id, :userid, :recordid, "._db_current_datetime().", :ownerid)");
            $newid = create_guid();
            $sql->execute(array(":id" => $newid, ":userid" => GetUserID($con), ":recordid" => $recordId, ":ownerid" => GetUserID($con)));
        } else {
            $sql = $con->prepare("delete from iris_changelogmonitor where id=:id");
            $sql->execute(array(":id" => $monitor_info['id']));
        }

        return $this->drawControl($recordId, $gridId, $instanceName);
    }

    public function drawControl($recordId, $gridId, $instanceName) {
        $result = array();

        $html_template = '<table class="changelog_control"><tr><td class="changelog_cb">#c1#</td><td class="changelog_label">#c2#</td></tr></table>';
        $random_id = 'changelog_'.rand();

        $monitor_info = $this->getMonitorInfo($recordId);
        if ($monitor_info['monitorstartdate'] == '') {
            $checked = '';
            $attr = 'date_str=""';
            $add_caption = '';
        } else {
            $checked = ' checked';
            $attr = ' date_str="'.$monitor_info['monitorstartdate'].'"';
            $add_caption = ' (отслеживаются с '.$monitor_info['monitorstartdate'].')';
        }
        $html_template = iris_str_replace('#c1#', '<input id="'.$random_id.'" '.$attr.' grid_id="'.$gridId.'" type="checkbox" onclick="' . $instanceName . '.switchMonitoring('.chr(39).$gridId.chr(39).', '.chr(39).'switch'.chr(39).', this) "'.$checked.'>', $html_template);
        $html_template = iris_str_replace('#c2#', '<label for="'.$random_id.'">Следить за историей'.$add_caption.'</label>', $html_template);

        $result['html'] = json_convert($html_template);

        return $result;
    }

    public function getMonitorInfo($recordId) {
        $con = $this->connection;
        $sql = $con->prepare("select "._db_datetime_to_string('monitorstartdate')." as monitorstartdate, id as id from iris_changelogmonitor where userid=:userid and recordid=:recordid");
        $sql->execute(array(":userid" => GetUserID($con), ":recordid" => $recordId));
        return current($sql->fetchAll());
    }
}
