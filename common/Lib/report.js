/**
 * Общий контроллер для отчётов
 */
irisControllers.classes.ReportCardController = IrisCardController.extend({

  /**
   * Колонку таблицы фильтровать по таблице
   */
  filterTableColumn: function(target, reportField, columnField) {
    var form = $(this.el.id).down('form');
    if (reportField == undefined) {
      reportField = form.Report_TableID;
    }
    if (columnField == undefined) {
      columnField = form.ColumnID;
    }
    var d = '';
    if (target != '') {
      d = 'd';
      target = '_' + target;
    }
    $(columnField).setAttribute('filter_column', 'TableID');
    $(columnField).setAttribute('filter_value', 'null');

    Transport.request({
      section: 'Report' + target, 
      'class': d + 'c_Report' + target, 
      method: 'filterTableColumn', 
      parameters: {
        report_table_id: c_Common_GetElementValue(reportField),
        table_column_id: c_Common_GetElementValue(columnField)
      },
      onSuccess: function (transport) {
        var res = transport.responseText.evalJSON().data;
        $(columnField).setAttribute('filter_value', res.table_id);
        if (res.clear) {
          c_Common_SetElementValue(columnField, c_Common_MakeFieldValue('', ''));
        }
      }
    });
  }

});
