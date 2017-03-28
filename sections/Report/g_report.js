/**
 * Раздел "Отчеты". Таблица.
 */
irisControllers.classes.g_Report = IrisGridController.extend({

  // Инициализация таблицы
  onOpen: function () {
    // Добавим кнопки на панель грида
    g_InsertUserButtons(this.el.id, [
      {
        name: T.t('Копировать'), 
        onclick: "irisControllers.objects.g_Report" + this.el.id + ".copyReport();"
      },
      {
        name: T.t('Печать'), 
        onclick: "irisControllers.objects.g_Report" + this.el.id + ".printReport();"
      }
    ], 'iris_Report');    
  },

  printReport: function() {
    var GridInfo = new Array();
    GridInfo = GetGridInfo(this.el.id, 'update');
    var record_id = GridInfo['selected_rec_id'];

    showParamsWindow({
     reportid: record_id
    }); // common/Lib/reportlib.js
  },

  copyReport: function(p_confirm_flag) {
    var p_grid_id = this.el.id;
    var self = this;
    if (p_confirm_flag == undefined) {
      p_confirm_flag = 0;
    }

    if (p_confirm_flag != 1) {
      Dialog.confirm(T.t('Вы уверены, что хотите скопировать данный отчет?'), {
        onOk: function() {
          self.copyReport(1); 
          Dialog.closeInfo();
        }, 
        className: "iris_win", 
        width: 300, 
        height: null, 
        buttonClass: "button", 
        okLabel: "Да", 
        cancelLabel: "Нет"
      });
      return;
    }

    var report_id = getGridSelectedID(p_grid_id);
    if (report_id == '') {
      wnd_alert(T.t('Нужно выбрать отчет'));
      return;
    }

    Transport.request({
      section: 'Report', 
      'class': 'g_Report', 
      method: 'copyReport', 
      parameters: {
        id: report_id
      },
      onSuccess: function (transport) {
        var result = transport.responseText;
        if (result.isJSON() == true) {
          var result = result.evalJSON().data;
          var messageHTML = result.message;
          if (result.success == '1') {
            redraw_grid(p_grid_id);
          }
        }
        else {
          messageHTML = T.t('Возникла ошибка при копировании заказа');
        }
        wnd_alert(messageHTML);
      }
    });
  }

});
