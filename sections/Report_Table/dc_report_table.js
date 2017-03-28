/**
 * Скрипт карточки таблицы отчёта
 */
irisControllers.classes.dc_Report_Table = 
    irisControllers.classes.ReportCardController.extend({

  events: {
    'lookup:changed #ParentTableID': 'onChangeParentTableID',
    'lookup:changed #TableID': 'onChangeTableID'
  },

  onChangeParentTableID: function() {
    var form = $(this.el.id).down('form');
    this.filterTableColumn('Table', form.ParentTableID, form.ParentColumnID);
  },

  onChangeTableID: function(event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id);
  },

  onOpen: function() {
    var form = $(this.el.id).down('form');

    // Таблица поля фильтруется по ID отчёта
    this.bindFields('ReportID', 'ParentTableID');
    
    //Свяжем ColumnID с TableID
  	bind_lookup_element(form.TableID, form.ColumnID, 'TableID');

    // Колонка таблицы фильтруется по ID таблицы
    this.filterTableColumn('Table', form.ParentTableID, form.ParentColumnID);
  }

});
