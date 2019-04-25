/**
 * Скрипт таблицы changelog
 */

irisControllers.classes.g_Changelog = IrisGridController.extend({
    onOpen: function() {
        this.switchMonitoring(this.el.id, 'init', null);
    },

    switchMonitoring: function(gridId, mode, p_this) {
        var self = this;
        var elem = getGridFooterTable(gridId);
        if (p_this) {
            p_this.setAttribute('disabled', 'disabled');
            $(p_this).up('table').down('label').setOpacity(0.5);
        }

        Transport.request({
            section: 'Changelog',
            'class': 'g_Changelog',
            method: 'switchMonitoring',
            parameters: {
                recordId: $(gridId).getAttribute('detail_parent_record_id'),
                gridId: gridId,
                mode: mode,
                instanceName: this.instanceName()
            },
            onSuccess: function (transport) {
                var data = transport.responseText.evalJSON().data;

                jQuery(elem).find('.grid_footer_left').html(data.html);
                self.markNewChanges(gridId);
            }
        });
    },

    markNewChanges: function(gridId) {
        var grid = $(gridId);
        var monitorDate = null;

        var elem = getGridFooterTable(gridId);
        var monitorDateStr = $(elem).down('input').getAttribute('date_str');
        if (monitorDateStr != '') {
            monitorDate = this.parseDate(monitorDateStr);
        }

        //По всем строчкам таблицы
        for (var i=1; i < grid.rows.length; i++) {
            if (grid.rows[i].getAttribute('rec_id') == null) {
                break;
            }
            if (monitorDateStr == '') {
                $(grid.rows[i]).removeClassName('grid_newchangelog'); // если дата не указана, то просто снимем выделение со всех строк
            } else {
                var changeDate = this.parseDate(grid.rows[i].getAttribute('t0_changedate'));
                if ((changeDate >= monitorDate) && (grid.rows[i].getAttribute('t0_userid') != g_session_values.userid)) {
                    $(grid.rows[i]).addClassName('grid_newchangelog');
                }
            }
        }
    },

    parseDate: function(value) {
        return moment(value, "DD.MM.YYYY HH:mm").toDate();
    }
});
