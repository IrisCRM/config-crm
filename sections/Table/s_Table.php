<?php

namespace Iris\Config\CRM\sections\Table;

use Config;

/**
 * Серверная логика раздела Таблицы
 */
class s_Table extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, ['common/Lib/lib.php']);
    }

    /**
     * Добавление описания колонки в iris_Table_Column
     */
    protected function AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, $colname, $colcode, $coltype, $isnotnull=0, $has_index=false, $has_pk=false, $fk_table='', $fk_upd_type='', $fk_del_type='')
    {
        //Получим id типа колонки
        if ($coltype == 'guid') {
            $coltype = '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1';
        }
        elseif ($coltype == 'datetime') {
            $coltype = '666d5a4e-6064-9286-a921-e7957d39d283';
        }
        elseif ($coltype == 'string250') {
            $coltype = '332cb042-111b-3598-4458-7b36a1d0b67f';
        }
        elseif ($coltype == 'string1000') {
            $coltype = '8e1d85be-6230-4c6f-6905-1aa87d25fa98';
        }
        elseif ($coltype == 'bool') {
            $coltype = '7c6eba80-2a51-bb27-53d7-a40f00f3d024';
        }
        elseif ($coltype == 'string30') {
            $coltype = '45fd9416-c707-ba1f-66c6-a6bf69424474';
        }

        //Дополнительные колонки
        $add_vals = null;
        $add_cols = '';
        if ($has_index) {
            $add_cols .= ', indexname';
            $add_vals .= ", '".$table_code."_pk_i'";
        }
        if ($has_pk) {
            $add_cols .= ', pkname';
            $add_vals .= ", 'pk_".$table_code."'";
        }
        if ('' != $fk_table) {
            $add_cols .= ', fkname, fktableid, onupdateid, ondeleteid';
            $add_vals .= ", 'fk_".$table_code."_ref_".$colcode."', (select id from iris_table where code='".$fk_table."'), (select id from iris_constraintaction where code='".$fk_upd_type."'), (select id from iris_constraintaction where code='".$fk_del_type."')";
        }

        //запрос
        $new_id = create_guid();
        $sql = "insert into iris_table_column (id, createid, createdate, tableid, ".
            '"name", code, columntypeid, isnotnull'.$add_cols.') '.
            "values ('".$new_id."', '".$UserID."', now(), '".$p_record_id."', ".
            "'".$colname."', '".$colcode."', '".$coltype."', ".$isnotnull.$add_vals.
            ");";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');

        //Если ошибка
        if ($statement->errorCode() != '00000') {
            $result['Error'] .= json_encode_str('Ошибка при добавлении информации о колонке "'.$colname.'" в таблицу "Колонки". ');
        }

        //Вернем с дополнением об ошибке, если возникла
        return $result;
    }


    /**
     * Добавление таблицы для отслеживания прав доступа
     */
    protected function AddAccessTable($con, $result, $table_code, $table_name, $UserID)
    {
        $table_code_main = $table_code;
        $table_code .= '_access';

        //Составим запрос добавления таблицы
        $sql = 'create table "'.$table_code.'" ('.
            'id character varying(36) NOT NULL,'.
            'createid character varying(36),'.
            'createdate timestamp without time zone,'.
            'modifyid character varying(36),'.
            'modifydate timestamp without time zone,'.
            'recordid character varying(36) NOT NULL, '.
            'accessroleid character varying(36), '.
            'contactid character varying(36), '.
            'r character varying(1), '.
            'w character varying(1), '.
            'd character varying(1), '.
            'a character varying(1), '.
            'CONSTRAINT pk_'.$table_code.' PRIMARY KEY (id), '.
            'CONSTRAINT fk_'.$table_code.'_ref__accessrole FOREIGN KEY (accessroleid) '.
            '    REFERENCES iris_accessrole (id) MATCH SIMPLE '.
            '    ON UPDATE RESTRICT ON DELETE RESTRICT, '.
            'CONSTRAINT fk_'.$table_code.'_ref__contact FOREIGN KEY (contactid) '.
            '    REFERENCES iris_contact (id) MATCH SIMPLE '.
            '    ON UPDATE RESTRICT ON DELETE RESTRICT, '.
            'CONSTRAINT fk_'.$table_code.'_ref__recordid FOREIGN KEY (recordid) '.
            '    REFERENCES '.$table_code_main.' (id) MATCH SIMPLE '.
            '    ON UPDATE CASCADE ON DELETE CASCADE, '.
            'CONSTRAINT "'.$table_code.'_CONTACT_UK" UNIQUE (recordid, contactid), '.
            'CONSTRAINT "'.$table_code.'_ROLE_UK" UNIQUE (recordid, accessroleid) '.
            ');';
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, $table_code_main.' (права доступа)', 'write');

        if ($statement->errorCode() != '00000') {
            $result['Error'] .= json_encode_str('Ошибка при добавлении таблицы "'.$table_code.'" в физическую структуру БД. ');
            return $result;
        }

        $record_id = create_guid();
        //Добавление таблицы _access в iris_Table
        $sql = "insert into iris_table (id, createid, createdate, ".
            '"name", code, is_access) '.
            "values ('".$record_id."', '".$UserID."', now(), ".
            "'".$table_name." - права доступа', '".$table_code."', 0".
            ");";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        //Если ошибка
        if ($statement->errorCode() != '00000') {
            $result['Error'] .= json_encode_str('Ошибка при добавлении информации о таблице "'.$table_code.'" в таблицу "Таблицы". ');
        }


        //Составим запрос добавления комментариея на таблицу в БД
        $sql = "COMMENT ON TABLE ".$table_code." IS '".$table_name." - права доступа';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');

        //Составим запрос добавления комментариев в БД
        $sql = "COMMENT ON COLUMN ".$table_code.".id IS 'ID';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".createid IS 'Автор';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".createdate IS 'Дата создания';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".modifyid IS 'Изменил';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".modifydate IS 'Дата изменения';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".recordid IS 'ID записи';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".accessroleid IS 'Роль';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".contactid IS 'Пользователь';";
        $statement = $con->prepare($sql);
        $statement->execute();
        $sql = "COMMENT ON COLUMN ".$table_code.".r IS 'Чтение';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".w IS 'Правка';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".d IS 'Удаление';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".a IS 'Изменение прав';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');

        //Составим запрос добавления индекса в БД
        $sql = "CREATE UNIQUE INDEX ".$table_code."_pk_i ON ".$table_code." USING btree(id);";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "CREATE INDEX ".$table_code."_accessroleid_i ON ".$table_code." USING btree(accessroleid);";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "CREATE INDEX ".$table_code."_contactid_i ON ".$table_code." USING btree(contactid);";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "CREATE INDEX ".$table_code."_recordid_i ON ".$table_code." USING btree(recordid);";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');


        //Составим запросы добавления информации о колонках
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'ID', 'id', 'guid', 1, true, true);
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Автор', 'createid', 'guid');
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Дата создания', 'createdate', 'datetime');
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Изменил', 'modifyid', 'guid');
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Дата изменения', 'modifydate', 'datetime');

        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'ID записи', 'recordid', 'guid', 1, true, false, $table_code_main, 'CASCADE', 'CASCADE');
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Роль', 'accessroleid', 'guid', 0, true, false, 'iris_accessrole', 'RESTRICT', 'RESTRICT');
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Пользователь', 'contactid', 'guid', 0, true, false, 'iris_contact', 'RESTRICT', 'RESTRICT');

        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Чтение', 'r', 'bool', 0);
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Правка', 'w', 'bool', 0);
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Удаление', 'd', 'bool', 0);
        $result = $this->AddColumnInfo($con, $result, $table_code, $record_id, $UserID, 'Изменение прав', 'a', 'bool', 0);

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data)
    {
        if ($old_data && $new_data) {
            return $this->afterUpdate($id, $old_data);
        }
        if ($old_data && !$new_data) {
            return;
        }

        $p_record_id = $id;
        $con = $this->connection;
        $result = null;

        //Данные о таблице
        list($Name, $table_code, $Description, $Dictionary, $is_access) =
            GetFieldValuesByID('Table', $p_record_id,
                array('Name', 'Code', 'Description', 'Dictionary', 'is_access'),
                $con);

        //Составим запрос добавления таблицы
        $sql = 'create table "'.$table_code.'" ('.
            'id character varying(36) NOT NULL,'.
            'createid character varying(36),'.
            'createdate timestamp without time zone,'.
            'modifyid character varying(36),'.
            'modifydate timestamp without time zone,'.

            '"name" character varying(250) NOT NULL,'.
            'code character varying(250),'.
            'description character varying(1000),'.
            'orderpos character varying(30),'.
            'CONSTRAINT pk_'.$table_code.' PRIMARY KEY (id)'.
            ');';
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, 'таблица '.$table_code, 'write');

        if ($statement->errorCode() != '00000') {
            $result['Error'] .= json_encode_str('Ошибка при добавлении таблицы в физическую структуру БД. ');
            return $result;
        }

        //Составим запрос добавления комментариея на таблицу в БД
        $sql = "COMMENT ON TABLE ".$table_code." IS '".$Name."';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');

        //Составим запрос добавления комментариев в БД
        $sql = "COMMENT ON COLUMN ".$table_code.".id IS 'ID';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".createid IS 'Автор';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".createdate IS 'Дата создания';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".modifyid IS 'Изменил';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".modifydate IS 'Дата изменения';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.'."name"'." IS 'Название';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".code IS 'Код';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".description IS 'Описание';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');
        $sql = "COMMENT ON COLUMN ".$table_code.".orderpos IS 'Позиция сортировки';";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');

        //Составим запрос добавления индекса в БД
        $sql = "CREATE UNIQUE INDEX ".$table_code."_pk_i ON ".$table_code." USING btree(id);";
        $statement = $con->prepare($sql);
        $statement->execute();
        log_sql($sql, '', 'write');


        list ($UserID, $UserName) = GetShortUserInfo(GetUserName(), $con);

        //Составим запросы добавления информации о колонках
        $result = $this->AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, 'ID', 'id', 'guid', 1, true, true);
        $result = $this->AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, 'Автор', 'createid', 'guid');
        $result = $this->AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, 'Дата создания', 'createdate', 'datetime');
        $result = $this->AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, 'Изменил', 'modifyid', 'guid');
        $result = $this->AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, 'Дата изменения', 'modifydate', 'datetime');
        $result = $this->AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, 'Название', 'name', 'string250', 1);
        $result = $this->AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, 'Код', 'code', 'string250');
        $result = $this->AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, 'Описание', 'description', 'string1000');
        $result = $this->AddColumnInfo($con, $result, $table_code, $p_record_id, $UserID, 'Позиция сортировки', 'orderpos', 'string30');


        //Если по таблице надо отслеживать права доступа, то создадим <таблица>_access
        if ($is_access) {
            $result = $this->AddAccessTable($con, $result, $table_code, $Name, $UserID);
        }

        // 22.11.2011: если это таблица справочника, то добавим в ее группу таблиц справочники
        if ($Dictionary != '') {
            $dict_sql = "insert into iris_Table_TableGroup (ID, createid, createdate, modifyid, modifydate, TableGroupID, TableID) values ('".create_guid()."', '".$UserID."', now(), '".$UserID."', now(), (select id from iris_tablegroup where code='Dictionary'), '".$p_record_id."');";
            $cmd = $con->prepare($dict_sql);
            $cmd->execute();
            log_sql($dict_sql, 'таблица '.$table_code.' добавлена в группу таблиц справочники', 'write');
        }

        $result['Result'] = 'ok';

        return $result;
    }

    protected function afterUpdate($p_record_id, $old_values)
    {
        $con = $this->connection;
        $result = null;
        $old_values = $old_values['FieldValues'];

        //Получим текущие значения полей
        $new_values = GetFormatedFieldValuesByID('Table', $p_record_id, array(
            'name',
            'code',
            'is_access',
            'description'
        ),	$con);
        $new_values = $new_values['FieldValues'];

        //Если изменили код (название в БД)
        if (GetArrayValueByName($new_values, 'code') != GetArrayValueByName($old_values, 'code')) {
            $sql = "alter table ".GetArrayValueByName($old_values, 'code').
                " rename TO ".GetArrayValueByName($new_values, 'code');
            $statement = $con->prepare($sql);
            $statement->execute();
            log_sql($sql.';', '', 'write');
            //Если ошибка
            if ($statement->errorCode() != '00000') {
                $result['Error'] .= json_encode_str('Ошибка при изменении названия таблицы в БД. '.$sql);
            }
        }

        //Если изменили название (комментарий)
        if (GetArrayValueByName($new_values, 'name') != GetArrayValueByName($old_values, 'name')) {
            $sql = "comment on table ".GetArrayValueByName($new_values, 'code').
                " is '".json_decode_str(GetArrayValueByName($new_values, 'name'))."';";
            $statement = $con->prepare($sql);
            $statement->execute();
            log_sql($sql, '', 'write');
            //Если ошибка
            if ($statement->errorCode() != '00000') {
                $result['Error'] .= json_encode_str('Ошибка при изменении комментария таблицы в БД. '.$sql);
            }
        }

        //Если изменили Доступ по записям
        if (GetArrayValueByName($new_values, 'is_access') != GetArrayValueByName($old_values, 'is_access')) {
            //Если включили
            if (1 == GetArrayValueByName($new_values, 'is_access')) {
                //TODO: необязательно, можно включить предварительную проверку на наличие таблицы с правами
                list ($UserID, $UserName) = GetShortUserInfo(GetUserName(), $con);
                // $this->AddAccessTable($con, $result, GetArrayValueByName($new_values, 'code'), GetArrayValueByName($new_values, 'name'), $UserID);
                // 22.11.2011: параметры считываем заново, так как при update у имени таблицы портиться кодировка
                $cmd = $con->prepare("select code, name from iris_table where id=:id");
                $cmd->execute(array(":id" => $p_record_id));
                $table_info = current($cmd->fetchAll(PDO::FETCH_ASSOC));
                $this->AddAccessTable($con, $result, $table_info['code'], $table_info['name'], $UserID);
            }
        }

        return $result;
    }

}
