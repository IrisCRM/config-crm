<?php

namespace Iris\Config\CRM\sections\Printform;

use Config;
use Iris\Iris;

/**
 * Формирование печатных форм документов
 */
class s_Printform extends Config
{
    public function __construct()
    {
        parent::__construct(array(
            'common/Lib/access.php',
        ));
    }

    public function render($params)
    {
        if (!function_exists('GetReplacementPattern')) {
            $this->_Loader->loadOnce('core/engine/printform.php');
        }

        if (empty($params['record_id'])) {
            return false;
        }

        // Если указан код ПФ
        if (!empty($params['code'])) {
            $pf = $this->_DB->getRecordByCode($params['code'], '{PrintForm}', 'id');
            if (!empty($pf['id'])) {
                $id = $pf['id'];
            }
            else {
                return false;
            }
        }
        else //Если указан id ПФ
        if (!empty($params['id'])) {
            $id = $params['id'];
        }
        else {
            return false;
        }

        // Есть ли доступ к ПФ
        $record_perm_arr = CheckRecordPermission('iris_printform', $id, 
                $this->connection);
        if ($record_perm_arr['R'] == 0) {
            return false;
        }
        return FillForm($id, $params['record_id']);
    }
}
