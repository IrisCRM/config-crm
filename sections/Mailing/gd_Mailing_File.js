//********************************************************************
// Раздел "Рассылка". Вкладка файлы.
//********************************************************************

irisControllers.classes.dg_Mailing_File = IrisGridController.extend({
    onOpen: function () {
        this.hideStandartButtons();

        //Добавление кнопок на панель
        g_InsertUserButtons(this.el.id, [
            {
                name: T.t('Прикрепить'),
                onclick: this.instanceName() + ".openAttachFileDialog();"
            },
            {
                name: T.t('Удалить'),
                onclick: this.instanceName() + ".openDeattachFileDialog();"
            }
        ], 'iris_File');
    },

    hideStandartButtons: function() {
        var gridId = this.el.id;
        var footer = getGridFooterTable(gridId);

        jQuery(footer).find('input.button_delete').hide().siblings().hide();
    },

    openAttachFileDialog: function() {
        var self = this;
        var mailingId = this.$el.attr('detail_parent_record_id');

        this.customGrid({
            section: 'Mailing',
            class: 'dg_Mailing_File',
            method: 'renderSelectFileDialog',
            parameters: {
                mailingId: mailingId
            },
            onSelect: function(fileId) {
                self.attachFile(fileId);
            }
        });
    },

    attachFile: function(fileId) {
        var gridId = this.el.id;

        Transport.request({
            section: 'Mailing',
            class: 'dg_Mailing_File',
            method: 'attachFile',
            parameters: {
                mailingId: this.$el.attr('detail_parent_record_id'),
                fileId: fileId
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var data = transport.responseText.evalJSON().data;

                if (!data.isSuccess) {
                    wnd_alert(data.message);
                    return;
                }

                redraw_grid(gridId);
            }
        });
    },

    openDeattachFileDialog: function() {
        var self = this;
        var gridId = this.el.id;
        var parentRecordId = this.$el.attr('detail_parent_record_id');
        var recordId = getGridSelectedID(gridId);

        Dialog.confirm("Открепить данный файл от письма?<br>(сам файл удален не будет)",{
            onOk:function() {
                Dialog.closeInfo();
                self.deAttachFile(recordId, parentRecordId);
            }, className: "iris_win",
            width: 300,
            height: null,
            buttonClass: "button",
            okLabel:"Да",
            cancelLabel: "Нет"
        });
    },

    deAttachFile: function(fileId, malingId) {
        var gridId = this.el.id;

        Transport.request({
            section: 'Mailing',
            class: 'dg_Mailing_File',
            method: 'deattachFile',
            parameters: {
                malingId: malingId,
                fileId: fileId
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var data = transport.responseText.evalJSON().data;

                if (!data.isSuccess) {
                    wnd_alert(data.message);
                    return;
                }

                redraw_grid(gridId);
            }
        });
    }
});
