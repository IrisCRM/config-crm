/**
 * Скрипт карточки печатной формы
 */

irisControllers.classes.c_Printform = IrisCardController.extend({

  events: {
      'radiobutton:changed #IsTextUse': 'toggleFields'
  },

  toggleFields: function() {
    var textflag = parseInt(this.fieldValue('IsTextUse'), 10);
    this.fieldProperty('PrintFormText', 'required', textflag);
    this.fieldProperty('printform_filename', 'required', (!textflag));
    this.fieldProperty('printform_file', 'required', (!textflag));
  },

  onOpen: function() {
    if (this.parameter('mode') == 'insert') {
      this.fieldValue('IsTextUse', 1);
      this.fieldValue('displayinsection', 1);
    }

    var filename = this.getField('printform_filename');
    if (filename) {
      filename.parents('tr.form_row').next().find('td[colspan=3]')
          .html(T.t('Файл используется для генерации печатной формы, если поле "Использовать текст" имеет значение "Нет"'));
    }

    this.toggleFields();
  }
});