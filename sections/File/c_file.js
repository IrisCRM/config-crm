/**
 * Скрипт карточки файла
 */

irisControllers.classes.c_File = IrisCardController.extend({

  events: {
    'lookup:changed #ContactID, #ObjectID, #ProjectID': 'onChangeLookup',
    'lookup:changed #IssueID, #BugID, #IncidentID': 'onChangeLookup',
    'lookup:changed #OfferID, #PactID, #InvoiceID': 'onChangeLookup',
    'lookup:changed #PaymentID, #FactInvoiceID, #DocumentID': 'onChangeLookup',
    'lookup:changed #TaskID, #EmailID': 'onChangeLookup'
  },

  onChangeLookup: function(event) {
    c_Common_LinkedField_OnChange($(this.el).down('form'), event.target.id);
  },

  onOpen: function () {
    var form = $(this.el).down('form');    
    var parent_grid = $(form._parent_id.value);

    // Если родительского грида нет, то выйдем
    if (parent_grid == null) {
      return;
    }

    // Если это не закладка d_Email_File, товыйдем
    if (parent_grid.getAttribute('detail_name') != 'd_Email_File') {
      return;
    }

    if (form._mode.value != 'insert') {
      form.EmailID.setAttribute('readonly', 'readonly');
      form.EmailID_btn.setAttribute('disabled', 'disabled');
      
      // Если письмо не указано, значит это прикрепленный файл. 
      // Сделаем изменение самого файла в этом случае недоступным
      if (form.EmailID.value == '') {
        form.down('div.fileinput_caption').down('div.fileinput_clearButton').hide_();
        form.down('div.fileinput_blocker').hide_();
        var tr = $(form).down('input[type=file]').hide_().up('tr');
        $(tr.cells[0]).down('span').addClassName('card_elem_title').
            setAttribute('title', 
            'Этот файл прикреплен к письму, поэтому его нельзя изменинять');
        if ($(form.btn_ok)) {
          $(form.btn_ok).hide_();      
        }
      }
    }
  }

});
