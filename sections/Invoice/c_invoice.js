/**
 * Скрипт карточки счета
 */

irisControllers.classes.c_Invoice = IrisCardController.extend({

  events: {
    'lookup:changed #AccountID, #ContactID': 'onChangeLookup',
    'lookup:changed #ProjectID, #OfferID, #PactID': 'onChangeLookup',
    'change #InvoiceStateID': 'onChangeInvoiceStateID',
    'change #PaymentAmount': 'onChangePaymentAmount'
  },

  /**
   * Название
   */
  updateName: function() {
    var l_tire = (this.getField('AccountID').val() == '') || 
        (this.getField('Number').val() == '') ? '' : ' - ';
    this.getField('Name').val(this.getField('Number').val() + 
        l_tire + this.getField('AccountID').val());
  },

  onChangeLookup: function(event) {
    var self = this;
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id,
        null, false, function() { self.updateName(); } );
  },

  onChangeInvoiceStateID: function(event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id);
  },

  onChangePaymentAmount: function(event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id, 
        null, true);
  },

  onOpen: function() {
    this.getField('Number').attr('readonly', 'readonly');

    var form = $(this.el.id).down('form');
    bind_lookup_element(form.AccountID, form.Account_PropertyID, 'AccountID');

    if (form._mode.value != 'insert') {
      this.getField('Amount').attr('readonly', 'readonly');
      this.getField('PaymentAmount').attr('readonly', 'readonly');
      var self = this;

      Transport.request({
        section: 'Invoice', 
        'class': 'c_Invoice', 
        method: 'getFieldProperties', 
        parameters: {
          id: form._id.value
        },
        onSuccess: function (transport) {
          var res = transport.responseText.evalJSON().data;

          // Раздизаблим
          if (res.EnabledFields.Amount) {
            self.getField('Amount').removeAttr('readonly');
          }
          if (res.EnabledFields.PaymentAmount) {
            self.getField('PaymentAmount').removeAttr('readonly');
          }

          // Фильтрация своих реквизитов по компании
          var AccountID = res.FilterFields.AccountID;
          if (AccountID) {
            self.getField('Your_PropertyID').attr('filter_column', 'AccountID');
            self.getField('Your_PropertyID').attr('filter_value', AccountID);
          }
        }
      });

      addCardHeaderButton(this.el.id, 'top', [{
        name: T.t('Создать') + '&hellip;', 
        buttons: [
          {
            name: T.t('Платеж'), 
            onclick: "if (common_cardIsSaved('" + this.el.id + "')) { " +
                "common_createInvoicePayment('card', '" + this.el.id + "'); }"
          },
          {
            name: T.t('Накладную'), 
            onclick: "if (common_cardIsSaved('" + this.el.id + "')) { " + 
                "common_createInvoiceFactInvoice('card', '"+ this.el.id + "'); }"
          },
          {
            name: T.t('Акт'), 
            onclick: "if (common_cardIsSaved('" + this.el.id + "')) { " + 
                "common_createInvoiceAct('card', '"+ this.el.id + "'); }"
          }
        ]
      }]);
      printform_createCardHeaderButton(this.el.id, 'top', 
          T.t('Печать') + '&hellip;');
    }
  }

});
