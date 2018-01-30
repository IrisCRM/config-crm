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
    this.onChangeEvent(event, {
      disableEvents: true,
      rewriteValues: false,
      letClearValues: false
    });
  },

  onOpen: function () {
    var parentGrid = jQuery("#" + this.parameter("parent_id"));

    // Если родительского грида нет, то выйдем
    if (!parentGrid.length) {
      return;
    }

    // Если это не закладка d_Email_File, товыйдем
    if (parentGrid.attr("detail_name") != 'd_Email_File') {
      return;
    }

    if (this.parameter("mode") != 'insert') {

      this.fieldProperty('EmailID', 'readonly', true);
      
      // Если письмо не указано, значит это прикрепленный файл. 
      // Скроем кнопку "ОК"
      if (!this.fieldValue("EmailID")) {
        this.$el.find("#btn_ok").hide();
      }
    }
  }

});
