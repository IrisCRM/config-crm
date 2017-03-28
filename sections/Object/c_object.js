/**
 * Скрипт карточки объекта
 */

irisControllers.classes.c_Object = IrisCardController.extend({

  events: {
    'lookup:changed #CountryID': 'onChangeCountryID',
    'lookup:changed #RegionID': 'onChangeRegionID',
    'lookup:changed #CityID': 'onChangeCityID',
    'lookup:changed #AccountID, #ContactID': 'onChangeLookup'
  },

  onChangeCountryID: function () {
    common_filtercity(this.el.id, 'c');
  },

  onChangeRegionID: function (event) {
    common_filtercity(this.el.id, 'r');
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id, 
      null, false);
  },

  onChangeCityID: function (event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id, 
      null, false);
  },

  onChangeLookup: function (event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id, 
      null, true, undefined, true);
  },

  onOpen: function () {
    common_filtercity(this.el.id, '');
  }

});
