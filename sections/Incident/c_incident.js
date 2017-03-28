/**
 * Скрипт карточки инцидента
 */

irisControllers.classes.c_Incident = IrisCardController.extend({

  events: {
    'lookup:changed #ContactID, #InformID, #ObjectID': 'onChangeLookup',
    'lookup:changed #IssueID, #ProjectID, #TaskID': 'onChangeLookup',
    'lookup:changed #OfferID, #PactID, #InvoiceID': 'onChangeLookup',
    'lookup:changed #PaymentID, #FactInvoiceID, #DocumentID': 'onChangeLookup',
    'change #IncidentStateID': 'onChangeIncidentStateID',
    'change #Date, #AnswerDate': 'onChangeIsRemind',
    'change #RemindDate': 'onChangeRemindDate',
    'change #IsRemind': 'onChangeIsRemind'
  },

  onChangeLookup: function(event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id);
  },

  onChangeIncidentStateID: function(event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id);
  },

  onChangeIsRemind: function() {
    c_Common_IsRemind_OnChange($(this.el.id).down('form'), 
        Array('Date', 'AnswerDate'), 0);
  },

  onChangeRemindDate: function() {
    c_Common_RemindDate_OnChange($(this.el.id).down('form'));
  },

  onOpen: function() {
    this.getField('Number').attr('readonly', 'readonly');
  }

});
