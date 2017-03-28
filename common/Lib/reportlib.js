//*******************************************************************
// Функции для запуска отчетов
// используются в разделе "Отчеты" и "Рассылки" (вкладка получатели)                                        
//*******************************************************************

//var g_reportlib_ScriptFileName = '/report.php';
var g_reportlib_ScriptFileName = '/index.php';
//var g_reportlib_ScriptFileName = '/config/sections/Report/p_report.php';

// открывает окно параметров отчета или запускает отчет, если в нем нет параметров
// p_params {  reportid: <id отчета>, 
//         reportcode: <код отчета. указваеться id или код. если указано оба, то берется код отчета>, 
//        [onOk: function(form) {новый обработчик для кнопки OK}}
//        okLabel: <новое название для кнопки "Запуск">,
//        cancelLabel: <новое название для кнопки "Отмена">]
function showParamsWindow(p_params) {
  var params = {
    reportid: '', 
    reportcode: '', 
    onOk: 0, 
    okLabel: T.t("Запуск"), 
    cancelLabel: T.t("Отмена")
  };
  params = Object.extend(params, p_params || {});
  
  Transport.request({
    section: 'Report', 
    'class': 'g_Report', 
    method: 'getFilters', 
    parameters: {
      report_id: params.reportid,
      report_code: params.reportcode
    },
    onSuccess: function(transport) {
      var result = transport.responseText.evalJSON().data;
      if (result.Show) {
        // id будущего окна. должно быть случайное, без символа _ !!!
        var win_id = "wnd" + (Math.random() + "").slice(3); 
        win = new Window({
          id: win_id, 
          className: "iris_win",
          title: T.t("Запуск отчета"), 
          width: 650, 
          height: 100
        }); 
        win.setConstraint(true, {
          left: 5, 
          right: 5, 
          top: 5, 
          bottom: 5
        });
        win.getContent().innerHTML = result.Card;

        jQuery("#" + win_id + " select[multiple] option[value='']").remove();
        jQuery('#' + win_id + ' select[multiple]').select2();

        win.setDestroyOnClose(); 
        win.toFront();
        // для исправления глюка IE с просвечиванием списков
        win.setZIndex(Windows.maxZIndex + 1);
        win.showCenter(0);
        UpdateCardHeight(win_id);
        
        var card_window = Windows.getFocusedWindow();
        card_window.options.minHeight = 100;
        card_window.setSize(card_window.getSize().width, 
            ((card_window.getSize().height > 500) ? 500: card_window.getSize().height));
        
        var form = win.getContent().getElementsByTagName("form")[0];
        var ok_btn = win.getContent().down('input#btn_ok');
        var cancel_btn = win.getContent().down('input#btn_cancel');

        form.setAttribute('reportid', params.reportid);
        form.setAttribute('reportcode', params.reportcode);
        
        if (params.onOk == 0) {
          ok_btn.observe('click', function() {RunReport(form, false, true)});
        } else {
          ok_btn.observe('click', function() {params.onOk(form)});
        }
        
        ok_btn.value = params.okLabel;//setAttribute('value', this.params.okLabel);
        cancel_btn.value = params.cancelLabel;
        return form;
      } 
      else {
        if (!IsEmptyValue(params.reportid)) {
          window.open(g_path + g_reportlib_ScriptFileName + "?r=" + params.reportid);  
        }
        else
        if (!IsEmptyValue(params.reportcode)) {
          window.open(g_path + g_reportlib_ScriptFileName + "?r=" + params.reportcode);  
        }
      }
    }
  });
}

// по объекту формы p_form возвращает массив фильтров, необходимых для запуска отчета
function getReportFilters(p_form) {
  //Считаем значения фильтров и передадим их в отчет
  //Фильтры нызваются p_autoparam_<number>, а соответствующие условия c_autoparam_<number>
  var i = 0;
  var param_number = 0;
  var param = null;
  var condition = null;
  var caption = '';
  var filters = new Array();

  var parameters = new Array();
  parameters = p_form.getAttribute('parameters').evalJSON();
  var value;
  for (var i=0; i<parameters.length; i++) {
    condition = p_form['c_'+parameters[i]];

    //Если заполнено и условие, то добавим фильтр
    if (!IsEmptyValue(condition)) {
      try {
        value = c_Common_GetElementValue(p_form[parameters[i]]);
        if (jQuery.isArray(value)) {
          value = !value.length ? '' : JSON.stringify(value); // для значений multiselect
          caption = c_Common_GetSelectCaptions(p_form[parameters[i]]);
        }
        else if (p_form[parameters[i]].options)
          caption = p_form[parameters[i]].options[p_form[parameters[i]].selectedIndex].innerHTML;
        else
          caption = p_form[parameters[i]].value;
      }
      catch (e) {
        value = null;
      }
      filters[param_number] = {
        'FilterID': condition.getAttribute('filterid'),
        'ParameterID': condition.getAttribute('parameterid'),
        'ParameterName': parameters[i],
        'Condition': condition.value,
        'Value': value,
        'Caption': caption,
        'Name': $($(condition).up('tr').cells[0]).innerHTML
      };
      param_number++;
    }
  }
  return filters;
}

// стандартная функция запуска отчета. если указан обработчик onOk, то рабоатет он
function RunReport(p_form, p_format, p_blank) {
  if (IsEmptyValue(p_format)) {
    p_format = null;
  }
  if (IsEmptyValue(p_blank)) {
    p_blank = false;
  }
  var filters = getReportFilters(p_form);
  //Отправляем параметры ajax запросом на сервер для создания ссылки, 
  //получаем в ответ код ссылки и вызываем отчёт уже по коду ссылки
  //Формат массива: [название действия, необходимые параметры]
  //Формат массива: [название действия (report), id записи, формат, массив фильтров]
  var params = ['report', p_form.getAttribute('reportid'), p_format, filters];
  new Ajax.Request(g_path+g_reportlib_ScriptFileName, {
    parameters: {
      '_func': 'getlink', 
      '_parameters': Object.toJSON(params)
    }, 
    onSuccess: function(transport) { 
      var result = transport.responseText.evalJSON();
      // TODO: выводить ошибку в стандартном окне
      if (result._error) {
        alert(result._error);
        return;
      }
      var linkcode = result.md5;
      
      var target = '';
      if (p_blank) {
        target = ' target="_blank"';
      }

      //Отправляем на сервер запрос по ссылке для построения отчёта
      
      html  = '<div style="display: none">';
      html += '<form ' + target + 'method="GET">';
      html += '<input type="hidden" name="l" value="' + linkcode + '"/>';
      html += '</form></div>';
      $(p_form).insert({'after': html});
      var newform = $(p_form).next().down();
      newform.submit();
      newform.up().remove();
      
      /*
      html = '<div>' +
        '<a' + target + ' href="?l=' + linkcode + '">' +
        'test</a></div>';
      $(p_form).insert({'after': html});
      var newform = $(p_form).next().down();
      newform.click();
      //newform.up().remove();
      */
    }
  });
}
