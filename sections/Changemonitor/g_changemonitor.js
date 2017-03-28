/**
 * Скрипт таблицы changemonitor
 */

irisControllers.classes.g_Changemonitor = IrisGridController.extend({
    onOpen: function() {
        var self = this;
        var gridId = this.el.id;

        $(this.el.id).observe('dblclick', function(event) {
            var element = Event.element(event);
            if ('TR' == element.up('tr').tagName) {
                self.gridClick(element.up('tr'));
            }
        });
    },

    gridClick: function(row) {
        var sectionName = row.getAttribute('code');
        // var dictonary_name = row.getAttribute('dictionary');
        // var detail_name = row.getAttribute('detail');

        if (sectionName != '') {
            openCard('grid', sectionName, row.getAttribute('recordid'));
        }
    }
});

