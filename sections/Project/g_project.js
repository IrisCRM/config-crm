/**
 * Раздел "Проекты". Таблица.
 */
irisControllers.classes.g_Project = IrisGridController.extend({

  // Инициализация таблицы
  onOpen: function () {
    // Добавим кнопки на панель грида
    g_InsertUserButtons(this.el.id, [
        {
          name: T.t('Копировать'), 
          onclick: "irisControllers.objects.g_Project" + this.el.id + ".copyProject(0);"
        },
      {
        name: T.t('Создать') + '&hellip;', 
        buttons: [
          {
            name: T.t('Коммерческое предложение'), 
            onclick: "common_createProjectOffer('grid', '" + this.el.id + "');"
          },
          {
            name: T.t('Договор'), 
            onclick: "common_createProjectPact('grid', '" + this.el.id + "');"
          },
          {
            name: T.t('Счет'), 
            onclick: "common_createProjectInvoice('grid', '" + this.el.id + "');"
          }
        ]
      }
    ], 'iris_Project');

    // Печатные формы
    printform_createButton(this.el.id, T.t('Печать') + '&hellip;');
  },


  copyProject: function(p_confirm_flag) {
    var p_grid_id = this.el.id;
    var self = this;
    if (p_confirm_flag != 1) {
      Dialog.confirm(T.t('Вы уверены, что хотите скопировать данный заказ?'), {
        onOk: function() { 
          self.copyProject(1); 
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

    var project_id = getGridSelectedID(p_grid_id);
    if (project_id == '') {
      wnd_alert(T.t('Нужно выбрать заказ'));
      return;
    }
    
    Transport.request({
      section: 'Project', 
      'class': 'g_Project', 
      method: 'copyProject', 
      parameters: {
        id: project_id
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
