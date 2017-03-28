/**********************************************************************
Раздел "Фильтры конфигуратора". Карточка.
**********************************************************************/

var c_config_filter = {

  //Инициализация карточки
  on_init: function (p_wnd_id) {
    //Форма
    var form = $(p_wnd_id).down('form');

    var thisobj = this;

    //Обработчики
    form.down(lc('#filtertype')).observe('radiobutton:changed', function() {
      thisobj.on_filtertype_change(form);
    });
    this.on_filtertype_change(form);
  },

  //Изменили тип фильтра
  on_filtertype_change: function (p_form) {
    var filtertype = c_Common_GetElementValue(p_form.down(lc('#filtertype')));
    if (filtertype == 1) {
      IrisCard.showTab(p_form, 1);
      IrisCard.hideTab(p_form, 2);
    }
    else {
      IrisCard.hideTab(p_form, 1);
      IrisCard.showTab(p_form, 2);
    }
  }
}