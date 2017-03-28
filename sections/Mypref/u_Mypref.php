<?php
//********************************************************************
// Раздел "Настройки" (личный кабинет). нестандартный раздел
//********************************************************************

namespace Iris\Config\CRM\sections\Mypref;

use Config;
use Iris\Iris;
use PDO;

class u_Mypref extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
        ));
    }

    public function changePassword($params) {
        $current = $params['current'];
        $new1 = $params['new1'];
        $new2 = $params['new2'];

        $con = $this->connection;
        $userId = GetUserID($con);

        $cmd = $con->prepare("select password from iris_contact where id=:id");
        $cmd->execute(array(":id" => $userId));
        $data = current($cmd->fetchAll(PDO::FETCH_ASSOC));
        $currentDbPassword = $data['password'];

        if (md5($current) != $currentDbPassword) {
            return array("isOk" => false, "errorMessage" => 'Необходимо указать верный текущий пароль');
        }

        if ($new1 == '') {
            return array("isOk" => false, "errorMessage" => 'Не указан новый пароль');
        }

        if ($new1 != $new2) {
            return array("isOk" => false, "errorMessage" => 'Поле "Пароль" и "Подтверждение" различаются. необходимо указать одинаковые значения');
        }

        // новый пароль совпадает со старым
        if ($current == $new1) {
            return array("isOk" => false, "errorMessage" => 'Новый пароль не должен совпадать со старым');
        }

        // пароль длинный и содержит цифры
        if (iris_strlen($new1) < 6) {
            return array("isOk" => false, "errorMessage" => 'Пароль должен быть не менее 6 символов');
        }

//        $pattern1 = '.[A-Za-z].'; // символы английского алфавита
//        $pattern2 = '.[0-9].'; // цифры
        $pattern3 = ".[а-яА-я\\.,!@#$%\\^&\\*() ~`_+\\\\\\[\\]\\{\\}]."; // недопустимые символы
//        $pm1 = iris_preg_match($pattern1, $new1);
//        $pm2 = iris_preg_match($pattern2, $new1);
        $pm3 = iris_preg_match($pattern3, $new1);
        /*
            if (!(($pm1 == 1) and ($pm2 == 1) and ($pm3 == 0))) {
                return array("isOk" => false, "errorMessage" => 'Пароль должен состоять из символов И цифр английского алфавита');
            }
        */
        if ($pm3 == 1) {
            return array("isOk" => false, "errorMessage" => 'Пароль может содержать только цифры и символы английского алфавита');
        }

        $cmd = $con->prepare("update iris_contact set password=:pwd where id=:id");
        $cmd->execute(array(":pwd" => md5($new1), ":id" =>$userId));
        $error_info = $cmd->errorInfo();
        if ($error_info[0] != '00000') {
            return array("isOk" => false, "errorMessage" => 'Не удалось сменить пароль');
        }

        return array("isOk" => true);
    }
}
