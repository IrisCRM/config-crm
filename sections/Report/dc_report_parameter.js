/**
 * Скрипт карточки параметра отчёта
 */
irisControllers.classes.dc_Report_Parameter = IrisCardController.extend({

  events: {
    'lookup:changed #TableID': 'onChangeTableID',
    'change #TypeID': 'onChangeTypeID'
  },

  onChangeTableID: function(event) {
    var form = $(this.el.id).down('form');
    c_Common_LinkedField_OnChange(form, event.target.id, 
        null, true, function(res) {
          var type = form.TypeID.options[form.TypeID.selectedIndex]
              .getAttribute('systemcode');
          if ('guid' == type) {
            ShowValueFieldByType(res, form.TypeID.up('tr'), 2, 'Значение');
          }
        });
  },

  onChangeTypeID: function(event) {
    var form = $(this.el.id).down('form');
    c_Common_LinkedField_OnChange(form, event.target.id, 
        null, true, function(res) {
          // И менять поле для ввода значения
          ShowValueFieldByType(res, form.TypeID.up('tr'), 2, 'Значение');
        }, true);
  },

  onOpen: function() {
    var form = $(this.el.id).down('form');

    if ('insert' != form._mode.value) {
      // Если открываем существующую запись, то получим значение фильтра
      this.resultByColumn();
    }
  },

  /**
   * Получить и отобразить значение фильтра по типу колонки фильта
   */
  resultByColumn: function() {
    var form = $(this.el.id).down('form');
    var type = form.TypeID.options[form.TypeID.selectedIndex]
        .getAttribute('systemcode');
    // Если открываем существующую запись, то получим значение параметра
    Transport.request({
      section: 'Report', 
      'class': 'dc_Report_Parameter', 
      method: 'getParameterValue', 
      parameters: {
        id: form._id.value,
        type: type
      },
      onSuccess: function(transport) {
        var res = transport.responseText.evalJSON().data;
        ShowValueFieldByType(res, form.TypeID.up('tr'), 2, 'Значение');

        if ('guid' == res.ColumnInfo.Type) {
          c_Common_SetElementValue(form.GUIDValue, res.ColumnInfo.Value);
        }
        if (('date' == res.ColumnInfo.Type) || 
            ('datetime' == res.ColumnInfo.Type)) {
          c_Common_SetElementValue(form.DateValue, res.ColumnInfo.Value);
        }
        if ('int' == res.ColumnInfo.Type) {
          c_Common_SetElementValue(form.IntValue, res.ColumnInfo.Value);
        }
        if ('float' == res.ColumnInfo.Type) {
          c_Common_SetElementValue(form.FloatValue, res.ColumnInfo.Value);
        }
        if (('string' == res.ColumnInfo.Type) || 
            ('char' == res.ColumnInfo.Type) || 
            ('text' == res.ColumnInfo.Type)) {
          c_Common_SetElementValue(form.StringValue, res.ColumnInfo.Value);
        }

        form._hash.value = GetCardMD5(get_window_id(form));
      }
    });
  }

});
