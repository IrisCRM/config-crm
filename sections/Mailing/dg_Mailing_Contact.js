//********************************************************************
// Раздел "Рассылка". Вкладка получатели.
//********************************************************************

irisControllers.classes.dg_Mailing_Contact = IrisGridController.extend({
    onOpen: function () {
        //Добавление кнопок на панель
        g_InsertUserButtons(this.el.id, [
            {
                name: T.t('Добавить') + '…',
                buttons: [
                    {
                        name: T.t('Выбрать контакт'),
                        onclick: this.instanceName() + ".openAddContactDialog();"
                    },
                    {
                        name: T.t('Из отчета'),
                        onclick: this.instanceName() + ".openAddContactFromReportDialog();"
                    }
                ]
            },
            {
                name: T.t('Удалить'),
                onclick: this.instanceName() + ".removeContact();"
            },
            {
                name: T.t('Предпросмотр'),
                onclick: this.instanceName() + ".previewEmail();"
            },
            {
                name: T.t('Создать письма'),
                onclick: this.instanceName() + ".openCreateEmailsDialog();"
            },
            {
                name: T.t('Удалить письма'),
                onclick: this.instanceName() + ".openDeleteEmailsDialog();"
            }

        ], 'iris_Mailing_Contact');

        this.addEmailLinks();
    },

    addEmailLinks: function() {
        var gridId = this.el.id;
        var grid = $(gridId);

        //Пройдем по всем строчкам таблицы и заполним поля-ссылки
        for (var i=1; i < grid.rows.length; i++) {
            if ($(grid.rows[i]).hasClassName('grid_void')) {
                break;
            }

            // сделаем поле "Контакт" ссылкой, при нажатии на которую открывается карточка контакта
            var span = $(grid.rows[i].down('td[alias="contact"]')).down('span');
            var new_value = '<span style="color: #3E569C; cursor: pointer; text-decoration: underline" onclick="openCard(\'grid\', \'Contact\', \'' + grid.rows[i].getAttribute('contactid') + '\')">'+span.innerHTML+'</span>';
            span.update(new_value);

            // сделаем поле email ссылкой
            if (grid.rows[i].getAttribute('t0_emailid') != '') {
                var email_value = '<span style="color: #3E569C; cursor: pointer; text-decoration: underline" onclick="openCard(\'grid\', \'Email\', \'' + grid.rows[i].getAttribute('t0_emailid') + '\')">открыть письмо...</span>';
                var span = $(grid.rows[i].down('td[alias="email"]')).down('span').update(email_value);
            }
        }
    },

    getMailingId: function() {
        return this.$el.attr('detail_parent_record_id');
    },

    getContactId: function() {
        return $(this.el.id).down('tr[rec_id="' + this.getSelectedId() + '"]').getAttribute('contactid')
    },

    openAddContactDialog: function() {
        var self = this;
        var mailingId = this.getMailingId();

        this.customGrid({
            section: 'Mailing',
            class: 'dg_Mailing_Contact',
            method: 'renderSelectContactDialog',
            parameters: {
                mailingId: mailingId
            },
            onSelect: function(contactId) {
                self.addContact(contactId);
            }
        });
    },

    addContact: function(contactId) {
        var gridId = this.el.id;

        Transport.request({
            section: 'Mailing',
            class: 'dg_Mailing_Contact',
            method: 'addContact',
            parameters: {
                mailingId: this.getMailingId(),
                contactId: contactId
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

    removeContact: function() {
        var gridId = this.el.id;
        var mailingId = this.getMailingId();
        var contactId = this.getContactId();

        Transport.request({
            section: 'Mailing',
            class: 'dg_Mailing_Contact',
            method: 'removeContact',
            parameters: {
                mailingId: mailingId,
                contactId: contactId
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

    openAddContactFromReportDialog: function() {
        var self = this;

        showParamsWindow({
            reportcode: 'mailing_contact',
            okLabel: "Добавить",
            onOk: function(form) {
                self.addContactFromReport(form, self.getMailingId());
            }
        }); // common/Lib/reportlib.js
    },

    addContactFromReport: function(form, mailingId) {
        var gridId = this.el.id;

        Transport.request({
            section: 'Mailing',
            class: 'dg_Mailing_Contact',
            method: 'addContactFromReport',
            parameters: {
                reportCode: 'mailing_contact',
                mailingId: mailingId,
                filters: getReportFilters(form)
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var data = transport.responseText.evalJSON().data;

                if (!data.isSuccess) {
                    wnd_alert(data.message);
                    return;
                }

                Windows.close(get_window_id(form));
                redraw_grid(gridId);
            }
        });
    },

    previewEmail: function() {
        var mailingId = this.getMailingId();
        var contactId = this.getContactId();
        var windowId = 'wnd_mailing_' + Math.floor(Math.random()*1000);
        var window = prepareCardWindow(windowId, 'Предпросмотр письма', 600, 500);

        Transport.request({
            section: 'Mailing',
            class: 'dg_Mailing_Contact',
            method: 'previewEmail',
            parameters: {
                mailingId: mailingId,
                contactId: contactId
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText.evalJSON();
                var data;

                if (!result.data) {
                    Windows.close(windowId);
                    return;
                }

                data = result.data;

                if (!data.isSuccess) {
                    Windows.close(windowId);
                    wnd_alert(data.message);
                    return;
                }

                window.getContent().innerHTML = data.subject + '<hr>' + data.body;
            }
        });
    },

    openCreateEmailsDialog: function() {
        var self = this;

        IrisDialog.confirm("Сейчас для каждого получателя, у которого еще создано письмо будет сформировано индивидуально письмо рассылки. Продолжить?", {
            onOk: function() {
                IrisDialog.closeInfo();
                self.createEmails(null);
            }
        });
    },

    createEmails: function(leftCount) {
        var self = this;
        var gridId = this.el.id;
        var mailingId = this.getMailingId();

        if (leftCount == null) {
            IrisDialog.info('Создаются письма рассылки...', {showProgress: true});
        } else {
            IrisDialog.setInfoMessage('Создаются письма рассылки...' + '<br>осталось '+leftCount+' '+getNumberCaption(parseInt(leftCount, 10), ['письмо', 'письма', 'писем']));
        }

        Transport.request({
            section: 'Mailing',
            class: 'dg_Mailing_Contact',
            method: 'createEmails',
            parameters: {
                mailingId: mailingId
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText.evalJSON();
                var data;

                if (!result.data) {
                    IrisDialog.closeInfo();
                    return;
                }

                data = result.data;

                if ((data.isSuccess) && (data.leftcount > 0)) {
                    self.createEmails(result.leftcount);
                    return;
                }

                IrisDialog.closeInfo();

                if (data.message != '') {
                    wnd_alert(data.message);
                }

                if (data.isSuccess) {
                    redraw_grid(gridId);
                }
            },
            onFail: function(transport) {
                IrisDialog.closeInfo();
            }
        });
    },

    openDeleteEmailsDialog: function() {
        var self = this;

        IrisDialog.confirm("Сейчас будут удалены все неотправленные письма данной рассылки. Продолжить?", {
            onOk: function() {
                IrisDialog.closeInfo();
                self.deleteEmails();
            },
            okLabel: "Удалить",
            cancelLabel: "Отмена"
        });
    },

    deleteEmails: function() {
        var gridId = this.el.id;

        IrisDialog.info('Удаляются письма рассылки...', { showProgress: true });

        Transport.request({
            section: 'Mailing',
            class: 'dg_Mailing_Contact',
            method: 'deleteEmails',
            parameters: {
                mailingId: this.getMailingId()
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText.evalJSON();
                var data;

                IrisDialog.closeInfo();

                if (!result.data) {
                    return;
                }

                data = result.data;

                if (data.message != '') {
                    wnd_alert(data.message);
                }

                if (data.isSuccess) {
                    redraw_grid(gridId);
                }
            },
            onFail: function(transport) {
                IrisDialog.closeInfo();
            }
        });
    }
});
