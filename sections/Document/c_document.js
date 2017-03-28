/**
 * Раздел "Документы". Карточка.
 */
irisControllers.classes.c_Document = IrisCardController.extend({

  events: {
    'lookup:changed #AccountID, #ContactID': 'onChangeLookup',
    'lookup:changed #ProjectID, #PactID': 'onChangeLookup'
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

  onOpen: function(p_wnd_id) {
    this.getField('Number').attr('readonly', 'readonly');

    var form = $(this.el.id).down('form');
    bind_lookup_element(form.AccountID, form.Account_PropertyID, 'AccountID');

    if (form._mode.value != 'insert') {
      this.getField('Amount').attr('readonly', 'readonly');
      printform_createCardHeaderButton(this.el.id, 'top', 
          T.t('Печать') + '&hellip;');
    }

    bind_select_element(form.DocumentTypeID, form.DocumentStateID, 
        'DocumentTypeID');

    var self = this;
    Transport.request({
      section: 'Document', 
      'class': 'c_Document', 
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

        // Фильтрация своих реквизитов по компании
        var AccountID = res.FilterFields.AccountID;
        if (AccountID) {
          self.getField('Your_PropertyID').attr('filter_column', 'AccountID');
          self.getField('Your_PropertyID').attr('filter_value', AccountID);
        }
      }
    });
  }

});