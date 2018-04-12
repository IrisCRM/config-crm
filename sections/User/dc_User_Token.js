/**
 * Скрипт карточки Токен
 */
irisControllers.classes.dc_User_Token = IrisCardController.extend({

  onOpen: function() {
    this.fieldProperty('code', 'readonly', true);
  },

});
