/**
 * Раздел "Мероприятия". Вкладка "Продукты". Карточка.
 */
irisControllers.classes.dc_Marketing_Product = IrisCardController.extend({

  events: {
    'change #Price, #Count, #UnitID': 'onChangeEvent',
    'lookup:changed #ProductID': 'onChangeEvent'
  },

  onOpen: function () {
    this.getField('Amount').attr('readonly', 'readonly');
  },

});
