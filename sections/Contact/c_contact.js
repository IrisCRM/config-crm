/**
 * Скрипт карточки контакта
 */

irisControllers.classes.c_Contact = IrisCardController.extend({

  extensions: ['Address'],

  events: {
    'keyup #Name': 'onChangeName',
    'lookup:changed #CountryID': 'onChangeCountryID',
    'lookup:changed #RegionID': 'onChangeRegionID',
    'lookup:changed #CityID': 'onChangeCityID',
    'lookup:changed #AccountID, #ObjectID': 'onChangeLookup',
    'click #_Skype': 'onClickSkype'
  },

  /**
   * Обращение = Имя отчество
   */
  onChangeName: function () {
    var name = this.fieldValue('Name').replace(/( )+/g, ' ').trim().split(' ');
    if (name.length <= 1) {
      this.fieldValue('SpeakName', name[0]);
      return;
    }
    this.fieldValue('SpeakName', name.splice(1, Number.MAX_VALUE).join(' '));
  },

  onChangeCountryID: function () {
    this.autoEditEventsEnabled = false;
    this.filterAddress('CountryID');
    this.autoEditEventsEnabled = true;
  },

  onChangeRegionID: function (event) {
    var self = this;
    this.onChangeEvent(event, {
      disableEvents: true,
      rewriteValues: true,
      letClearValues: true,
      onApply: function() {
        self.filterAddress('RegionID');
      }
    });
  },

  onChangeCityID: function (event) {
    var self = this;
    this.onChangeEvent(event, {
      disableEvents: true,
      rewriteValues: false,
      letClearValues: false,
      onApply: function() {
        self.filterAddress();
      }
    });
  },

  onChangeLookup: function (event) {
    this.onChangeEvent(event, {
      disableEvents: true
    });
  },

  onClickSkype: function() {
    skype_to(this.fieldValue('Skype'));
  },

  onOpen: function() {
    // Задизаблим
    this.getField('balance').attr('readonly', true);

    // Нарисуем иконку skype
    this.addButtonForField({
      fieldId: 'Skype',
      buttonId: '_Skype',
      iconClass: 'earphone'
    });

    // Если редактируется запись
    if (this.parameter('mode') != 'insert') {
      // Кнопка печать...
      printform_createCardHeaderButton(this.el.id, 'top', T.t('Печать')+'&hellip;');    
    }

    this.filterAddress();
  }
});
