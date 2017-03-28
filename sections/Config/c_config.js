/**
 * Скрипт карточки Конфигуратора
 */
irisControllers.classes.c_Config = IrisCardController.extend({

  events: {
    'field:edited #type': 'on_type_change',
    'field:edited #sectiontype': 'on_sectiontype_change',
  },

  onOpen: function() {
    this.on_type_change();
  },

  //Изменили тип конфигурации
  on_type_change: function (event) {
    var type = this.fieldValue('type');
    if (type == 1) {
      this.showField('sectiontype');
      this.showField('Name');
      this.showField('showaccessdetail');
      this.showTab(1);
      this.showTab(2);
      this.on_sectiontype_change();
    }
    else {
      this.hideField('sectiontype');
      this.hideField('Name');
      this.hideField('showaccessdetail');
      this.showTab(1);
      this.hideTab(2);
    }
  },

  //Изменили тип раздела
  on_sectiontype_change: function (event) {
    var sectiontype = this.fieldValue('sectiontype');

    if (sectiontype == 1) {
      this.showTab(1);
      this.hideTab(2);
    }
    else
    if (sectiontype == 2) {
      this.hideTab(1);
      this.showTab(2);
    }
    else {
      this.hideTab(1);
      this.hideTab(2);
    }
  }
});