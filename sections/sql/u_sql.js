/**
 * Скрипт раздела SQL
 */

irisControllers.classes.u_sql = IrisCardController.extend({

  onOpen: function() {
    Transport.request({
      'section': 'sql',
      'class': 'u_sql',
      'method': 'onPrepare',
      onSuccess: function(transport) {
        var text = transport.responseText.evalJSON().data;
        var text_txt = '';
        if (text['error']) {
          text_txt += text['error'] + '<br>';
        }
        if (text['html']) {
          text_txt += text['html'];
        }
        g_Prepare_Custom_Section(text_txt);
      }
    });
  },

  runSQL: function() {
    var result_area = this.getField('u_sql_sqlresult');
    Transport.request({
      'section': 'sql',
      'class': 'u_sql',
      'method': 'RunSQL',
      'parameters': {
        'sql': this.fieldValue('sql')
      },
      onSend: function() {
        result_area.html(T.t('Выполнение SQL скрипта...'));
      },
      onSuccess: function(transport) {
        var text = transport.responseText.evalJSON().data;
        result_area.html(text.error + '<br>' + text.html);
      },
      onFail: function(transport) {
        result_area.html(T.t('Возникла ошибка при отправке запроса на сервер'));
      }
    });
  }

});
