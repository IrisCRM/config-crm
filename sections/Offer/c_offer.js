/**
 * Скрипт карточки КП
 */

irisControllers.classes.c_Offer = IrisCardController.extend({

  events: {
    'lookup:changed #AccountID': 'updateName',
    'lookup:changed #ContactID, #ProjectID': 'onChangeLookup'
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

  onOpen: function() {
    this.getField('Number').attr('readonly', 'readonly');

    var form = $(this.el.id).down('form');
    if (form._mode.value != 'insert') {
      this.getField('Amount').attr('readonly', 'readonly');
      var self = this;

      Transport.request({
        section: 'Offer', 
        'class': 'c_Offer', 
        method: 'getEnabledFields', 
        parameters: {
          id: form._id.value
        },
        onSuccess: function (transport) {
          var res = transport.responseText.evalJSON();

          // раздизаблим
          if (res.data.EnabledFields.Amount) {
            self.getField('Amount').removeAttr('readonly');
          }
        }
      });

      addCardHeaderButton(this.el.id, 'top', [{
        name: T.t('Создать') + '&hellip;', 
        buttons: [
          {
            name: T.t('Договор'), 
            onclick: "if (common_cardIsSaved('" + this.el.id + "')) { " +
                "common_createOfferPact('card', '" + this.el.id + "'); }"
          },
          {
            name: T.t('Счет'), 
            onclick: "if (common_cardIsSaved('" + this.el.id + "')) { " + 
                "common_createOfferInvoice('card', '"+ this.el.id + "'); }"
          }
        ]
      }]);

      printform_createCardHeaderButton(this.el.id, 'top', 
          T.t('Печать') + '&hellip;');
    }
  }

});
