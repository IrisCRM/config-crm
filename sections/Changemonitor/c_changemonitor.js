/**
 * Скрипт карточки changemonitor
 */

irisControllers.classes.c_Changemonitor = IrisCardController.extend({
    onOpen: function() {
        var form = $(this.el.id).down('form');
        var recordId = this.fieldValue('recordid');
        var tableId = this.fieldValue('TableID');
        var windowId = get_window_id(form);
        var window = null;

        Windows.focus(windowId);
        window = Windows.getFocusedWindow();
        window.setHTMLContent('');

        Transport.request({
            section: 'Changemonitor',
            'class': 'c_ChangeMonitor',
            method: 'getCardInfo',
            parameters: {
                tableId: tableId
            },
            onSuccess: function (transport) {
                var data = transport.responseText.evalJSON().data;

                Windows.close(windowId); // закроем текущее окно
                if (data.section) {
                    openCard('grid', data.section, recordId);
                }
            }
        });
    }
});

