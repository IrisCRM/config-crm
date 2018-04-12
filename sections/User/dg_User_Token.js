/**
 * Вкладка "Токены". Таблица.
 */
irisControllers.classes.dg_User_Token = IrisGridController.extend({
  onOpen: function () {
    this.getFooterButtons("delete").hide();
  },
});
