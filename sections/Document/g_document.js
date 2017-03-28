/**
 * Раздел "Документы". Таблица.
 */
irisControllers.classes.g_Document = IrisGridController.extend({

  onOpen: function () {
    // Печатные формы
    printform_createButton(this.el.id, T.t('Печать') + '&hellip;');
  }

});
