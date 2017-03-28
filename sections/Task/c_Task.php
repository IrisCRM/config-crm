<?php

namespace Iris\Config\CRM\sections\Task;

use Config;
use Iris\Iris;

/**
 * Карточка дела
 */
class c_Task extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    /**
     * TODO: delete, not used?
     */
    public function onChangeTaskID($params)
    {
        return GetLinkedValues('Task', $params['value'], 
                array('Account', 'Contact', 'Object', 'Product', 'Project', 
                    'Issue', 'Bug', 'Marketing', 'Space', 'Offer', 'Pact', 
                    'Invoice', 'Payment', 'FactInvoice', 'Document', 
                    'Incident'), 
                $this->connection);
    }

    public function renderSelectRecordDialog($params)
    {
        // Описание колонок, которые будут отображаться в таблице
        $columns = array(
            'orderpos' => array(
                'caption' => 'Порядок',
                'type' => 'int',
                'display' => false,
                'sort' => 'asc',
                'width' => '10%',
            ),
            'stagename' => array(
                'caption' => 'Стадия',
                'type' => 'string',
                'width' => '20%',
            ),
            'name' => array(
                'caption' => 'Название',
                'type' => 'string',
                'width' => '50%',
            ),
            'completed' => array(
                'caption' => 'Завершено',
                'type' => 'datetime',
                'width' => '20%',
            ),
        );

        // Выбираем данные для отображеия в таблице
        $sql = "select t0.id as id, 
                t0.orderpos as orderpos, 
                t1.name as stagename,
                t0.name as name,
                _iris_datetime_to_string[(select max(FinishDate)
                    from iris_task t00
                    left join iris_taskstate t01 on t01.id = t00.taskstateid
                    where t00.projectid = :projectid
                    and t00.tasktargetid = t0.id
                    and t01.code = 'Finished'
                )] as completed
                from iris_tasktarget t0
                left join iris_projectstage t1 on t0.projectstageid = t1.id
                where t0.isactive = 1
                order by t0.orderpos
        ";
        $filter = array(
            ':projectid' => $params['projectid'],
        );
        $values = $this->_DB->exec($sql, $filter);

        // Выбранная по умолчанию запись - либо следующая либо текущая цель
        $targetid = $params['nexttargetid'] && $params['nexttargetid'] != 'null'
                ? $params['nexttargetid'] : $params['targetid'];
        $parameters = array(
            'selected_id' => $targetid,
            'grid_id' => 'custom_grid_'. md5(time() . rand(0, 10000)),
        );

        // Подготовка данных для пердставления таблицы
        $data = $this->getCustomGrid($columns, $values, $parameters);

        // Построение представления таблицы
        $result = array(
            //'Error' => 'Возникла ошибка',
            'Card' => $this->renderView('grid', $data),
            'GridId' => $parameters['grid_id'],
        );
        return $result;
    }
}
