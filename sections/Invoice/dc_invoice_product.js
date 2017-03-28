/**
 * Раздел "Счета". Вкладка "Продукты". Карточка.
 */
irisControllers.classes.dc_Invoice_Product = IrisCardController.extend({

  events: {
    'change #Price, #Count, #UnitID, #Discount': 'onChangeEvent',
    'lookup:changed #ProductID': 'onChangeEvent'
  },

  onOpen: function () {
    this.getField('Amount').attr('readonly', 'readonly');
  },

});
