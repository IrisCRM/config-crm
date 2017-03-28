/**
 * Раздел "Интервью". Вкладка "Ответы". Карточка.
 */
irisControllers.classes.dc_Interview_Response = IrisCardController.extend({

  events: {
    'field:edited #ResponseID': 'onChangeEvent',
    'field:edited #PollQuestionID': 'onChangePollQuestionID',

    'field:edited #intvalue': 'onChangeIntValue',
    'field:edited #floatvalue': 'onChangeFloatValue',
    'field:edited #datevalue': 'onChangeDateValue',
    'field:edited #datetimevalue': 'onChangeDatetimeValue',
    'field:edited #stringvalue': 'onChangeStringValue'
  },

  onOpen: function() {
    var self = this;
    var p_wnd_id = this.el.id;
    //Форма карточки
    var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

    if (IsEmptyValue(card_form.PollQuestionID.getAttribute('lookup_value'))) {
      wnd_alert('Опрос завершён');
    }

    var interviewid = card_form.InterviewID.getAttribute('lookup_value');
    Transport.request({
      section: 'Interview',
      'class': 'dc_Interview_Response',
      method: 'GetInterviewParams',
      parameters: {
        '_p_id': interviewid
      },
      onSuccess: function(transport) {
        var result = transport.responseText.evalJSON().data;
        var pollid = result.Params.PollID;
        card_form.PollQuestionID.setAttribute('filter_where',
            " T0.id not in (select pollquestionid from iris_interview_response where interviewid = '"+interviewid+"')"+
            " and T0.pollid = '"+pollid+"'");
        self.showhide();
      }
    });

    bind_lookup_element(card_form.QuestionID, card_form.ResponseID, 'QuestionID');

    if (card_form._mode.value != 'insert') {
      this.showhide();
    }

    //Скрыть QuestionID
    var row = card_form.QuestionID.up('.form_row');
    jQuery(row).find('.form_table').hide();
  },

  onChangeIntValue: function(event) {
    this.calcmark(this.FieldValue('intvalue'), 'int');
  },
  onChangeFloatValue: function(event) {
    this.calcmark(this.FieldValue('floatvalue'), 'float');
  },
  onChangeDateValue: function(event) {
    this.calcmark(this.FieldValue('datevalue'), 'date');
  },
  onChangeDatetimeValue: function(event) {
    this.calcmark(this.FieldValue('datetimevalue'), 'datetime');
  },
  onChangeStringValue: function(event) {
    this.calcmark(this.FieldValue('stringvalue'), 'string');
  },

  /**
   * Подсчитать оценку в зависимости от ответа
   */
  calcmark: function (p_value, p_type) {
    var p_form = document.getElementById(this.el.id).getElementsByTagName("form")[0];
    var values = p_form._response_values.value.evalJSON();
    for (var i=0; i<values.length; i++) {
      if ((((p_type == 'string') || (p_type == 'guid')) && values[i].Value == p_value)
          || (p_type == 'int' && parseInt(p_value) <= parseInt(values[i].Value))
          || (p_type == 'float' && parseFloat(p_value) <= parseFloat(values[i].Value))
          || (((p_type == 'date') || (p_type == 'datetime')) && this.StrToDate(p_value) <= this.StrToDate(values[i].Value))
      ) {
        p_form.mark.value = values[i].ResponseValue;
        return;
      }
    }
    p_form.mark.value = 0;
  },
  /**
   * Перевод строки Дд.Мм.ВвГг в формат даты
   */
  StrToDate: function(Dat) {
    var year = parseInt(Dat.split(".")[2]);
    var month = parseInt(Dat.split(".")[1])-1;
    var day = parseInt(Dat.split(".")[0]);
    return new Date(year, month, day);
  },

  onChangePollQuestionID: function(event) {
    var self = this;
    this.onChangeEvent(event, {
      disableEvents: true,
      onApply: function() {
        self.showhide();
      }
    });
  },

  //После выбора вопроса
  showhide: function() {
    if (!this.fieldValue('PollQuestionID'))
    {
      return;
    }
    var p_form = document.getElementById(this.el.id).getElementsByTagName("form")[0];
    //Получить код типа ответа на вопрос, скрыть ненужные типы ответов и показать нужные
    Transport.request({
      section: 'Interview',
      'class': 'dc_Interview_Response',
      method: 'GetQuestionInfo',
      parameters: {
        '_p_pollquestionid': p_form.PollQuestionID.getAttribute('lookup_value'),
        '_p_interviewid': p_form.InterviewID.getAttribute('lookup_value')
      },
      onSuccess: function(transport) {
        var result = transport.responseText.evalJSON().data;
        var code = result.Params.ResponseTypeCode;
        var selected_yes = '';
        var class_yes = '';
        var selected_no = '';
        var class_no = '';
        var sel_yes = 'no';
        var sel_no = 'no';

        $(p_form.ResponseID.up('.form_row')).style.display = 'none';
        $(p_form.stringvalue.up('.form_row')).style.display = 'none';
        $(p_form.intvalue.up('.form_row')).style.display = 'none';
        $(p_form.floatvalue.up('.form_row')).style.display = 'none';
        $(p_form.datevalue.up('.form_row')).style.display = 'none';
        $(p_form.datetimevalue.up('.form_row')).style.display = 'none';

        if ('String' == code) {
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
        if ('Single' == code) {
          $(p_form.ResponseID.up('.form_row')).style.display = 'table-row';
        }

        if ('Multi' == code) {
          var row = $($(p_form.datetimevalue).up('.form_row'));

          //Нарисовать дополнительные ответы, чтобы их сохранить программно
          var MultiFields = '';
          result.FieldValues.forEach(function(val) {
            //Код доменного поля "Да-Нет"
            selected_yes = '';
            class_yes = '';
            selected_no = '';
            class_no = '';
            sel_yes = 'no';
            sel_no = 'no';
            if (1 == val.ResponseValue) {
              selected_yes = 'selected=""';
              class_yes = 'rb-selected-f';
              sel_yes = 'yes';
            }
            if (0 == val.ResponseValue) {
              selected_no = 'selected=""';
              class_no = 'rb-selected-l';
              sel_no = 'yes';
            }
            MultiFields +=
                '<tr class="form_row">'+
                '<td class="form_table" align="left" width="1%">'+
                '<nobr><span class="card_elem_caption">'+val.Name+'<br></span></nobr>'+
                '</td>'+
                '<td class="form_table" colspan="1" width="75%">'+
                '<div class="radiobtn-cont">'+
                '<div class="radiobtn-values" have_null="no" onclick="selectRadioButton(event)">'+
                '<table class="rb-table"><tbody><tr>'+
                '<td>'+
                '<span class="rbelem-f '+class_yes+'" pos="f" value="1" selected="'+sel_yes+'">'+
                '<span class="rb-caption">Да</span></span>'+
                '</td>'+
                '<td>'+
                '<span class="rbelem-l '+class_no+'" pos="l" value="0" selected="'+sel_no+'">'+
                '<span class="rb-caption">Нет</span></span>'+
                '</td>'+
                '</tr></tbody></table>'+
                '</div>'+
                '<select mandatory="yes" class="edtText" is_radio="yes" '+
                'style="width:100%; display: none" id="_multi_'+val.Value+'" '+
                'responseid="'+val.Value+'" '+
                'interviewresponseid="'+val.Caption+'" '+
                'onfocus="this.className = \'edtText_selected\';" '+
                'onblur="this.className = \'edtText\';" elem_type="select">'+
                '<option value="1" '+selected_yes+'>Да</option>'+
                '<option value="0" '+selected_no+'>Нет</option>'+
                '</select>'+
                '</div>'+
                '</td>'+
                '</tr>';
          });
          //Оформим так, чтобы остальные поля оставались выровненными
          MultiFields =
              '<tr id="multifields"><td colspan=4><table id="_multival_"><tbody>' +
              MultiFields +
              '</tbody></table></td></tr>';
          row.insert({after: MultiFields});

          p_form.mark.setAttribute('disabled', 'disabled');
        }
        else {
          //Удалим мультивариантыне ответы, если они были нарисованы
          var mf = $(p_form).down('[id="multifields"]');
          if (mf) {
            mf.remove();
          }
        }

        //Если ответы не мульти, то сохраним диапазоны в параметре формы "_response_values"
        if (!p_form._response_values) {
          $(p_form).down('input').insert({ after: '<input type="hidden" id="_response_values" value="">' });
        }
        p_form._response_values.value = Object.toJSON(result.ResponseValues);
      }
    });
  },
  /**
   * После сохранения карточки, но ещё до её закрытия
   */
  onAfterSave: function(p_rec_id, p_mode) {
    var wnd_id = arguments[3];
    var form = $(wnd_id).getElementsByTagName("form")[0];

    var values = this.getResponses(wnd_id);
    if (values == null) {
      return;
    }

    // передача их на сервер
    Transport.request({
      section: 'Interview',
      'class': 'dc_Interview_Response',
      method: 'UpdateMultiResponse',
      parameters: {
        '_p_id': p_rec_id,
        '_p_pollquestionid': form.PollQuestionID.getAttribute('lookup_value'),
        '_p_interviewid': form.InterviewID.getAttribute('lookup_value'),
        '_p_values': Object.toJSON(values)
      },
      onSuccess: function(transport) {
        // анализ результата
        try {
          var result = transport.responseText.evalJSON().data;
          if (result.success != 1) {
            wnd_alert(result.message);
          }
        } catch (e) {
          wnd_alert('Не удалось сохранить информацию об ответах');
        }
      }
    });
  },

  getResponses: function(p_wnd_id) {
    var form = $($(p_wnd_id).getElementsByTagName("form")[0]);
    var multi_table = $(form.down('#_multival_'));
    var values = [];
    var select;

    //Если вопрос не предполагает мультивариантный ответ
    if (multi_table == null) {
      return null;
    }

    //Если мультивариантный
    for (var i = 0; i < multi_table.rows.length; i++) {
      select = $(multi_table.rows[i]).down('select');
      var val = {};
      val.responseid = select.getAttribute('responseid');
      val.interviewresponseid = select.getAttribute('interviewresponseid');
      if (IsEmptyGUIDValue(select.getAttribute('interviewresponseid'))) {
        val.interviewresponseid = null;
      }
      val.responsevalue = select.value;
      values.push(val);
    }

    return values;
  }

});

