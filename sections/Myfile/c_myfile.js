/**
 * Скрипт карточки файла
 */

irisControllers.classes.c_Myfile = IrisCardController.extend({

  events: {
    'lookup:changed #ContactID, #ProjectID': 'onChangeLookup'
  },

  onChangeLookup: function(event) {
    c_Common_LinkedField_OnChange($(this.el).down('form'), event.target.id);
  },

  onOpen: function () {
    var form = $(this.el).down('form');    

    // Закрываем некоторые поля
    form.AccountID.setAttribute('readonly', 'readonly');
    form.AccountID_btn.setAttribute('disabled', 'true'); 
    form.ContactID.setAttribute('readonly', 'readonly');
    form.ContactID_btn.setAttribute('disabled', 'true'); 
    form.OwnerID.setAttribute('readonly', 'readonly');
    form.OwnerID_btn.setAttribute('disabled', 'true'); 
    form.Date.setAttribute('readonly', 'readonly');

    if (form._mode.value == 'insert') {
      // Контакт = текущий пользователь. Дизаблим.
      form['ContactID'].setAttribute('original_value', 
          g_session_values['username']);
      form['ContactID'].value = g_session_values['username'];
      SetLookupValue(form['ContactID'], g_session_values['userid']);
    }
  }

});
