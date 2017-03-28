/**
 * Скрипт карточки выпуска
 */

irisControllers.classes.c_Issue = IrisCardController.extend({

  events: {
      'lookup:changed #ProductID': 'updateName',
      'keyup #Version': 'updateName',
      'change #IssueStateID': 'onChangeIssueStateID'
  },

  /**
   * Название = "<Продукт> - <Версия>"
   */
  updateName: function() {
    var l_tire = (this.getField('ProductID').val() == '') || (this.getField('Version').val() == '') ? '' : ' - ';
    this.getField('Name').val(this.getField('ProductID').val() + 
        l_tire + this.getField('Version').val());
  },

  onChangeIssueStateID: function(event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id);
  },

  onOpen: function() {
	if ($(this.el).down('form')._mode.value == 'insert') {
      this.updateName();
    }
  }

});
