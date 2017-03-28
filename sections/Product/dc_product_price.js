/**
 * Скрипт карточки цены продукта
 */

irisControllers.classes.dc_Product_Price = IrisCardController.extend({

  events: {
    'lookup:changed #ProductID': 'onChangeProductID'
  },

  onChangeProductID: function() {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), 'ProductID', 
        null, true); 
  }

});
