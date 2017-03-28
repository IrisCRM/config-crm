/**********************************************************************
Раздел "Поле карточки конфигуратора". Карточка.
**********************************************************************/

var c_config_card_field = {

  //Инициализация карточки
  on_init: function (p_wnd_id) {
    //Форма
    var form = $(p_wnd_id).down('form');

    var thisobj = this;

    //Обработчики
    form.down(lc('#controltype')).observe('change', function() {
      thisobj.on_controltype_change(form);
    });
    form.down(lc('#fieldtype')).observe('change', function() {
      thisobj.on_controltype_change(form);
      thisobj.on_fieldtype_change(form);
    });
    form.down(lc('#listsql')).observe('keyup', function() {
      thisobj.on_listsql_change(form);
    });
    form.down(lc('#DictTableID')).observe('lookup:changed', function() {
      thisobj.on_dicttableid_change(form);
    });
    form.down(lc('#method')).observe('keyup', function() {
      thisobj.on_method_change(form);
    });
    form.down(lc('#onclick')).observe('keyup', function() {
      thisobj.on_method_change(form);
    });

    //Вызовем обработчики при инициализации карточки
    this.on_controltype_change(form);
    this.on_fieldtype_change(form);
    thisobj.on_method_change(form);
  },

  //Изменили тип элемента
  on_controltype_change: function (p_form) {
    var controltype = c_Common_GetElementValue(p_form.down(lc('#controltype')));
    var fieldtype = c_Common_GetElementValue(p_form.down(lc('#fieldtype')));
    var datatype = c_Common_GetElementValue(p_form.down(lc('#datatype')));

    //Скрытие и отображение полей
    IrisCard.hideField(p_form, 'datatype');
    IrisCard.hideField(p_form, 'fieldtype');
    IrisCard.hideField(p_form, 'name');
    IrisCard.hideField(p_form, 'ismandatory');
    IrisCard.hideField(p_form, 'ColumnID');
    IrisCard.hideField(p_form, 'columnsql');
    IrisCard.hideField(p_form, 'title');
    IrisCard.hideField(p_form, 'code');
    IrisCard.hideField(p_form, 'height');
    IrisCard.hideField(p_form, 'width');
    IrisCard.hideField(p_form, 'align');
    IrisCard.hideField(p_form, 'method');
    IrisCard.hideField(p_form, 'onclick');
    IrisCard.hideTab(p_form, 1);
    IrisCard.hideTab(p_form, 2);
    IrisCard.hideTab(p_form, 3);
    IrisCard.hideTab(p_form, 4);

    if (controltype != IrisDomain.control_type.spacer
    && controltype != IrisDomain.control_type.splitter) {
      IrisCard.showField(p_form, 'datatype');
      IrisCard.showField(p_form, 'fieldtype');
      IrisCard.showField(p_form, 'ismandatory');
      IrisCard.showField(p_form, 'ColumnID');
      IrisCard.showField(p_form, 'columnsql');
      IrisCard.showField(p_form, 'title');
      IrisCard.showField(p_form, 'name');
    }

    if (controltype == IrisDomain.control_type.splitter) {
      IrisCard.showField(p_form, 'name');
      IrisCard.showTab(p_form, 1);
    }
    else
    if (controltype == IrisDomain.control_type.phone) {
      IrisCard.showTab(p_form, 4);
    }
    else
    if (controltype == IrisDomain.control_type.textarea) {
      IrisCard.showTab(p_form, 2);
    }
    else
    if (controltype == IrisDomain.control_type.lookup
    || controltype == IrisDomain.control_type.select
    || controltype == IrisDomain.control_type.checkbox
    || controltype == IrisDomain.control_type.radiobutton) {
      IrisCard.showTab(p_form, 3);

      IrisCard.hideField(p_form, 'DictTableID');
      IrisCard.hideField(p_form, 'DictColumnID');
      IrisCard.hideField(p_form, 'dictfiltersql');

      IrisCard.hideField(p_form, 'ListSortColumnID');
      IrisCard.hideField(p_form, 'ListExtFields');
      IrisCard.hideField(p_form, 'listsql');
      IrisCard.hideField(p_form, 'domain');
      IrisCard.hideField(p_form, 'checkedindex');

      if (controltype == IrisDomain.control_type.lookup) {
        IrisCard.showField(p_form, 'DictTableID');
        IrisCard.showField(p_form, 'DictColumnID');
        IrisCard.showField(p_form, 'dictfiltersql');
      }
      else
      if (controltype == IrisDomain.control_type.checkbox) {
        IrisCard.showField(p_form, 'domain');
        IrisCard.showField(p_form, 'checkedindex');
      }
      else
      if (controltype == IrisDomain.control_type.radiobutton) {
        IrisCard.showField(p_form, 'domain');
      }
      else
      if (controltype == IrisDomain.control_type.select) {
        this.on_fieldtype_change(p_form);
      }
    }
    else
    if (controltype == IrisDomain.control_type.button) {
      IrisCard.showField(p_form, 'code');
      IrisCard.showField(p_form, 'width');
      IrisCard.showField(p_form, 'align');
      IrisCard.showField(p_form, 'method');
      IrisCard.showField(p_form, 'onclick');
      IrisCard.hideField(p_form, 'datatype');
      IrisCard.hideField(p_form, 'fieldtype');
      IrisCard.hideField(p_form, 'ismandatory');
      IrisCard.hideField(p_form, 'ColumnID');
      IrisCard.hideField(p_form, 'columnsql');
      IrisCard.hideField(p_form, 'title');
      IrisCard.hideField(p_form, 'height');
    }
    else
    if (controltype == IrisDomain.control_type.detail) {
      IrisCard.showField(p_form, 'code');
      IrisCard.showField(p_form, 'height');
      IrisCard.hideField(p_form, 'datatype');
      IrisCard.hideField(p_form, 'fieldtype');
      IrisCard.hideField(p_form, 'name');
      IrisCard.hideField(p_form, 'ismandatory');
      IrisCard.hideField(p_form, 'ColumnID');
      IrisCard.hideField(p_form, 'columnsql');
      IrisCard.hideField(p_form, 'title');
    }
    else
    if (controltype == IrisDomain.control_type.matrix) {
      IrisCard.showField(p_form, 'code');
      IrisCard.hideField(p_form, 'datatype');
      IrisCard.hideField(p_form, 'fieldtype');
      IrisCard.hideField(p_form, 'name');
      IrisCard.hideField(p_form, 'ismandatory');
      IrisCard.hideField(p_form, 'ColumnID');
      IrisCard.hideField(p_form, 'columnsql');
      IrisCard.hideField(p_form, 'title');
    }

    //Доступность значений fieldtype и datatype
    IrisCard.enableOptions(p_form, 'datatype', [
      IrisDomain.data_type.string,
      IrisDomain.data_type.int,
      IrisDomain.data_type.decimal,
      IrisDomain.data_type.date,
      IrisDomain.data_type.datetime,
      IrisDomain.data_type.file,
      IrisDomain.data_type.id
    ]);
    IrisCard.enableOptions(p_form, 'fieldtype', [
      IrisDomain.field_type.common,
      IrisDomain.field_type.date,
      IrisDomain.field_type.file,
      IrisDomain.field_type.fk_column,
      IrisDomain.field_type.domain
    ]);

    if (controltype == IrisDomain.control_type.email
    || controltype == IrisDomain.control_type.url
    || controltype == IrisDomain.control_type.password
    || controltype == IrisDomain.control_type.phone
    || controltype == IrisDomain.control_type.textarea) {
      IrisCard.disableOptions(p_form, 'datatype', [
        IrisDomain.data_type.int,
        IrisDomain.data_type.decimal,
        IrisDomain.data_type.date,
        IrisDomain.data_type.datetime,
        IrisDomain.data_type.file,
        IrisDomain.data_type.id
      ]);
      IrisCard.disableOptions(p_form, 'fieldtype', [
        IrisDomain.field_type.date,
        IrisDomain.field_type.file,
        IrisDomain.field_type.fk_column,
        IrisDomain.field_type.domain
      ]);
    }
    else
    if (controltype == IrisDomain.control_type.common) {
      IrisCard.disableOptions(p_form, 'datatype', [
        IrisDomain.data_type.id
      ]);
      IrisCard.disableOptions(p_form, 'fieldtype', [
        IrisDomain.field_type.fk_column,
        IrisDomain.field_type.domain
      ]);

      if (fieldtype == IrisDomain.field_type.common) {
        IrisCard.disableOptions(p_form, 'datatype', [
          IrisDomain.data_type.date,
          IrisDomain.data_type.datetime,
          IrisDomain.data_type.file,
          IrisDomain.data_type.id
        ]);
      }
      else
      if (fieldtype == IrisDomain.field_type.date) {
        IrisCard.disableOptions(p_form, 'datatype', [
          IrisDomain.data_type.string,
          IrisDomain.data_type.int,
          IrisDomain.data_type.decimal,
          IrisDomain.data_type.file,
          IrisDomain.data_type.id
        ]);
      }
      else
      if (fieldtype == IrisDomain.field_type.file) {
        IrisCard.disableOptions(p_form, 'datatype', [
          IrisDomain.data_type.string,
          IrisDomain.data_type.int,
          IrisDomain.data_type.decimal,
          IrisDomain.data_type.date,
          IrisDomain.data_type.datetime,
          IrisDomain.data_type.id
        ]);
      }
    }
    else
    if (controltype == IrisDomain.control_type.lookup) {
      IrisCard.disableOptions(p_form, 'datatype', [
        IrisDomain.data_type.string,
        IrisDomain.data_type.int,
        IrisDomain.data_type.decimal,
        IrisDomain.data_type.date,
        IrisDomain.data_type.datetime,
        IrisDomain.data_type.file
      ]);
      IrisCard.disableOptions(p_form, 'fieldtype', [
        IrisDomain.field_type.common,
        IrisDomain.field_type.date,
        IrisDomain.field_type.file,
        IrisDomain.field_type.domain
      ]);
    }
    else
    if (controltype == IrisDomain.control_type.select) {
      IrisCard.disableOptions(p_form, 'datatype', [
        IrisDomain.data_type.file
      ]);
      IrisCard.disableOptions(p_form, 'fieldtype', [
        IrisDomain.field_type.common,
        IrisDomain.field_type.date,
        IrisDomain.field_type.file
      ]);

      if (fieldtype == IrisDomain.field_type.fk_column) {
        IrisCard.disableOptions(p_form, 'datatype', [
          IrisDomain.data_type.string,
          IrisDomain.data_type.int,
          IrisDomain.data_type.decimal,
          IrisDomain.data_type.date,
          IrisDomain.data_type.datetime,
          IrisDomain.data_type.file
        ]);
      }
      else
      if (fieldtype == IrisDomain.field_type.domain) {
        IrisCard.disableOptions(p_form, 'datatype', [
          IrisDomain.data_type.file
        ]);
      }
    }
    else
    if (controltype == IrisDomain.control_type.checkbox
    || controltype == IrisDomain.control_type.radiobutton) {
      IrisCard.disableOptions(p_form, 'datatype', [
        IrisDomain.data_type.decimal,
        IrisDomain.data_type.date,
        IrisDomain.data_type.datetime,
        IrisDomain.data_type.file,
        IrisDomain.data_type.id
      ]);
      IrisCard.disableOptions(p_form, 'fieldtype', [
        IrisDomain.field_type.common,
        IrisDomain.field_type.date,
        IrisDomain.field_type.file,
        IrisDomain.field_type.fk_column
      ]);
    }

    if (datatype > 0) {
      if (p_form.down(lc('#datatype')).down('option', datatype).hasAttribute('disabled')) {
        p_form.down(lc('#datatype')).setValue('null');
      }
    }
    if (fieldtype > 0) {
      if (p_form.down(lc('#fieldtype')).down('option', fieldtype).hasAttribute('disabled')) {
        p_form.down(lc('#fieldtype')).setValue('null');
      }
    }
  },

  //Изменили тип поля
  on_fieldtype_change: function (p_form) {
    var controltype = c_Common_GetElementValue(p_form.down(lc('#controltype')));
    var fieldtype = c_Common_GetElementValue(p_form.down(lc('#fieldtype')));

    if (controltype == IrisDomain.control_type.select) {
      IrisCard.hideField(p_form, 'DictTableID');
      IrisCard.hideField(p_form, 'DictColumnID');
      IrisCard.hideField(p_form, 'ListSortColumnID');
      IrisCard.hideField(p_form, 'ListExtFields');
      IrisCard.hideField(p_form, 'listsql');
      IrisCard.hideField(p_form, 'domain');

      if (fieldtype == IrisDomain.field_type.fk_column) {
        IrisCard.showField(p_form, 'DictTableID');
        IrisCard.showField(p_form, 'DictColumnID');
        IrisCard.showField(p_form, 'ListSortColumnID');
        IrisCard.showField(p_form, 'ListExtFields');
        IrisCard.showField(p_form, 'listsql');

        this.on_listsql_change(p_form);
      }
      else
      if (fieldtype == IrisDomain.field_type.domain) {
        IrisCard.showField(p_form, 'domain');
      }
    }
  },

  //Изменили поле "Список, SQL"
  on_listsql_change: function (p_form) {
    var controltype = c_Common_GetElementValue(p_form.down(lc('#controltype')));
    var fieldtype = c_Common_GetElementValue(p_form.down(lc('#fieldtype')));
    var listsql = c_Common_GetElementValue(p_form.down(lc('#listsql')));
    var dicttableid = c_Common_GetElementValue(p_form.down(lc('#DictTableID')));

    if (controltype == IrisDomain.control_type.select) {
      if (fieldtype == IrisDomain.field_type.fk_column) {
        if (!IsEmptyValue(listsql)) {
          IrisCard.hideField(p_form, 'DictTableID');
          IrisCard.hideField(p_form, 'DictColumnID');
          IrisCard.hideField(p_form, 'ListSortColumnID');
          IrisCard.hideField(p_form, 'ListExtFields');
          IrisCard.showField(p_form, 'listsql');
        }
        else
        if (!IsEmptyValue(dicttableid)) {
          IrisCard.showField(p_form, 'DictTableID');
          IrisCard.showField(p_form, 'DictColumnID');
          IrisCard.showField(p_form, 'ListSortColumnID');
          IrisCard.showField(p_form, 'ListExtFields');
          IrisCard.hideField(p_form, 'listsql');
        }
        else {
          IrisCard.showField(p_form, 'DictTableID');
          IrisCard.showField(p_form, 'DictColumnID');
          IrisCard.showField(p_form, 'ListSortColumnID');
          IrisCard.showField(p_form, 'ListExtFields');
          IrisCard.showField(p_form, 'listsql');
        }
      }
    }
  },

  //Изменили поле "Ссылка на таблицу"
  on_dicttableid_change: function (p_form) {
    this.on_listsql_change(p_form);
  },

  on_method_change: function (p_form) {
    var controltype = c_Common_GetElementValue(p_form.down(lc('#controltype')));
    var onclick = c_Common_GetElementValue(p_form.down(lc('#onclick')));
    var method = c_Common_GetElementValue(p_form.down(lc('#method')));

    if (controltype == IrisDomain.control_type.button) {
      if (!IsEmptyValue(onclick)) {
        IrisCard.hideField(p_form, 'method');
        IrisCard.showField(p_form, 'onclick');
      }
      else
      if (!IsEmptyValue(method)) {
        IrisCard.showField(p_form, 'method');
        IrisCard.hideField(p_form, 'onclick');
      }
      else {
        IrisCard.showField(p_form, 'method');
        IrisCard.showField(p_form, 'onclick');
      }
    }
  }

}