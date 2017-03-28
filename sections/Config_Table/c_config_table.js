/**********************************************************************
Раздел "Таблицы конфигуратора". Карточка.
**********************************************************************/

var c_config_table = {

  //Инициализация карточки
  on_init: function (p_wnd_id) {
    //Форма
    var form = $(p_wnd_id).down('form');

    var thisobj = this;

    //Обработчики
    form.down(lc('#type')).observe('radiobutton:changed', function() {
      thisobj.on_type_change(form);
    });
    form.down(lc('#jsfile')).observe('keyup', function() {
      thisobj.on_jsfile_change(form);
    });
    form.down(lc('#phpfile')).observe('keyup', function() {
      thisobj.on_phpfile_change(form);
    });
    form.down(lc('#phpfilereplace')).observe('keyup', function() {
      thisobj.on_phpfilereplace_change(form);
    });

    this.on_type_change(form);
    this.on_jsfile_change(form);
    this.on_phpfile_change(form);
    this.on_phpfilereplace_change(form);
  },

  //Изменили тип таблицы
  on_type_change: function (p_form) {
    var type = c_Common_GetElementValue(p_form.down(lc('#type')));
    if (type == 1) {
      IrisCard.hideTab(p_form, 3);
    }
    else {
      IrisCard.showTab(p_form, 3);
    }
  },

  //Изменили название js файла
  on_jsfile_change: function (p_form) {
    var jsfile = c_Common_GetElementValue(p_form.down(lc('#jsfile')));
    if (!IsEmptyValue(jsfile)) {
      IrisCard.showField(p_form, 'jspathtype');
      IrisCard.showField(p_form, 'jsoninit');
      IrisCard.showField(p_form, 'jsonaftermodify');
      IrisCard.showField(p_form, 'jsonafterdelete');
      IrisCard.showField(p_form, 'jsondblclick');
    }
    else {
      IrisCard.hideField(p_form, 'jspathtype');
      IrisCard.hideField(p_form, 'jsoninit');
      IrisCard.hideField(p_form, 'jsonaftermodify');
      IrisCard.hideField(p_form, 'jsonafterdelete');
      IrisCard.hideField(p_form, 'jsondblclick');
    }
  },

  //Изменили название php файла
  on_phpfile_change: function (p_form) {
    var phpfile = c_Common_GetElementValue(p_form.down(lc('#phpfile')));
    if (!IsEmptyValue(phpfile)) {
      IrisCard.showField(p_form, 'phponinit');
    }
    else {
      IrisCard.hideField(p_form, 'phponinit');
    }
  },

  //Изменили название php replace файла
  on_phpfilereplace_change: function (p_form) {
    var phpfile = c_Common_GetElementValue(p_form.down(lc('#phpfilereplace')));
    if (!IsEmptyValue(phpfile)) {
      IrisCard.showField(p_form, 'phponreplace');
    }
    else {
      IrisCard.hideField(p_form, 'phponreplace');
    }
  }
}