<?php

namespace Iris\Config\CRM\sections\Report;

use Config;
use Iris\Iris;
use PDO;

/**
 * Таблица проекта
 */
class g_Report extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/report.php',
        ));
    }

    /**
     * Преобразовывает guid p_quid_value по маске p_guid_token
     */
    protected function _convGUID($p_quid_value, $p_guid_token) {
        $new_guid_md5 = strtolower(md5($p_quid_value . $p_guid_token));
        $hyphen = chr(45);
        $uuid = substr($new_guid_md5, 0, 8) . $hyphen 
                . substr($new_guid_md5, 8, 4) . $hyphen 
                . substr($new_guid_md5, 12, 4) . $hyphen
                . substr($new_guid_md5, 16, 4) . $hyphen
                . substr($new_guid_md5, 20,12);
        return $uuid;
    }

    /**
     * Фильтры для построения отчёта
     */
    public function getFilters($params)
    {
        list($response, $filters) = 
            Report_GetFilters($params['report_id'], $params['report_code']);
        return $response;
    }

    /**
     * Копирует указанный отчет
     */
    public function copyReport($params) 
    {
        $con = $this->connection;
        $p_report_id = $params['id'];

        if (IsUserInAdminGroup($con) == 0) {
            return array("success" => 0, "message" => json_convert('Данная функция доступна только администраторам'));
        }

        $guid_token = create_guid(); // для преобразования ссылок на сущности старого отчета

        // 1. отчет
        $rep_data_sql = "select id, name || ' (Копия)' as name, ".
            "case when code <> '' then code || '-copy' else null end as code, ".
            "recordcount, graphtype, xalias, xreport_tableid, xcolumnid, ".
            "showzero, showaxis, showlabels, graphwidth, graphheight, colorschemeid, ".
            "sql, description, tabletype, tablecardrows, tablecardcols, width, ".
            "beforeshowscript, beforeshowfunction, aftershowscript, aftershowfunction, isdistinct ".
            "from iris_report where id=:id";
        $rep_data_cmd = $con->prepare($rep_data_sql);
        $rep_data_cmd->execute(array(":id" => $p_report_id));
        $rep_data = current($rep_data_cmd->FetchAll(PDO::FETCH_ASSOC));

        $rep_ins_sql  = 'insert into iris_report (id, name, code, '.
            'recordcount, graphtype, xalias, xreport_tableid, xcolumnid, '.
            'showzero, showaxis, showlabels, graphwidth, graphheight, colorschemeid, '.
            'sql, description, tabletype, tablecardrows, tablecardcols, width, '.
            'beforeshowscript, beforeshowfunction, aftershowscript, aftershowfunction, isdistinct) ';
        $rep_ins_sql .= 'values (#id#, #name#, #code#, #recordcount#, #graphtype#, '.
            '#xalias#, #xreport_tableid#, #xcolumnid#, '.
            '#showzero#, #showaxis#, #showlabels#, #graphwidth#, #graphheight#, #colorschemeid#, '.
            '#sql#, #description#, #tabletype#, #tablecardrows#, #tablecardcols#, #width#, '.
            '#beforeshowscript#, #beforeshowfunction#, #aftershowscript#, #aftershowfunction#, #isdistinct#);'.chr(10);
        $rep_ins_query = iris_str_replace(
            array('#id#', '#name#', '#code#', '#recordcount#', '#graphtype#', '#xalias#', 
                '#xreport_tableid#', '#xcolumnid#', 
                '#showzero#', '#showaxis#', '#showlabels#', '#graphwidth#', '#graphheight#', '#colorschemeid#', 
                '#sql#', '#description#', '#tabletype#', '#tablecardrows#', '#tablecardcols#', '#width#',
                '#beforeshowscript#', '#beforeshowfunction#', '#aftershowscript#', '#aftershowfunction#', '#isdistinct#'), 
            array(
                "'".$this->_convGUID($rep_data['id'], $guid_token)."'",
                "'".$rep_data['name']."'",
                "'".$rep_data['code']."'",
                ($rep_data['recordcount'] == '') ? 'null' : $rep_data['recordcount'],
                ($rep_data['graphtype'] == '') ? 'null' : "'".$rep_data['graphtype']."'",
                "'".$rep_data['xalias']."'",
                ($rep_data['xreport_tableid'] == '') ? 'null' : "'".$rep_data['xreport_tableid']."'",
                ($rep_data['xcolumnid'] == '') ? 'null' : "'".$rep_data['xcolumnid']."'",
                ($rep_data['showzero'] == '') ? 'null' : "'".$rep_data['showzero']."'",
                ($rep_data['showaxis'] == '') ? 'null' : "'".$rep_data['showaxis']."'",
                ($rep_data['showlabels'] == '') ? 'null' : "'".$rep_data['showlabels']."'",
                ($rep_data['graphwidth'] == '') ? 'null' : "'".$rep_data['graphwidth']."'",
                ($rep_data['graphheight'] == '') ? 'null' : "'".$rep_data['graphheight']."'",
                ($rep_data['colorschemeid'] == '') ? 'null' : "'".$rep_data['colorschemeid']."'",
                '$x$'.$rep_data['sql'].'$x$',
                '$x$'.$rep_data['description'].'$x$',
                ($rep_data['tabletype'] == '') ? 'null' : "'".$rep_data['tabletype']."'",
                ($rep_data['tablecardrows'] == '') ? 'null' : $rep_data['tablecardrows'],
                ($rep_data['tablecardcols'] == '') ? 'null' : $rep_data['tablecardcols'],
                ($rep_data['width'] == '') ? 'null' : $rep_data['width'],
                ($rep_data['beforeshowscript'] == '') ? 'null' : "'".$rep_data['beforeshowscript']."'",
                ($rep_data['beforeshowfunction'] == '') ? 'null' : "'".$rep_data['beforeshowfunction']."'",
                ($rep_data['aftershowscript'] == '') ? 'null' : "'".$rep_data['aftershowscript']."'",
                ($rep_data['aftershowfunction'] == '') ? 'null' : "'".$rep_data['aftershowfunction']."'",
                ($rep_data['isdistinct'] == '') ? 'null' : $rep_data['isdistinct'],
            ), 
            $rep_ins_sql
        );
        //echo $rep_ins_query;

        // 2. права доступа к отчету
        $rac_data_sql = "select id, recordid, accessroleid, contactid, r, w, d, a from iris_report_access where recordid = :id";
        $rac_data_cmd = $con->prepare($rac_data_sql);
        $rac_data_cmd->execute(array(":id" => $p_report_id));
        $rac_data = $rac_data_cmd->FetchAll(PDO::FETCH_ASSOC);
        $rac_ins_sql  = 'insert into iris_report_access (id, recordid, accessroleid, contactid, r, w, d, a) ';
        $rac_ins_sql .= 'values (#id, #recordid#, #accessroleid#, #contactid#, #r#, #w#, #d#, #a#);'.chr(10);
        foreach ($rac_data as $rac_val) {
            $rac_ins_query .= iris_str_replace(
                array('#id', '#recordid#', '#accessroleid#', '#contactid#', '#r#', '#w#', '#d#', '#a#'), 
                array(
                    "'".$this->_convGUID($rac_val['id'], $guid_token)."'",
                    "'".$this->_convGUID($rac_val['recordid'], $guid_token)."'",
                    ($rac_val['accessroleid'] == '') ? 'null' : "'".$rac_val['accessroleid']."'",
                    ($rac_val['contactid'] == '') ? 'null' : "'".$rac_val['contactid']."'",
                    "'".$rac_val['r']."'",
                    "'".$rac_val['w']."'",
                    "'".$rac_val['d']."'",
                    "'".$rac_val['a']."'",
                ), 
                $rac_ins_sql
            );
        }
        //echo $rac_ins_query;        

        // 3. таблицы отчета
        $rtb_data_sql = "select id, reportid, tableid, columnid, name, code, ".
            "parenttableid, parentcolumnid, description, orderpos, sql ".
            "from iris_report_table where reportid = :id";
        $rtb_data_cmd = $con->prepare($rtb_data_sql);
        $rtb_data_cmd->execute(array(":id" => $p_report_id));
        $rtb_data = $rtb_data_cmd->FetchAll(PDO::FETCH_ASSOC);
        $rtb_ins_sql  = 'insert into iris_report_table (id, reportid, tableid, columnid, name, code, '.
            'parenttableid, parentcolumnid, description, orderpos, sql) ';
        $rtb_ins_sql .= 'values (#id#, #reportid#, #tableid#, #columnid#, #name#, #code#, '.
            '#parenttableid#, #parentcolumnid#, #description#, #orderpos#, #sql#);'.chr(10);
        $rtb_upd_sql  = 'update iris_report_table set parenttableid = #parenttableid# where id = #id#;'.chr(10);
        foreach ($rtb_data as $rtb_val) {
            $rtb_ins_query .= iris_str_replace(
                array('#id#', '#reportid#', '#tableid#', '#columnid#', '#name#', '#code#', 
                    '#parenttableid#', '#parentcolumnid#', '#description#',
                    '#orderpos#', '#sql#'), 
                array(
                    "'".$this->_convGUID($rtb_val['id'], $guid_token)."'",
                    "'".$this->_convGUID($rtb_val['reportid'], $guid_token)."'",
                    ($rtb_val['tableid'] == '') ? 'null' : "'".$rtb_val['tableid']."'",
                    ($rtb_val['columnid'] == '') ? 'null' : "'".$rtb_val['columnid']."'",
                    "'".$rtb_val['name']."'",
                    "'".$rtb_val['code']."'",
                    //($rtb_val['parenttableid'] == '') ? 'null' : "'".$rtb_val['parenttableid']."'",
                    'null',
                    ($rtb_val['parentcolumnid'] == '') ? 'null' : "'".$rtb_val['parentcolumnid']."'",
                    '$x$'.$rtb_val['description'].'$x$',
                    ($rtb_val['orderpos'] == '') ? 'null' : $rtb_val['orderpos'],
                    '$x$'.$rtb_val['sql'].'$x$',
                ), 
                $rtb_ins_sql
            );

            if ($rtb_val['parenttableid'] == '') {
                continue;
            }
            $rtb_upd_query .= iris_str_replace(
                array('#id#', '#parenttableid#'),
                array(
                    "'".$this->_convGUID($rtb_val['id'], $guid_token)."'",
                    "'".$this->_convGUID($rtb_val['parenttableid'], $guid_token)."'",
                ),
                $rtb_upd_sql
            );        
        }
        //echo $rtb_ins_query.$rtb_upd_query;

        // 4. колонки отчета
        $rcl_data_sql = "select id, reportid, report_tableid, columnid, functionid, code, name, ".
            "number, showinreport, showingraph, columntypeid, ordernumber, groupnumber, ".
            "orderdirection, grouptype, totalid, sql, description, ".
            "linkedreportid, linkedparameter, linkedcolumnid ".
            "from iris_report_column where reportid = :id";
        $rcl_data_cmd = $con->prepare($rcl_data_sql);
        $rcl_data_cmd->execute(array(":id" => $p_report_id));
        $rcl_data = $rcl_data_cmd->FetchAll(PDO::FETCH_ASSOC);
        $rcl_ins_sql  = 'insert into iris_report_column (id, reportid, report_tableid, columnid, '.
            'functionid, code, name, '.
            'number, showinreport, showingraph, columntypeid, ordernumber, groupnumber, '.
            'orderdirection, grouptype, totalid, sql, description, '.
            'linkedreportid, linkedparameter, linkedcolumnid) ';
        $rcl_ins_sql .= 'values (#id#, #reportid#, #report_tableid#, #columnid#, '.
            '#functionid#, #code#, #name#, #number#, #showinreport#, #showingraph#, #columntypeid#, '.
            '#ordernumber#, #groupnumber#, #orderdirection#, #grouptype#, #totalid#, #sql#, #description#, '.
            '#linkedreportid#, #linkedparameter#, #linkedcolumnid#);'.chr(10);
        foreach ($rcl_data as $rcl_val) {
            $rcl_ins_query .= iris_str_replace(
                array('#id#', '#reportid#', '#report_tableid#', '#columnid#', '#functionid#', 
                    '#code#', '#name#', '#number#', '#showinreport#', '#showingraph#', 
                    '#columntypeid#', '#ordernumber#', '#groupnumber#', '#orderdirection#', '#grouptype#', 
                    '#totalid#', '#sql#', '#description#',
                    '#linkedreportid#', '#linkedparameter#', '#linkedcolumnid#'), 
                array(
                    "'".$this->_convGUID($rcl_val['id'], $guid_token)."'",
                    "'".$this->_convGUID($rcl_val['reportid'], $guid_token)."'",
                    //($rcl_val['report_tableid'] == '') ? 'null' : "'".$rcl_val['report_tableid']."'",
                    ($rcl_val['report_tableid'] == '') ? 'null' : "'".$this->_convGUID($rcl_val['report_tableid'], $guid_token)."'",
                    ($rcl_val['columnid'] == '') ? 'null' : "'".$rcl_val['columnid']."'",
                    ($rcl_val['functionid'] == '') ? 'null' : "'".$rcl_val['functionid']."'",
                    "'".$rcl_val['code']."'",
                    "'".$rcl_val['name']."'",
                    ($rcl_val['number'] == '') ? 'null' : $rcl_val['number'],
                    "'".$rcl_val['showinreport']."'",
                    "'".$rcl_val['showingraph']."'",
                    ($rcl_val['columntypeid'] == '') ? 'null' : "'".$rcl_val['columntypeid']."'",
                    ($rcl_val['ordernumber'] == '') ? 'null' : $rcl_val['ordernumber'],
                    ($rcl_val['groupnumber'] == '') ? 'null' : $rcl_val['groupnumber'],
                    ($rcl_val['orderdirection'] == '') ? 'null' : "'".$rcl_val['orderdirection']."'",
                    ($rcl_val['grouptype'] == '') ? 'null' : "'".$rcl_val['grouptype']."'",
                    ($rcl_val['totalid'] == '') ? 'null' : "'".$rcl_val['totalid']."'",
                    '$x$'.$rcl_val['sql'].'$x$',
                    '$x$'.$rcl_val['description'].'$x$',
                    ($rcl_val['linkedreportid'] == '') ? 'null' : "'".$rcl_val['linkedreportid']."'",
                    ($rcl_val['linkedparameter'] == '') ? 'null' : "'".$rcl_val['linkedparameter']."'",
                    ($rcl_val['linkedcolumnid'] == '') ? 'null' : "'".$rcl_val['linkedcolumnid']."'",
                ), 
                $rcl_ins_sql
            );
        }
        //echo $rcl_ins_query;

        // 5. фильтры отчета
        $rfl_data_sql = "select id, reportid, report_tableid, columnid, condition, ".
            "guidvalue, datevalue, floatvalue, stringvalue, intvalue, ".
            "name, code, isvisible, number, parentfilterid, logiccondition, ".
            "sql, droplistsql, description, equation ".
            "from iris_report_filter where reportid = :id";
        $rfl_data_cmd = $con->prepare($rfl_data_sql);
        $rfl_data_cmd->execute(array(":id" => $p_report_id));
        $rfl_data = $rfl_data_cmd->FetchAll(PDO::FETCH_ASSOC);
        $rfl_ins_sql  = 'insert into iris_report_filter ('.
            'id, reportid, report_tableid, columnid, condition, '.
            'guidvalue, datevalue, floatvalue, stringvalue, intvalue, '.
            'name, code, isvisible, number, logiccondition, '.
            'sql, droplistsql, description, equation) ';
        $rfl_ins_sql .= 'values (#id#, #reportid#, #report_tableid#, #columnid#, #condition#, '.
            '#guidvalue#, #datevalue#, #floatvalue#, #stringvalue#, #intvalue#, '.
            '#name#, #code#, #isvisible#, #number#, #logiccondition#, '.
            '#sql#, #droplistsql#, #description#, #equation#);'.chr(10);
        $rfl_upd_sql  = 'update iris_report_filter set parentfilterid = #parentfilterid# where id = #id#;'.chr(10);
        foreach ($rfl_data as $rfl_val) {
            $rfl_ins_query .= iris_str_replace(
                array('#id#', '#reportid#', '#report_tableid#', '#columnid#', '#condition#', 
                    '#guidvalue#', '#datevalue#', '#floatvalue#', '#stringvalue#', '#intvalue#', 
                    '#name#', '#code#', '#isvisible#', '#number#', 
                    '#logiccondition#', '#sql#', '#droplistsql#', '#description#', '#equation#'), 
                array(
                    "'".$this->_convGUID($rfl_val['id'], $guid_token)."'",
                    "'".$this->_convGUID($rfl_val['reportid'], $guid_token)."'",
                    //($rfl_val['report_tableid'] == '') ? 'null' : "'".$rfl_val['report_tableid']."'",
                    ($rfl_val['report_tableid'] == '') ? 'null' : "'".$this->_convGUID($rfl_val['report_tableid'], $guid_token)."'",
                    ($rfl_val['columnid'] == '') ? 'null' : "'".$rfl_val['columnid']."'",
                    ($rfl_val['condition'] == '') ? 'null' : "'".$rfl_val['condition']."'",
                    ($rfl_val['guidvalue'] == '') ? 'null' : "'".$rfl_val['guidvalue']."'",
                    ($rfl_val['datevalue'] == '') ? 'null' : "'".$rfl_val['datevalue']."'",
                    ($rfl_val['floatvalue'] == '') ? 'null' : "'".$rfl_val['floatvalue']."'",
                    ($rfl_val['stringvalue'] == '') ? 'null' : "'".$rfl_val['stringvalue']."'",
                    ($rfl_val['intvalue'] == '') ? 'null' : "'".$rfl_val['intvalue']."'",
                    "'".$rfl_val['name']."'",
                    "'".$rfl_val['code']."'",
                    "'".$rfl_val['isvisible']."'",
                    ($rfl_val['number'] == '') ? 'null' : $rfl_val['number'],
                    "'".$rfl_val['logiccondition']."'",
                    '$x$'.$rfl_val['sql'].'$x$',
                    '$x$'.$rfl_val['droplistsql'].'$x$',
                    '$x$'.$rfl_val['description'].'$x$',
                    ($rfl_val['equation'] == '') ? 'null' : "'".$rfl_val['equation']."'",
                ),
                $rfl_ins_sql
            );

            if ($rfl_val['parentfilterid'] == '') {
                continue;
            }
            $rfl_upd_query .= iris_str_replace(
                array('#id#', '#parentfilterid#'),
                array(
                    "'".$this->_convGUID($rfl_val['id'], $guid_token)."'",
                    "'".$this->_convGUID($rfl_val['parentfilterid'], $guid_token)."'",
                ),
                $rfl_upd_sql
            );        
        }
        //echo $rfl_ins_query.$rfl_upd_query;

        // 6. параметры отчета
        $rpm_data_sql = "select id, reportid, name, code, typeid, tableid, number, isvisible, ".
            "droplistsql, description, equation ".
            "from iris_report_parameter where reportid = :id";
        $rpm_data_cmd = $con->prepare($rpm_data_sql);
        $rpm_data_cmd->execute(array(":id" => $p_report_id));
        $rpm_data = $rpm_data_cmd->FetchAll(PDO::FETCH_ASSOC);
        $rpm_ins_sql  = 'insert into iris_report_parameter (id, reportid, name, code, typeid, '.
            'tableid, number, isvisible, droplistsql, description, equation) ';
        $rpm_ins_sql .= 'values (#id#, #reportid#, #name#, #code#, #typeid#, '.
            '#tableid#, #number#, #isvisible#, #droplistsql#, #description#, #equation#);'.chr(10);
        foreach ($rpm_data as $rmp_val) {
            $rpm_ins_query .= iris_str_replace(
                array('#id#', '#reportid#', '#name#', '#code#', '#typeid#', '#tableid#', 
                    '#number#', '#isvisible#', '#droplistsql#', '#description#', '#equation#'), 
                array(
                    "'".$this->_convGUID($rmp_val['id'], $guid_token)."'",
                    "'".$this->_convGUID($rmp_val['reportid'], $guid_token)."'",
                    "'".$rmp_val['name']."'",
                    "'".$rmp_val['code']."'",
                    ($rmp_val['typeid'] == '') ? 'null' : "'".$rmp_val['typeid']."'",
                    ($rmp_val['tableid'] == '') ? 'null' : "'".$rmp_val['tableid']."'",
                    ($rmp_val['number'] == '') ? 'null' : $rmp_val['number'],
                    "'".$rmp_val['isvisible']."'",
                    '$x$'.$rmp_val['droplistsql'].'$x$',
                    '$x$'.$rmp_val['description'].'$x$',
                    ($rmp_val['equation'] == '') ? 'null' : "'".$rmp_val['equation']."'",
                ), 
                $rpm_ins_sql
            );
        }
        //echo $rpm_ins_query;

        // вставка нового отчета
        $rep_full_query = $rep_ins_query.$rac_ins_query.$rtb_ins_query.$rtb_upd_query.$rcl_ins_query.$rfl_ins_query.$rfl_upd_query.$rpm_ins_query;
        //$rep_full_query = $rep_ins_query.$rtb_ins_query.$rfl_ins_query.$rfl_upd_query.$rpm_ins_query;
        $con->exec($rep_full_query);
        $errorinfo = $con->errorInfo();
        if ($errorinfo[0] != '00000') {
            return array("success" => 0, "message" => json_convert('Возникла ошибка при копировании отчета: <br><b>'.$errorinfo[2].'</b>'));
            //return array("success" => 0, "message" => json_convert('Возникла ошибка при копировании отчета: <br><b>'.$errorinfo[2].'</b><br>'.$rep_full_query));
        }
        log_sql($rep_full_query, 'copy report', 'write');

        return array("success" => 1, "message" => json_convert('Отчет скопирован'));
    }
}
