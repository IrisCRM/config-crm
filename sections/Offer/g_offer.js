/**
 * Раздел "КП". Таблица.
 */
irisControllers.classes.g_Offer = IrisGridController.extend({

  onOpen: function () {
    // Добавим кнопки на панель грида
    g_InsertUserButtons(this.el.id, [
      {
        name: T.t('Создать') + '&hellip;', 
        buttons: [
          {
            name: T.t('Договор'), 
            onclick: "common_createOfferPact('grid', '" + this.el.id + "');"
          },
          {
            name: T.t('Счет'), 
            onclick: "common_createOfferInvoice('grid', '" + this.el.id + "');"
          }
        ]
      }
    ], 'iris_Offer');
    
    // Печатные формы
    printform_createButton(this.el.id, T.t('Печать') + '&hellip;');
  }

});
