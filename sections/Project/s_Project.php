<?php

namespace Iris\Config\CRM\sections\Project;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки заказа
 */
class s_Project extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
        $this->_section_name = substr(__CLASS__, 2);
    }

    public function onBeforePostAccountID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'AccountID');
        $this->getValuesFromTables($result, array(
            '{Account}' => array(
                'filter' => 'id',
                'value' => $id,
                'result' => 'PrimaryContactID',
                'alias' => 'ContactID',
                'left' => '{Contact}',
            ),
        ));
        return $result;
    }

    public function onBeforePostContactID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'ContactID');
        return $this->getLinkedValues('{Contact}', $id, array('{{Account}}'));
    }

    public function onBeforePostObjectID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'ObjectID');
        return $this->getLinkedValues('{Object}', $id, 
                array('{{Account}}', '{{Contact}}'));
    }

    public function onBeforePostProjectStageID($params) 
    {
        $id = $this->fieldValue($params['old_data'], 'ProjectStageID');
        return $this->getProbability($id);
    }

    public function onBeforePostProjectStateID($params) 
    {
        $id = $this->fieldValue($params['old_data'], 'ProjectStateID');
        $result = null;
        // Изменение состояния ведет к изменению стадии (если завершено)
        $state = $this->_DB->getRecordById($id, '{ProjectState}', 'code');
        if ($state['code'] == 'Finished') {
            $stage = $this->_DB->getRecordByCode('Finished', '{ProjectStage}', 
                    array('probability', 'id'));
            $this->mergeFields($result, $this->formatField('Probability', 
                    $stage['probability']));
            $this->mergeFields($result, $this->formatField('ProjectStageID', 
                    $stage['id']));
            $date = $this->_Local->dbDateToLocal($this->_DB->datetime());
            $this->mergeFields($result, $this->formatField('FinishDate', $date));
        }
        return $result;
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $result = $this->prepareDetail($params);

        // Значения справочников
        $result = GetDictionaryValues(
            array (
                array ('Dict' => 'ProjectState', 'Code' => 'Plan'),
                array ('Dict' => 'ProjectStage', 'Code' => 'Sale_Info'),
                array ('Dict' => 'Currency', 'Code' => 'RUB')
            ), 
            $this->connection, $result);

        $result = GetDefaultDictionaryValues(
            array('ProjectType'), 
            $this->connection, $result);

        // Ответственный
        $res = GetUserAccessInfo($this->connection);
        $role = $res['userrolecode'];
        // Если текущий пользователь - клиент, то ответственный из его карточки
        if ($role == 'Client') {
            list ($user_id, $name) = GetShortUserInfo(GetUserName(), 
                    $this->connection);
            $result = GetLinkedValuesDetailed('iris_Contact', $user_id, 
                    array(
                        array(
                            'Field' => 'OwnerID', 
                            'GetTable' => 'iris_Contact',
                            'GetField' => 'Name',
                        ),
                        array(
                            'Field' => 'AccountID', 
                            'GetTable' => 'iris_Account',
                            'GetField' => 'Name',
                        ),
                    ), $this->connection, $result);
            // Клеинт
            $result = FieldValueFormat('ContactID', $user_id, $name, $result);
        }
        else {
            $result = GetDefaultOwner(GetUserName(), $this->connection, $result);
        }

        // Номер
        $Number = GenerateNewNumber('ProjectNumber', 'ProjectNumberDate', 
                $this->connection);        
        $result = FieldValueFormat('Number', $Number, null, $result);
        $result = FieldValueFormat('Name', $Number, null, $result);

        // Дата
        $Date = GetCurrentDBDate($this->connection);
        $result = FieldValueFormat('StartDate', $Date, null, $result);
        $result = FieldValueFormat('PlanStartDate', $Date, null, $result);

        // Обязательные суммы
        $result = FieldValueFormat('Income', 0, null, $result);
        $result = FieldValueFormat('Expense', 0, null, $result);
        $result = FieldValueFormat('Profit', 0, null, $result);
        
        // Вероятность для стадии
        $l_StageID = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'ProjectStageID', 'Value');
        $result = $this->getProbability($l_StageID, $result);

        return $result;
    }

    public function onAfterPost($p_table, $p_id, $OldData, $NewData)
    {
        // Если создаём проект
        if (!$OldData) {
            UpdateNumber('Project', $p_id, 'ProjectNumber', 'ProjectNumberDate');
            $this->SetAccessForProject($p_id, 'new');
        }
        else {
            $this->SetAccessForProject($p_id, 'finished');
        }
    }

    public function getProbability($p_StageID, $result = null)
    {
        list ($Probability, $StageCode) = GetFieldValuesByID('ProjectStage', 
                $p_StageID, array('Probability', 'Code'), $this->connection);
        $result = FieldValueFormat('Probability', $Probability, null, $result);

        // Если при изменении стадии надо менять состояние, то вернём и состояние
        $ProjectStateID = GetFieldValueByID('ProjectStage', $p_StageID, 
                'ProjectStateID', $this->connection);
        if ($ProjectStateID) {
            $result = FieldValueFormat('ProjectStateID', $ProjectStateID, 
                    null, $result);
        }

        if ($StageCode == 'Finished') {
            $date = GetCurrentDBDate($this->connection);
            $result = FieldValueFormat('FinishDate', $date, null, $result);
        }

        return $result;
    }

    /**
     * Даем права на чтение ответственному и клиенту
     */
    public function SetAccessForProject($id, $p_mode = 'new') {
        $Linked = GetLinkedValuesDetailed('iris_Project', $id, array(
            array('Field' => 'ContactID'),
            array('Field' => 'OwnerID'),
            array('Field' => 'ProjectStateID',
                'GetField' => 'Code',
                'GetTable' => 'iris_ProjectState')
        ));
        $FieldValues = $Linked['FieldValues'];

        // Если это новый заказ
        if ($p_mode == 'new') {
            // Добавим клиента в доступ на чтение заказа
            $permissions[] = array(
                'userid' => GetArrayValueByParameter($FieldValues, 'Name', 
                        'ContactID', 'Value'), 
                'roleid' => '', 
                'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0
            );
            // Ответственному дадим права на редактирование заказа
            // (полезно, если ответственный - не автор заказа)
            $permissions[] = array(
                'userid' => GetArrayValueByParameter($FieldValues, 'Name', 
                        'OwnerID', 'Value'), 
                'roleid' => '', 
                'r' => 1, 'w' => 1, 'd' => 0, 'a' => 1
            );        
        }

        // Если это не новый заказ
        if ($p_mode == 'finished') {
            $StageCode = GetArrayValueByParameter($FieldValues, 'Name', 
                    'ProjectStateID', 'Caption');
            // Если состояние заказа "Завершен" или "Отменен",
            // то уберем у ответсвенного доступ на редактирование заказа
            if (($StageCode == 'Cancel') or ($StageCode == 'Finished')) {
                $permissions[] = array(
                    'userid' => GetArrayValueByParameter($FieldValues, 'Name', 
                            'OwnerID', 'Value'), 
                    'roleid' => '', 
                    'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0
                );        
            }
        }

        // Применим изменения доступа
        $res = ChangeRecordPermissions('iris_Project', $id, $permissions, 
                $this->connection);
    }    
}
