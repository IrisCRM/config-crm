/**
 * Скрипт карточки комплектации продукта
 */

irisControllers.classes.dc_Product_Constituent = IrisCardController.extend({

  events: {
    'keyup #Price, #Count': 'onChangeAmounts',
    'lookup:changed #ConstituentID': 'onChangeConstituentID'
  },

  onChangeAmounts: function () {
    var form = $(this.el.id).down('form');
    var count = parseFloat(form.Count.value) ;
    var price = parseFloat(form.Price.value);

    if ((count.toString() == "NaN") || (price.toString() == 'NaN')) {
      form.Amount.value = '';
    }
    else {
      form.Amount.value = (count * price).toFixed(2);
    }
  },

  onChangeConstituentID: function() {
    var form = $(this.el.id).down('form');
    c_Common_LinkedField_OnChange(form, 'ConstituentID', null, true, 
      function() { form.onChangeAmounts(); }, true);
  },

  onOpen: function () {
    this.getField('have').attr('readonly', 'readonly');
    this.getField('wait').attr('readonly', 'readonly');
    this.getField('reserve').attr('readonly', 'readonly');
  }

});
