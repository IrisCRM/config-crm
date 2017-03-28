/**
 * Скрипт карточки поставщика продукта
 */

irisControllers.classes.dc_Product_Account = IrisCardController.extend({

  events: {
    'keyup #Price': 'onChangePrice',
    'lookup:changed #ProductID': 'onChangeProductID'
  },

  onChangePrice: function () {
    $(this.el.id).down('form').ActualityDate.value = iris_GetCurrentDate();
  },

  onChangeProductID: function() {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), 'ProductID', 
      null, true, undefined, true);
  }

});

