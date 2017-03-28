/**
 * Скрипт карточки решения
 */
irisControllers.classes.c_Answer = IrisCardController.extend({

  onOpen: function() {
    this.getField('Number').attr('readonly', 'readonly');
  }

});
