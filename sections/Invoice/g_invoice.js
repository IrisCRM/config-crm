/**
 * Раздел "Счета". Таблица.
 */
irisControllers.classes.g_Invoice = IrisGridController.extend({

  onOpen: function () {
    // Кнопка Создать...
    g_InsertUserButtons(this.el.id, [
      {
        name: T.t('Создать') + '&hellip;', 
        buttons: [
          {
            name: T.t('Платеж'), 
            onclick: "common_createInvoicePayment('grid', '" + this.el.id + "');"
          },
          {
            name: T.t('Накладную'), 
            onclick: "common_createInvoiceFactInvoice('grid', '" + this.el.id + "');"
          },
          {
            name: T.t('Акт'), 
            onclick: "common_createInvoiceAct('grid', '" + this.el.id + "');"
          }
        ]
      }
    ], 'iris_Invoice');
    
    // Печатные формы
    this.drawPrintFormButton();
  }

});
