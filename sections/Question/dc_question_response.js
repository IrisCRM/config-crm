/**
 * Скрипт карточки справочника Стандартные ответы
 */
irisControllers.classes.dc_Question_Response = IrisCardController.extend({

  events: {
    'lookup:changed #QuestionID': 'onChangeQuestionID'
  },

  onChangeQuestionID: function(event) {
    var self = this;
    this.onChangeEvent(event, {
      disableEvents: true,
      onApply: function() {
        self.showhide();
      }
    });
  },

  onOpen: function() {
    this.showhide();
  },

  showhide: function() {
    var p_form = $(this.el.id).down('form');
    //Получить код типа ответа на вопрос, скрыть ненужные типы ответов и показать нужные
    Transport.request({
      section: 'Question',
      'class': 'ds_Question_Response',
      method: 'GetQuestionInfo',
      parameters: {
        '_p_questionid': p_form.QuestionID.getAttribute('lookup_value')
      },
      onSuccess: function(transport) {
        var result = transport.responseText.evalJSON().data;
        var code = result.Params.ResponseTypeCode;

        $(p_form.stringvalue.up('.form_row')).style.display = 'none';
        $(p_form.intvalue.up('.form_row')).style.display = 'none';
        $(p_form.floatvalue.up('.form_row')).style.display = 'none';
        $(p_form.datevalue.up('.form_row')).style.display = 'none';
        $(p_form.datetimevalue.up('.form_row')).style.display = 'none';

        if (('String' == code) || ('Single' == code) || ('Multi' == code)) {
          $(p_form.stringvalue.up('.form_row')).style.display = 'table-row';
        }
        if ('Int' == code) {
          $(p_form.intvalue.up('.form_row')).style.display = 'table-row';
        }
        if ('Float' == code) {
          $(p_form.floatvalue.up('.form_row')).style.display = 'table-row';
        }
        if ('Date' == code) {
          $(p_form.datevalue.up('.form_row')).style.display = 'table-row';
        }
        if ('Datetime' == code) {
          $(p_form.datetimevalue.up('.form_row')).style.display = 'table-row';
        }
      }
    });
  }

});


