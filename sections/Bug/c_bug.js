/**
 * Скрипт карточки замечания
 */

irisControllers.classes.c_Bug = IrisCardController.extend({

  events: {
    'change #FindDate, #EditDate, #VerifyDate': 'onChangeDate',
    'lookup:changed #FindID, #EditID, #VerifyID': 'onChangeLookup',
    'change #BugStateID': 'onChangeBugStateID',
    'change #Date, #AnswerDate': 'onChangeIsRemind',
    'change #RemindDate': 'onChangeRemindDate',
    'change #IsRemind': 'onChangeIsRemind'
  },

  onChangeDate: function(event) {
    this.onChangeIsRemind(event);
    this.onChangeLookup(event);
  },

  onChangeLookup: function(event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id);
  },

  onChangeBugStateID: function(event) {
    var form = $(this.el.id).down('form');
    var BugStateCode = form.BugStateID.options[form.BugStateID.selectedIndex].getAttribute('code');
    // если состояние исправлено, то заполним дату исправления и через нее исправил
    if (BugStateCode == 'Finish') {
      form.EditDate.value = iris_GetCurrentDate();
      c_Common_LinkedField_OnChange(form, 'EditDate');
      this.onChangeIsRemind();
    }
    // если состояние проверено, то заполним дату проверки и через нее проверил
    if (BugStateCode == 'Checked') {
      form.VerifyDate.value = iris_GetCurrentDate();
      c_Common_LinkedField_OnChange(form, 'VerifyDate');
      this.onChangeIsRemind();
    }
  },

  onChangeIsRemind: function() {
    c_Common_IsRemind_OnChange($(this.el.id).down('form'), 
        Array('FindDate', 'EditDate', 'VerifyDate'), -60*10);
  },

  onChangeRemindDate: function() {
    c_Common_RemindDate_OnChange($(this.el.id).down('form'));
  },

  onOpen: function() {
    this.getField('Number').attr('readonly', 'readonly');
  }

});
