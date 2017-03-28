/**********************************************************************
Раздел "Колонка таблицы конфигуратора". Карточка.
**********************************************************************/

var c_config_table_column = {

  //Инициализация карточки
  on_init: function (p_wnd_id) {
    //Форма
    var form = $(p_wnd_id).down('form');

    var thisobj = this;

    //Обработчики
    form.down(lc('#columntype')).observe('change', function() {
      thisobj.on_columntype_change(form);
    });

    this.on_columntype_change(form);
  },

  //Изменили тип элемента
  on_columntype_change: function (p_form) {
    var columntype = c_Common_GetElementValue(p_form.down(lc('#columntype')));

    //Скрытие и отображение полей
    IrisCard.hideTab(p_form, 1);
    IrisCard.hideTab(p_form, 2);
    IrisCard.hideTab(p_form, 3);

    if (columntype == IrisDomain.column_type.domain) {
      IrisCard.showTab(p_form, 1);
    }
    else
    if (columntype == IrisDomain.column_type.fk_column) {
      IrisCard.showTab(p_form, 2);
    }
    else
    if (columntype == IrisDomain.column_type.fk_column_extended) {
      IrisCard.showTab(p_form, 3);
    }
  }
}