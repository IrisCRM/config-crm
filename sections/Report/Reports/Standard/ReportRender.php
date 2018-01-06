<?php

namespace Iris\Config\CRM\sections\Report\Reports\Standard;

class ReportRender
{
    /**
     * Информация об отчёте
     */
    public $report_info;

    /**
     * Фильтры отчёта
     */
    public $filters;

    /**
     * Формат отчёта - html (или пусто) или csv
     */
    public $format;

    /**
     * Название отчёта с таким набором фильтров (заголовок)
     */
    public $report_name;

    protected $_sql;
    protected $_params;
    protected $_show_info;
    protected $_report_description;
    protected $_js;

    public $data;
    public $errorinfo;

    public function __construct($report_info, $filters = null, $format = null, 
            $report_name = null) 
    {
        $this->report_info = $report_info;
        $this->filters = $filters;
        $this->format = $format;
        $this->report_name = $report_name;
    }

    /**
     * Подготовка данных для построения отчёта
     */
    public function prepare()
    {
        list ($this->_sql, $this->_params, $this->_show_info) = 
                BuildReportSQL($this->report_info['id'], $this->filters);
        $this->_report_description = GetReportInfo(
                $this->report_info['id'], $this->report_name);
    }

    /**
     * Построение данных отчёта
     */
    public function buildData()
    {
        list ($this->data, $this->errorinfo) = BuildReportData(
                $this->_show_info, $this->_sql, $this->_params);
    }

    /**
     * Построение таблицы отчёта
     */
    public function buildTable()
    {
        $table = null;
        if (!$this->format || $this->format == 'html') {
            switch ($this->report_info['tabletype']) {
                case 't':
                    // Транспонированная
                    $table = BuildReportTransposedTable(
                            $this->data, $this->_show_info, $this->_sql, 
                            $this->_params, $this->errorinfo);
                break;
                case 'c':
                    // Карточки
                    $layuot = array(
                        "row_count" => $this->report_info['tablecardrows'], 
                        "column_count" => $this->report_info['tablecardcols'],
                    );
                    $table = BuildReportCards($this->data, $this->_show_info, 
                            $this->_sql, $this->_params, $this->errorinfo, 
                            $layuot);
                break;
                default:
                    // Обычная
                    $table = BuildReportTable($this->data, $this->_show_info, 
                            $this->_sql, $this->_params, $this->errorinfo);
                break;
            }
        }
        else 
        if ('csv' == $this->format) {
            $table = BuildReportTable($this->data, $this->_show_info, 
                    $this->_sql, $this->_params, 
                    $this->errorinfo, $this->format);
        }
        return $table;
    }

    /**
     * Установить обработчики js
     *
     * @param string $js Javascript код с обработчиками
     */
    public function setJavascript($js) {
        $this->_js = $js;
    }

    /**
     * Генерация отчёта
     */
    public function render()
    {
        // Подготовка отчета
        $this->prepare();

        // Извлечение данных для отчёта
        $this->buildData();
        $this->data['javascript_bottom'] = $this->_js;

        //TODO: Генерация полей с фильтрами
        list ($filtters_all, $filters) = Report_GetFilters(
                $this->report_info['id'], null, true, $this->filters);

        $filter_fields = 
            '<div id="window_filter_report" class="report-filters">'.
            json_decode_str($filtters_all['Card']).
            '</div>';

        $parameter_fields = 
            '<div id="parameters" style="display: none;">'.
            json_decode_str($filtters_all['Card']).
            '</div>';

        // Отрисовка табличной части
        $table = $this->buildTable();
        $report = null;
        if (!$this->format || $this->format == 'html') {
            // Отрисовка графика
            $graph = BuildReportGraph(
                    $this->data, $this->_show_info, $this->report_info['id']);

            // Генерация html отчета
            $report = BuildReport($table, $graph, $filters, 
                    $this->_report_description, $filter_fields, 
                    $parameter_fields);
        }
        else 
        if ('csv' == $this->format) {
            $report = $table;
            header('Content-Type: text/csv;');
            header('Content-Disposition: attachment; filename="report.csv"');
        }

        return $report;
    }
}
