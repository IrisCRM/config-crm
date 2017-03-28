/**
 * Раздел "Проекты". Вкладка "Продукты". Карточка.
 */
irisControllers.classes.dc_Project_Product = IrisCardController.extend({

  events: {
    'field:edited #Price, #Cost, #Discount': 'onChangeOnce',
    'field:edited #Count, #TimeUnit, #Duration, #UnitID': 'onChangeOnce',
    'field:edited #StartDate': 'onChangeOnce',
    'field:edited #ProductID': 'onChangeOnce'
  },

  onChangeOnce: function(event) {
    this.onChangeEvent(event, {
      disableEvents: true
    });
  },

  onOpen: function() {
    this.getField('CostAmount').attr('readonly', 'readonly');
    this.getField('PriceAmount').attr('readonly', 'readonly');
    this.getField('Amount').attr('readonly', 'readonly');
  },

});
