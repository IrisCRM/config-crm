/**
 * Раздел "Проекты". Закладка "Пеимущества клеинта". Карточка.
 */
irisControllers.classes.dc_Project_Advantage = IrisCardController.extend({

  events: {
    'keyup #Count, #Value': 'onChangeAcounts'
  },

  onChangeAcounts: function() {
    var count = parseFloat(this.getField('Count').val());
    var value = parseFloat(this.getField('Value').val());

    if ((isNaN(count.toString())) || isNaN(value.toString())) {
      this.getField('Amount').val('');
    }
    else {
      this.getField('Amount').val((count * value).toFixed(2));
    }
  }
});