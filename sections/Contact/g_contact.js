/**
 * Раздел "Контакты". Таблица.
 */
irisControllers.classes.g_Contact = IrisGridController.extend({

  // Инициализация таблицы
  onOpen: function () {
    printform_createButton(this.el.id, T.t('Печать') + '&hellip;');
  }

});
