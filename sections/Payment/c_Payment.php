<?php

namespace Iris\Config\CRM\sections\Payment;

use Config;
use Iris\Iris;

/**
 * Карточка платежа
 */
class c_Payment extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    public function onChangePaymentStateID($params, $con = null)
    {
        $result = null;
        $StateCode = GetFieldValueByID(
                'PaymentState', $params['value'], 'Code', $con);

        if ($StateCode == 'Completed') {
            $date = GetCurrentDBDate($con);
            $result = FieldValueFormat('PaymentDate', $date, null, $result);
        }

        return $result;
    }

    public function onChangeAmount($params, $con = null)
    {
        $result = null;
        if ($params['value'] > 0) {
            $result = GetDictionaryValues(
                array (
                    array ('Dict' => 'PaymentState', 'Code' => 'Completed')
                ), $con);
            $date = GetCurrentDBDate($con);
            $result = FieldValueFormat('PaymentDate', $date, null, $result);
        }
        return $result;
    }

}
