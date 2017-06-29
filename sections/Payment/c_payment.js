/**
 * Скрипт карточки платежа
 */

irisControllers.classes.c_Payment = IrisCardController.extend({

  events: {
    'lookup:changed #ContactID, #ProjectID': 'onChangeEvent',
    'lookup:changed #InvoiceID, #PactID, #FactInvoiceID': 'onChangeEvent',
    'lookup:changed #AccountID': 'onChangeAccountID',
    'change #PaymentTypeID': 'onChangePaymentTypeID',
    'change #PaymentStateID': 'onChangePaymentStateID',
    'change #Amount': 'onChangeAmount'
  },

  /**
   * Краткое описание: <Номер> - <Компания>
   */
  updateName: function() {
    var l_tire = (this.getField('AccountID').val() == '') || 
        (this.getField('Number').val() == '') ? '' : ' - ';
    this.getField('Name').val(this.getField('Number').val() + l_tire + 
        this.getField('AccountID').val());
  },

  onChangeAccountID: function () {
    this.updateName(); 
    // Поле Контакт зависит от поля Компания
    this.bindFields('AccountID', 'ContactID');
  },

  onChangePaymentTypeID: function () {
    var p_form = $(this.el).down('form');
    try {
      var code_str = p_form.PaymentTypeID.options[p_form.PaymentTypeID.selectedIndex].getAttribute('code');
      if ('In' == code_str) {
        this.fieldLabel('isCash', T.t('С кошелька'));
      }
      else {
        this.fieldLabel('isCash', T.t('На кошелек'));
      }
    } catch (e) {};
  },

  onChangePaymentStateID: function(event) {
    c_Common_LinkedField_OnChange($(this.el).down('form'), event.target.id);
  },

  onChangeAmount: function(event) {
    c_Common_LinkedField_OnChange($(this.el).down('form'), event.target.id, 
      null, true);
  },

  onOpen: function () {
    this.getField('Number').attr('readonly', 'readonly');
    // Поле Контакт зависит от поля Компания
    this.bindFields('AccountID', 'ContactID');
    this.onChangePaymentTypeID();
  }

});
