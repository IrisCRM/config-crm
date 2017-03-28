/**
 * Методы для работы с адресами
 */
irisControllers.classes.Address = {

  filterAddress: function(field) {
  	var value = this.fieldValue(field);
  	if (value) {
      if (field == 'CountryID') {
        this.fieldValue('RegionID', null);
        this.fieldValue('CityID', null);
      }
      if (field == 'RegionID') {
        this.fieldValue('CityID', null);
      }
    }
    var CountryID = this.fieldValue('CountryID');
    var RegionID = this.fieldValue('RegionID');
    var where = '';

    if (CountryID) {
      where += "t0.CountryID = '" + CountryID + "'";
    }
    if (where) {
      this.fieldProperty('RegionID', 'filter_where', where)
    }
    else {
      this.fieldRemoveProperty('RegionID', 'filter_where')
    }

    if (RegionID) {
      where += where ? " and " : "";
      where += "t0.RegionID = '" + RegionID + "'";
    }
    if (where) {
      this.fieldProperty('CityID', 'filter_where', where)
    }
    else {
      this.fieldRemoveProperty('CityID', 'filter_where')
    }
  }

};