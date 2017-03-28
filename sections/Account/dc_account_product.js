/**
 * Раздел Компании. Закладка Продукты.
 */

irisControllers.classes.dc_Account_Product = IrisCardController.extend({
  
  // Определяем события
  events: {
      'change #Price': 'changedPrice',
      'lookup:changed #ProductID': 'changedProduct'
  },

  changedPrice: function () {
    this.$el.find('#ActualityDate').val(iris_GetCurrentDate());
  },

  changedProduct: function() {
    c_Common_LinkedField_OnChange($(this.el).down('form'), 'ProductID', 
      null, true, undefined, true);
    this.changedPrice();
  }

});