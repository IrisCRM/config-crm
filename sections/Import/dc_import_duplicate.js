//********************************************************************
// Скрипт карточки вкладки "Дубликаты при импорте"
//********************************************************************

irisControllers.classes.dc_Import_Duplicate = IrisGridController.extend({
    onOpen: function () {
        var form = $(this.el.id).down('form');
        bind_lookup_element(form.TableID, form.ColumnID, 'TableID');
    }
});
