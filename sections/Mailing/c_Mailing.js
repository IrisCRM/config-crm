//********************************************************************
// Раздел "Рассылка". Карточка.
//********************************************************************

irisControllers.classes.c_Mailing = IrisCardController.extend({
    onOpen: function () {
        var form = $(this.el.id).down('form');
        var windowId = get_window_id(form);

        form.StartDate.setAttribute('disabled', 'disabled');
        form.EndDate.setAttribute('disabled', 'disabled');

        if (this.parameter('mode') == 'update') {
            addCardFooterButton(windowId, 'top', T.t('Отправить рассылку'), this.instanceName()+'.sendMailing()', '');
        }
    },

    sendMailing: function(isRecursive) {
        var self = this;
        var mailingId = this.parameter('id');
        var endHandler = function(message) {
            Dialog.closeInfo();
            wnd_alert(message || 'Возникла ошибка при отправке рассылки');
        };

        if (!isRecursive) {
            this.showSendMailingDialog();
        }

        Transport.request({
            section: 'Mailing',
            class: 'c_Mailing',
            method: 'sendMailing',
            parameters: {
                mailingId: mailingId
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var response = transport.responseText.evalJSON();
                var operationMessageBox = $('mailing_operation');

                if (!response.data) {
                    endHandler();
                    return;
                }

                if (operationMessageBox.getAttribute('cancel') == 'yes') {
                    endHandler('Рассылка писем прервана');
                    return;
                }

                if (!response.data.isSuccess) {
                    endHandler(response.data.message);
                    return;
                }

                if (response.data.sendCount < response.data.allCount) {
                    self.sendMailing(true);
                }
            },
            onFail: function() {
                endHandler();
            }
        });

        this.startMailingStatusUpdater();
    },

    showSendMailingDialog: function() {
        var self = this;

        Dialog.alert(
            'Рассылка отправляется<div id="mailing_operation" type="mailing_operation" style="color: #3E569C">Подготовка...</div>' +
                '<div type="progrssbar" style="width: 250px; height: 15px; margin: 10px 20px 0px; border: 1px solid #3E569C">' +
                '<div type="pb_scale" style="width: 0; height: 100%; background-color: #3E569C;"></div></div>',
            {
                width:300,
                height:100,
                className: "iris_win",
                buttonClass: "button",
                okLabel: "Отмена",
                onOk: function() {
                    self.cancelMailing();
                }
            });
    },

    startMailingStatusUpdater: function() {
        var self = this;
        var mailingId = this.parameter('id');

        new PeriodicalExecuter(function(pe) {
            Transport.request({
                section: 'Mailing',
                class: 'c_Mailing',
                method: 'getMailingStatus',
                parameters: {
                    mailingId: mailingId
                },
                skipErrors: ['class_not_found', 'file_not_found'],
                onSuccess: function(transport) {
                    var response = transport.responseText.evalJSON();
                    var operationMessageBox = $('mailing_operation');
                    var messageBox = operationMessageBox.up('div.iris_win_content');

                    if (!response.data) {
                        return;
                    }

                    if (operationMessageBox == null) {
                        pe.stop();
                        return;
                    }

                    operationMessageBox.update(
                        'Отправлено писем ' + response.data.sendCount + ' из ' + response.data.allCount);
                    messageBox.down('div[type="pb_scale"]').setStyle({
                        "width": ((response.data.sendCount / response.data.allCount) * 100) + '%'
                    });

                    if (response.data.sendCount >= response.data.allCount) {
                        pe.stop();
                        if ($('mailing_operation_closebtn') == null) {
                            operationMessageBox.setStyle({'color': '#37be0e'});
                            messageBox.down('input.button').hide_().insert({
                                'after': '<input id="mailing_operation_closebtn" type="button" class="button" ' +
                                         'value="Закрыть" onclick="Dialog.closeInfo()">'
                            });
                        }
                    }
                }
            });
        }, 5);
    },

    cancelMailing: function() {
        var messageBox = $(Windows.getFocusedWindow().getId());

        messageBox.down('div[type="mailing_operation"]').update('Отмена операции').setAttribute('cancel', 'yes');
        messageBox.down('input.button').setAttribute('disabled', 'disabled');
    }
});
