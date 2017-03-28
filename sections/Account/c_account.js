/**
 * Скрипт карточки компании
 */
irisControllers.classes.c_Account = IrisCardController.extend({

  extensions: ['Address'],

  events: {
    'field:edited #CountryID': 'onChangeCountryID',
    'field:edited #RegionID': 'onChangeRegionID',
    'field:edited #CityID': 'onChangeCityID',
    'click #_Email': 'onClickEmailLink'
  },

  onChangeCountryID: function(event) {
    this.autoEditEventsEnabled = false;
    this.filterAddress('CountryID');
    this.autoEditEventsEnabled = true;
  },

  onChangeRegionID: function(event) {
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

  onChangeCityID: function(event) {
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

  onClickEmailLink: function() {
    // Email to primary contact
    var contactid = this.fieldValue('PrimaryContactID');
    if (contactid != null) {
      var table = 'iris_Contact';
      var recordname = this.fieldDisplayValue('PrimaryContactID');
      var email = '';
      var params = table + '#;' + contactid + '#;' + 
          recordname + '#;' + email + '#;';

      openCard('grid', 'Email', '', params);
    }
  },

  /**
   * Инициализация карточки
   */
  onOpen: function() {
    // Фильтрация для поля основной контакт
    this.getField('PrimaryContactID').attr('filter_where',
        "t0.accountid='" + this.fieldValue('_id') + "'");

    // Нарисуем конвертик полю основной контакт
    this.addButtonForField({
      fieldId: 'PrimaryContactID',
      buttonId: '_Email',
      iconClass: 'envelope'
    });
    
    this.filterAddress();
  }

});