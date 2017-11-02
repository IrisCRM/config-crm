/**
 * Скрипт карточки раздела Таблица
 */

irisControllers.classes.c_Table = IrisCardController.extend({

  onOpen: function () {
    // при нажатии на название поля "Справочник (код)" откроем справочник
    var card_form = document.getElementById(this.el.id).getElementsByTagName("form")[0];
    var dictionaryLabel = this.getFieldLabel("Dictionary");
    var dictionaryCode = this.fieldValue("Dictionary");

    dictionaryLabel.css({"cursor": 'pointer', "color": "#3E569C"}).
      on('click', function() {
        if (dictionaryCode != '') {
          opengridwindow("", "", "dict", dictionaryCode);
        }
      });

    if (this.parameter('mode') === 'insert') {
      this.getField('ShowColumnID').attr('readonly', true);
    }
    if (this.parameter('mode') === 'update') {
      // Фильтрация для поля "Отображать колонку"
      this.getField('ShowColumnID').attr('filter_where', "T0.tableid = '" + this.getField('_id').val() + "'");

      addCardHeaderButton(this.el.id, 'top', 'Создать справочник',
        'irisControllers.objects.c_Table' + this.el.id + '.createDictXmlAsk()',
        'Если таблица является справочником (заполнено поле "Справочник (код)"), ' +
        'то будет создано xml описание справочника'
      );
      addCardHeaderButton(this.el.id, 'top', 'Скопировать права',
		  'irisControllers.objects.c_Table' + this.el.id + '.copyAccessAsk()',
        'Скопировать права по умолчанию от этой таблицы во все остальные таблицы, ' +
        'у которых учитывается доступ по записям. Старые права по умолчанию при этом будут удалены'
      );
    }
  },

  copyAccessAsk: function() {
    if (g_session_values.userrolecode != 'admin') {
      wnd_alert('Данная функция доступна только администраторам');
      return;
    }
  
    var div_id = (Math.random()+"").slice(3);
    var self = this;
    Dialog.confirm("Права доступа по умолчанию от таблицы <br><b> " + this.getField('Code').val() + "</b>" +
        "<br> будут установлены для <b><span id='" + div_id + "'>**</span></b> таблиц," +
        "<br> у которых включен доступ по записям. Продолжить?", {
      onOk: function() {
        Dialog.closeInfo();
        self.copyAccess(self.getField('_id').val());
      },
      className: "iris_win",
      width: 300,
      buttonClass: "button",
      okLabel: "Да",
      cancelLabel: "Нет"
    });

    this.request({
      method: 'GetTableCount',
      parameters: {
        id: this.parameter('id')
      },
      onSuccess: function (transport) {
        var count;
        try {
          var result = transport.responseText.evalJSON();
          count = result.data.message;
        } catch (e) {
          count = '';
        }
        jQuery('#' + div_id).html(count);
      }
    });
  },

  copyAccess: function(p_table_id) {
    console.log(p_table_id);
    Dialog.info('Идет копирование...', {
      width: 250,
      height: 60,
      className: "iris_win",
      showProgress: true
    });

    this.request({
      method: 'CopyAccessDefault',
      parameters: {
        'table_id': p_table_id
      },
      onSuccess: function(transport) {
        Dialog.closeInfo();
        try {
          var result = transport.responseText.evalJSON();
          wnd_alert(result.data.message, 350);
        } catch (e) {
          wnd_alert('Не удалось скопировать права по умолчанию', 350);
        }
      }
    });
  },
  
  createDictXmlAsk: function(p_exec_flag) {
    if (g_session_values.userrolecode != 'admin') {
      wnd_alert('Данная функция доступна только администраторам');
      return;
    }
  
    var table_code = this.getField('Code').val();
    var table_name = this.getField('Name').val();
    var dict_code = this.getField('Dictionary').val();
    if (dict_code == '') {
      wnd_alert('Не заполнено поле "Справочник (код)"');
      return;
    }
    var self = this;

    this.request({
      method: 'GetDictStatus',
      parameters: {
        'dict_code': dict_code
      },
      onSuccess: function(transport) {
        var result = transport.responseText.evalJSON();
        if (result.data.success == '0') {
          wnd_alert(result.data.errm);
          return;
        }
  
        Dialog.confirm("Будет создан справочник <b>" + dict_code + "</b> для таблицы <b>" + table_code + "</b>", {
          onOk: function() {
            Dialog.closeInfo();
            self.createDictXml(table_code, table_name, dict_code);
          },
          className: "iris_win",
          width: 300,
          buttonClass: "button",
          okLabel: "Продолжить",
          cancelLabel: "Отмена"
        });
      }
    });
  },
  
  createDictXml: function(p_table_code, p_table_name, p_dict_code) {
    this.request({
      method: 'CreateNewDict',
      parameters: {
        'table_code': p_table_code,
        'table_name': p_table_name,
        'dict_code': p_dict_code
      },
      onSuccess: function(transport) {
        var result = transport.responseText.evalJSON();
        if (result.data.success == '0') {
          wnd_alert(result.data.errm);
          return;
        }
        wnd_alert(result.data.message);
      }
    });
  }

});
