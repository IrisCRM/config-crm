<?php

namespace Iris\Config\CRM\sections\sql;

use Config;
use Iris\Iris;
use Language;
use PDO;

/**
 * Раздел SQL
 */
class u_sql extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    public function onPrepare($params)
    {
        // Проверим, имеет ли текущий пользователь админские права
        $T = Language::getInstance();
        if (!$this->_User->isAdmin()) {
            $result['error'] = json_encode_str($T->t(
                'Вы должны иметь права админстратора '
                . 'для доступа к этой функции.'));
            return $result;
        }

        $data = array();
        $result = $this->renderSectionView('sql_form', $data);
        return array('html' => json_encode_str($result));
    }

    function RunSQL($params)
    {
        // Проверим, имеет ли текущий пользователь админские права
        $T = Language::getInstance();
        if (!$this->_User->isAdmin()) {
            $result['error'] = json_encode_str($T->t(
                'Вы должны иметь права админстратора '
                . 'для доступа к этой функции.'));
            return $result;
        }

        $con = $this->connection;

        $sql = json_decode_str($params['sql']);
        
        $con->exec($sql);
        $errorCode = $con->errorInfo();

        $result['error'] = '';
        $result['html'] = '';
        
        if (strtolower(substr(trim($sql), 0, 7)) == 'select ') {
            $statement = $con->prepare($sql);
            $statement->execute();
            $res = $statement->fetchAll(PDO::FETCH_ASSOC);
            $result['html'] .= '<table id="grid" class="grid">';
            $result['html'] .= '<thead>';
            $result['html'] .= '<tr>';
            foreach ($res[0] as $key => $val) {
                $result['html'] .= '<th class="grid">'
                		. json_encode_str($key) . '</th>';
            }
            $result['html'] .= '</tr>';
            $result['html'] .= '</thead>';
            
            $result['html'] .= '<tbody>';
            $class = 'grid_even';
            foreach ($res as $row) {
                $result['html'] .= "<tr class=\"$class\">";
                $class = $class == 'grid_even' ? 'grid_odd' : 'grid_even';
                foreach ($row as $key => $val) {
                   $result['html'] .= '<td class="grid_row_string">'
                   		. json_encode_str($row[$key]) . '</td>';
                }
                $result['html'] .= '</tr>';
            }
            $result['html'] .= '</tbody>';
            $result['html'] .= '</table>';
        }

        if ('00000' != $errorCode[0]) {
           $result['error'] .= $errorCode[0] . ': ' 
           	    . json_encode_str($errorCode[2]);
        }
        else {
           $result['html'] = json_encode_str('<p>' 
           	    . $T->t('Скрипт выполнен успешно') . '.</p>')
           	    . $result['html'];
        }

        return $result;
    }

}
