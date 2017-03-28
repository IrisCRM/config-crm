/**
 * Раздел "Договоры". Таблица.
 */
irisControllers.classes.g_Pact = IrisGridController.extend({

  onOpen: function () {
    // Кнопка Создать...
    g_InsertUserButtons(this.el.id, [
      {
        name: T.t('Создать') + '&hellip;', 
        buttons: [
          {
            name: T.t('Счет'), 
            onclick: "common_createPactInvoice('grid', '" + this.el.id + "');"
          },
          {
            name: T.t('Акт'), 
            onclick: "common_createPactAct('grid', '" + this.el.id + "');"
          }
        ]
      }
    ], 'iris_Pact');

    // Печатные формы
    printform_createButton(this.el.id, T.t('Печать') + '&hellip;');
  }

});
