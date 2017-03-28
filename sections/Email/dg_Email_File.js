//********************************************************************
// Раздел "E-mail". Вкладка файлы.
//********************************************************************

irisControllers.classes.dg_Email_File = IrisGridController.extend({

    deleteButton: null,

    onOpen: function() {
        this.deleteButton = this.getDeleteButton();
        jQuery(this.deleteButton).hide();

        //Добавление кнопок на панель
        g_InsertUserButtons(this.el.id, [
            {
                name: T.t('Удалить'),
                onclick: this.instanceName() + ".openDeattachFileDialog();"
            },
            {
                name: T.t('Прикрепить файл'),
                onclick: this.instanceName() + ".openAttachFileDialog();"
            }
        ], 'iris_File');
    },

    getDeleteButton: function() {
        var gridId = this.el.id;
        var footer = getGridFooterTable(gridId);

        return jQuery(footer).find('input.button_delete');
    },

    openDeattachFileDialog: function() {
        var self = this;
        var gridId = this.el.id;
        var parentRecordId = this.$el.attr('detail_parent_record_id');
        var recordId = getGridSelectedID(gridId);
        var emailId = $(gridId).down('tr[rec_id="' + recordId + '"]').getAttribute('emailid');

        // если обычный файл, то вызовем функцию его удаления и выйдем
        if (emailId == parentRecordId) {
            this.deleteButton.trigger('click');
            return;
        }

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

    deAttachFile: function(fileId, emailId) {
        var gridId = this.el.id;

        Transport.request({
            section: "Email",
            'class': "dg_Email_File",
            method: 'deattachFile',
            parameters: {
                emailId: emailId,
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

    openAttachFileDialog: function() {
        var self = this;
        var emailId = this.$el.attr('detail_parent_record_id');

        this.customGrid({
            section: 'Email',
            class: 'dg_Email_File',
            method: 'renderSelectFileDialog',
            parameters: {
                emailId: emailId
            },
            onSelect: function(fileId) {
                self.attachFile(fileId);
            }
        });
    },

    attachFile: function(fileId) {
        var gridId = this.el.id;

        Transport.request({
            section: "Email",
            'class': "dg_Email_File",
            method: 'attachFile',
            parameters: {
                emailId: this.$el.attr('detail_parent_record_id'),
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