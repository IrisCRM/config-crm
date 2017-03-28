/**
 * Скрипт карточки продукта
 */

irisControllers.classes.c_Product = IrisCardController.extend({

  events: {
      'keyup #Price': 'onChangePrice',
      'keyup #Cost': 'onChangeCost'
  },

  onChangePrice: function () {
    $(this.el).down('form').PriceDate.value = iris_GetCurrentDate();
  },

  onChangeCost: function (event) {
    $(this.el).down('form').CostDate.value = iris_GetCurrentDate();
  },

  onOpen: function () {
    this.getField('have').attr('readonly', 'readonly');
    this.getField('wait').attr('readonly', 'readonly');
    this.getField('reserve').attr('readonly', 'readonly');
  }

});
