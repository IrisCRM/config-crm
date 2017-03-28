/**
 * Скрипт карточки работы
 */

irisControllers.classes.c_Work = IrisCardController.extend({

    events: {
        'lookup:changed #ParentWorkID': 'onChangeParentWorkID'
    },

    onOpen: function () {
        this.fieldProperty('IsAutoDateCorrection', 'readonly', true);
        this.fieldProperty('IsCalculateProgress', 'readonly', true);
        this.fieldProperty('IsRemind', 'readonly', true);
        this.fieldProperty('RemindDate', 'readonly', true);

        if (this.parameter('mode') == 'insert') {
            this.onChangeParentWorkID();
        }
        // TODO: заполнять заказ из родителькой работы у дочерней работы
    },

    onChangeParentWorkID: function(event) {
        var self = this;
        var form = $(this.el.id).down('form');

        this.request({
            class: 's_Work',
            method: 'getParentInfo',
            parameters: {
                parentId: this.fieldValue('ParentWorkID'),
                projectId: this.fieldValue('ProjectID')
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText.evalJSON();
                var data;
                var number;

                if (!result.data) {
                    return;
                }

                data = result.data;
                number = ((data.number !=null) ? data.number + '.' : '') + (parseInt(data.workCount, 10) + 1);
                self.fieldValue('Number', number);
            }
        });
    }
});
