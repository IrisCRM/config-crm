/**
 * Скрипт карточки поля отчёта
 */
irisControllers.classes.dc_Report_Column = 
    irisControllers.classes.ReportCardController.extend({

  events: {
    'lookup:changed #Report_TableID': 'onChangeReportTableID',
    'change #sql': 'onChangesql',
    'lookup:changed #ColumnID': 'onChangeColumnID'
  },

  onChangeReportTableID: function() {
    this.filterTableColumn('Column');
  },

  onChangesql: function() {
    if (this.getField('sql').val()) {
      this.getField('Report_TableID').parents('.form_row').find('.form_table').hide();
    }
    else {
      this.getField('Report_TableID').parents('.form_row').find('.form_table').show();
    }
  },

  onChangeColumnID: function(event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id);
  },

  onOpen: function() {
    var form = $(this.el.id).down('form');

    // Таблица поля фильтруется по ID отчёта
    this.bindFields('ReportID', 'Report_TableID');
    //Свяжем LinkedColumnID с ReportID
    bind_lookup_element(form.ReportID, form.LinkedColumnID, 'ReportID');

    // Колонка поля фильтруется по ID таблицы поля
    this.filterTableColumn('Column');

    this.onChangesql();
  }

});
