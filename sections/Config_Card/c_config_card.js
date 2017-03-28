/**********************************************************************
Раздел "Карточка конфигуратора". Карточка.
**********************************************************************/

var c_config_card = {

  //Инициализация карточки
  on_init: function (p_wnd_id) {
    //Форма
    var form = $(p_wnd_id).down('form');

    var thisobj = this;

    //Обработчики
    form.down(lc('#jsfile')).observe('keyup', function() {
      thisobj.on_jsfile_change(form);
    });
    form.down(lc('#phpfile')).observe('keyup', function() {
      thisobj.on_phpfile_change(form);
    });

    this.on_jsfile_change(form);
    this.on_phpfile_change(form);
  },

  //Изменили название js файла
  on_jsfile_change: function (p_form) {
    var jsfile = c_Common_GetElementValue(p_form.down(lc('#jsfile')));
    if (!IsEmptyValue(jsfile)) {
      IrisCard.showField(p_form, 'jspathtype');
      IrisCard.showField(p_form, 'jsoninit');
      IrisCard.showField(p_form, 'jsonaftersave');
    }
    else {
      IrisCard.hideField(p_form, 'jspathtype');
      IrisCard.hideField(p_form, 'jsoninit');
      IrisCard.hideField(p_form, 'jsonaftersave');
    }
  },

  //Изменили название php файла
  on_phpfile_change: function (p_form) {
    var phpfile = c_Common_GetElementValue(p_form.down(lc('#phpfile')));
    if (!IsEmptyValue(phpfile)) {
      IrisCard.showField(p_form, 'phponinit');
      IrisCard.showField(p_form, 'phponbeforesave');
      IrisCard.showField(p_form, 'phponaftersave');
    }
    else {
      IrisCard.hideField(p_form, 'phponinit');
      IrisCard.hideField(p_form, 'phponbeforesave');
      IrisCard.hideField(p_form, 'phponaftersave');
    }
  }
}
