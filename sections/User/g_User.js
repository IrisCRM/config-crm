/**
 * Раздел "Пользователи". Таблица.
 */
irisControllers.classes.g_User = IrisGridController.extend({
  onOpen: function () {
    g_InsertUserButtons(this.el.id, [
      {
        name: T.t('Принудительный выход'), 
        onclick: "irisControllers.objects.g_User" + this.el.id + ".forcedLogout();"
      }
    ], 'iris_Contact');
  },

  forcedLogout: function () {
    var gridId = this.el.id;
    var userId = getGridSelectedID(gridId);
    if (userId == '') {
      wnd_alert(T.t('Нужно выбрать пользователя'));
      return;
    }

    Transport.request({
      section: 'User', 
      'class': 'g_User', 
      method: 'forcedLogout', 
      parameters: {
        userId: userId
      },
      onSuccess: function (transport) {
        var result = transport.responseText;
        if (result.isJSON() == true) {
          var result = result.evalJSON().data;
          if (result.isSuccess) {
            redraw_grid(gridId);
            return;
          }
        }
        wnd_alert(T.t('Ошибка'));
      }
    });
  }

});
