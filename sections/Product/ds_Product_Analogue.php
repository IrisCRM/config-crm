<?php

namespace Iris\Config\CRM\sections\Product;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки продукта
 */
class ds_Product_Analogue extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    function onAfterPost($table, $id, $old_data, $new_data) {
        $select_sql = "select pa1.ProductID, pa1.AnalogueID, "
                . "pa1.IsReverse, pa2.IsReverse, pa2.id "
                . "from iris_Product_Analogue pa1 "
                . "left join iris_Product_Analogue pa2 " 
                . "on pa2.ProductID = pa1.AnalogueID " 
                . "and pa2.AnalogueID = pa1.ProductID "
                . "where pa1.ID = :p_id";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_id' => $id,
        ));
        $statement->bindColumn(1, $ProductID);
        $statement->bindColumn(2, $AnalogueID);
        $statement->bindColumn(3, $IsReverse1);
        $statement->bindColumn(4, $IsReverse2);
        $statement->bindColumn(5, $ID2);
        $res = $statement->fetch();

        $Action = 'None';

        //если у текущего обратный аналог, а другого нет или есть, но не обратный аналог, то...
        if ($IsReverse1 == 1) {
            if ($IsReverse2 == '') { //если нет, то вставим
                $id = create_guid();
                $insert_sql = "insert into iris_Product_Analogue "
                        . "(id, ProductID, AnalogueID, IsReverse) "
                        . "values (:p_id, :p_product_id, "
                        . ":p_analogue_id, :p_isreverse)";
                $statement = $this->connection->prepare($insert_sql);
                $statement->execute(array(
                    ':p_id' => $id,
                    ':p_product_id' => $AnalogueID,
                    ':p_analogue_id' => $ProductID,
                    ':p_isreverse' => $IsReverse1,
                ));
                $Action = 'Insert';
            }
            else
            if ($IsReverse2 != 1) { //если есть, но не обратный, то обновим
                $update_sql = "update iris_Product_Analogue "
                        . "set IsReverse = :p_isreverse "
                        . "where ID = :p_id";
                $statement = $this->connection->prepare($update_sql);
                $statement->execute(array(
                    ':p_id' => $ID2,
                    ':p_isreverse' => $IsReverse1,
                ));
                $Action = 'Update';
            }
        }
        //если у текущего не обратный аналог, а другого обратный аналог, то удалим
        else
        if ($IsReverse2 == 1) { //если есть, то удалим
            $update_sql = "delete from iris_Product_Analogue "
                    . "where ID = :p_id";
            $statement = $this->connection->prepare($update_sql);
            $statement->execute(array(
                ':p_id' => $ID2,
            ));
            $Action = 'Delete';
        }

        $l_result['ProductAnalogue']['Action'] = $Action;
        return $l_result;
    }
}
