/**
 * Раздел "Накладные". Таблица.
 */
irisControllers.classes.g_FactInvoice = IrisGridController.extend({

  onOpen: function () {
    // Кнопка Создать...
    g_InsertUserButtons(this.el.id, [
      {
        name: T.t('Создать') + '&hellip;', 
        buttons: [
          {
            name: T.t('Платеж'), 
            onclick: "common_createFactInvoicePayment('grid', '" + this.el.id + "');"
          }
        ]
      }
    ], 'iris_FactInvoice');

    // Печатные формы
    printform_createButton(this.el.id, T.t('Печать') + '&hellip;');
  }

});
