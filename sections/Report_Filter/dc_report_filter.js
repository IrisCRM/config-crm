/**
 * Скрипт карточки фильтра отчёта
 */
irisControllers.classes.dc_Report_Filter = 
    irisControllers.classes.ReportCardController.extend({

  events: {
    'lookup:changed #Report_TableID': 'onChangeReportTableID',
    'change #sql': 'onChangesql',
    'lookup:changed #ColumnID': 'onChangeColumnID'
  },

  onChangeReportTableID: function() {
    this.filterTableColumn('Filter');
  },

  onChangesql: function() {
    var row = this.getField('Report_TableID').parents('.form_row');
    console.log(row);
    if (this.getField('sql').val()) {
      row.find('.form_table').hide();
      row.prev().find('.form_table').hide();
      row.next().find('.form_table').hide();
      row.next().next().find('.form_table').hide();
    }
    else {
      row.find('.form_table').show();
      row.prev().find('.form_table').show();
      row.next().find('.form_table').show();
      row.next().next().find('.form_table').show();
    }
  },

  onChangeColumnID: function(event) {
    var form = $(this.el.id).down('form');
    c_Common_LinkedField_OnChange(form, event.target.id, 
        null, false, function(res) {
          // И менять поле для ввода значения
          ShowValueFieldByType(res, form.condition.up('tr'), 2, 'Значение');
        });
  },

  onOpen: function() {
    var form = $(this.el.id).down('form');

    // Таблица фильтра фильтруется по ID отчёта
    this.bindFields('ReportID', 'Report_TableID');

    // В родительских фильтрах оставим только фильтры этого отчёта
    this.bindFields('ReportID', 'ParentFilterID');

    // Колонка графика фильтруется по ID таблицы графика
    this.filterTableColumn('Filter');

    if ('insert' != form._mode.value) {
      // Если открываем существующую запись, то получим значение фильтра
      this.resultByColumn();
    }

    this.onChangesql();
  },

  /**
   * Получить и отобразить значение фильтра по типу колонки фильта
   */
  resultByColumn: function() {
    var form = $(this.el.id).down('form');
    // Если открываем существующую запись, то получим значение фильтра
    Transport.request({
      section: 'Report_Filter', 
      'class': 'dc_Report_Filter', 
      method: 'getFilterValue', 
      parameters: {
        id: form._id.value,
        column_id: c_Common_GetElementValue(form.ColumnID)
      },
      onSuccess: function(transport) {
        var l_res = transport.responseText.evalJSON().data;
        ShowValueFieldByType(l_res, form.condition.up('tr'), 2, 'Значение');

        if ('guid' == l_res.ColumnInfo.Type) {
          c_Common_SetElementValue(form.GUIDValue, l_res.ColumnInfo.Value);
        }
        if (('date' == l_res.ColumnInfo.Type) || 
            ('datetime' == l_res.ColumnInfo.Type)) {
          c_Common_SetElementValue(form.DateValue, l_res.ColumnInfo.Value);
        }
        if ('int' == l_res.ColumnInfo.Type) {
          c_Common_SetElementValue(form.IntValue, l_res.ColumnInfo.Value);
        }
        if ('float' == l_res.ColumnInfo.Type) {
          c_Common_SetElementValue(form.FloatValue, l_res.ColumnInfo.Value);
        }
        if (('string' == l_res.ColumnInfo.Type) || 
            ('char' == l_res.ColumnInfo.Type) || 
            ('text' == l_res.ColumnInfo.Type)) {
          c_Common_SetElementValue(form.StringValue, l_res.ColumnInfo.Value);
        }

        form._hash.value = GetCardMD5(get_window_id(form));
      }
    });
  }

});
