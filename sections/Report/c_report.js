/**
 * Скрипт карточки отчёта
 */

irisControllers.classes.c_Report = 
    irisControllers.classes.ReportCardController.extend({

  events: {
    'lookup:changed #xReport_TableID': 'onChangeReportTableID',
  },

  onChangeReportTableID: function() {
    var form = $(this.el.id).down('form');
    this.filterTableColumn('', form.xReport_TableID, form.xColumnID);
  },

  onOpen: function() {
    // Таблица графика фильтруется по ID отчёта
    this.bindFields('_id', 'xReport_TableID', 'ReportID');
    // Колонка графика фильтруется по ID таблицы графика
    var form = $(this.el.id).down('form');
    this.filterTableColumn('', form.xReport_TableID, form.xColumnID);
  }

});
