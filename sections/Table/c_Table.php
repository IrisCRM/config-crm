<?php

namespace Iris\Config\CRM\sections\Table;

use Config;
use Iris\Iris;
use Loader;

/**
 * Карточка раздела Таблицы
 */
class c_Table extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, ['common/Lib/lib.php']);
    }

    public function GetTableCount()
    {
        $res = $this->connection->query("select count(id)-1 from iris_table where is_access = '1'")->fetchAll();
        return [
            "message" => $res[0][0]
        ];
    }

    /**
     * Копирует права по умолчанию из заданной таблицы ко всем таблицам, у которых учитываются права доступа по записям
     * @param array $params
     * @return array
     */
    function CopyAccessDefault($params) {
        $table_id = $params['table_id'];
        $con = $this->connection;

        if (IsUserInAdminGroup($con) == 0) {
            return [
                "success" => 0,
                "message" => json_convert('Данная функция доступна только администраторам"'),
            ];
        }

        // 1. удалить все права по умлчанию у всех таблиц, кроме мастер-таблицы с id=$table_id
        $del_cmd = $con->prepare("delete from iris_table_accessdefault where tableid <> :tableid");
        $del_cmd->execute([
            ":tableid" => $table_id,
        ]);
        if ($del_cmd->errorCode() != '00000') {
            return [
                "success" => 0,
                "message" => json_convert('Ошибка! Не удалось удалить права по умолчанию у таблиц"'),
            ];
        }

        // 2. вставить для всех таблиц права по умолчанию, как у мастер таблицы
        $ins_sql  = "insert into iris_table_accessdefault (id, tableid, creatorroleid, accessroleid, r, w, d, a) ";
        $ins_sql .= "select iris_genguid() as id, T0.id as tableid, TAD.creatorroleid, TAD.accessroleid, TAD.r, TAD.w, TAD.d, TAD.a ";
        //$ins_sql .= "from iris_table T0, (select creatorroleid, accessroleid, r, w, d, a from iris_table_accessdefault TAD where tableid = :tableid) TAD ";
        $ins_sql .= "from iris_table T0, (select creatorroleid, accessroleid, r, w, d, a from iris_table_accessdefault TAD) TAD ";
        $ins_sql .= "where T0.is_access = '1' and T0.id <> :tableid";

        $ins_cmd = $con->prepare($ins_sql);
        $ins_cmd->execute([
            ":tableid" => $table_id,
        ]);
        if ($ins_cmd->errorCode() != '00000') {
            return [
                "success" => 0,
                "message" => json_convert('Ошибка! Не удалось вставить права по умолчанию у таблиц"'),
            ];
        }

        return [
            "success" => 1,
            "message" => json_convert('Права скопированы успешно'),
        ];
    }

    /**
     * Проверка существования справочника
     */
    function GetDictStatus($params) {
        $p_dict_code = $params['dict_code'];

        if (IsUserInAdminGroup() == 0) {
            return [
                "success" => 0,
                "errm" => json_convert('Данная функция доступна только администраторам'),
            ];
        }

        /** @var Loader $Loader */
        $Loader = Iris::$app->getContainer()->get('Loader');
        $dictName = $Loader->getNewFileName('dictionary/' . $p_dict_code . '.xml');
        if (file_exists($dictName)) {
            return [
                "success" => 0,
                "errm" => json_convert('Справочник уже существует'),
            ];
        }

        return [
            "success" => 1,
        ];
    }

    /**
     * Создает новый справочник (xml)
     */
    function CreateNewDict($params) {
        $p_table_code = $params['table_code'];
        $p_table_name = $params['table_name'];
        $p_dict_code = $params['dict_code'];

        $ex_info = $this->GetDictStatus([
            'dict_code' => $p_dict_code,
        ]);
        if ($ex_info['success'] == 0)
            return $ex_info;

        $dict_template = $this->getDictXMLTempale();

        // перевод в UTF
        $dict_template = pack("CCC", 0xef, 0xbb, 0xbf) . UtfEncode($dict_template);

        // замена
        $ver_xml = simplexml_load_file(Iris::$app->getCoreDir() . 'core/version.xml');
        $dict_template = iris_str_replace(
            ['#TABLE_NAME#', '#TABLE_CAPTION#', '#VER#', '#DATE#'],
            [$p_table_code, $p_table_name, $ver_xml->CURRENT_VERSION, date('d.m.Y H:i:s')],
            $dict_template
        );

        // сохранение файла
        /** @var Loader $Loader */
        $Loader = Iris::$app->getContainer()->get('Loader');
        $filename = $Loader->getNewFileName('dictionary/' . $p_dict_code . '.xml');
        $dirname = dirname($filename);
        if (!is_dir($dirname)) {
            mkdir($dirname, fileperms(Iris::$app->getSrcDir()), true);
        }
        file_put_contents($filename, $dict_template);

        return [
            "success" => 1,
            "message" => json_convert('Справочник создан успешно'),
        ];
    }

    protected function getDictXMLTempale() {
        return <<<EOD
<?xml version="1.0"?>
<!-- created with Iris CRM #VER# on #DATE# -->
<DICT>
   <DICTONARY table="#TABLE_NAME#">
      <GRID_WND lines_count="1" caption="#TABLE_CAPTION#" width="600" height="275">
         <COLUMNS>
			<ITEM caption="Порядок" db_field="OrderPos" width="80px" row_type="common"/>
            <ITEM caption="Название" db_field="Name" width="50%" row_type="common"/>
            <ITEM caption="Код" db_field="Code" width="20%" row_type="common"/>
            <ITEM caption="Описание" db_field="Description" width="30%" row_type="common"/>
         </COLUMNS>
      </GRID_WND>
      <EDITCARD name="dc_#TABLE_NAME#" caption="#TABLE_CAPTION#" width="450" height="180" layout="1, 2, 1">
         <ELEMENTS>
            <FIELD elem_type="text" caption="Название" db_field="Name" mandatory="yes" datatype="string" row_type="common"/>
            <FIELD elem_type="text" caption="Код" db_field="Code" mandatory="no" datatype="string" row_type="common"/>
			<FIELD elem_type="text" caption="Порядок в списке" title="порядок данной записи в выпадающем списке карточки и в автофильтре" db_field="OrderPos" mandatory="no" datatype="string" row_type="common"/>
            <FIELD elem_type="textarea" textarea_rows="2" caption="Описание" db_field="Description" mandatory="no" datatype="string" row_type="common"/>
         </ELEMENTS>
      </EDITCARD>
   </DICTONARY>
</DICT>
EOD;
    }
}
