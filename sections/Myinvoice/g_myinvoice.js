//********************************************************************
// Раздел "Мои счета". Таблица.
//********************************************************************

irisControllers.classes.g_Myinvoice = IrisGridController.extend({
    isEmailFetching: 0,

    onOpen: function () {
        var grid = $(this.el.id);

        //Добавление кнопок на панель
        g_InsertUserButtons(this.el.id, [
            {
                name: T.t('Оплатить счет'),
                onclick: this.instanceName() + ".openPayDialog();"
            }
        ], 'iris_Invoice');

        // Печатные формы
        printform_createButton(this.el.id, T.t('Печать') + '&hellip;');
    },

    openPayDialog: function() {
        var self = this;
        var invoiceId = getGridSelectedID(this.el.id);
        var errorHandler = function(message) {
            IrisDialog.alert(message || T.t('Ошибка'), {});
        };

        this.request({
            method: 'checkBalance',
            parameters: {
                invoiceId: invoiceId
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText.evalJSON();
                var data;

                if (!result.data) {
                    errorHandler();
                    return;
                }

                data = result.data;

                if (data.errorMessage) {
                    IrisDialog.alert(data.errorMessage, {});
                    return;
                }

                if (data.isOk) {
                    // Средств баланса достаточно, чтобы оплатить счет => оплата счета
                    IrisDialog.confirm("Внимание! Нажимая кнопку <b>«Оплатить»</b>, Вы соглашаетесь с тем, что Ваш баланс уменьшится на <b>"+data.amount+"</b>. Вернуть эту сумму будет нельзя. Если Вы НЕ хотите, чтобы с Вашего баланса списывались деньги, нажмите «Отмена».", {
                        onOk: function() {
                            IrisDialog.closeInfo();
                            self.payInvoice(invoiceId);
                        },
                        width: 400,
                        okLabel: "Оплатить",
                        cancelLabel: "Отмена"
                    });
                }
                else {
                    //Средств недостаточно => нужно пополнить баласн
                    IrisDialog.alert("Средств на Вашем кошельке недостаточно для оплаты счета. <br>Доступный баланс: <b>"+data.balance+"</b>.<br> Сумма оплаты: <b>"+data.amount+"</b>.", {
                        width: 400
                    });
                }
            },
            onFail: function(transport) {
                errorHandler();
            }
        });
    },

    payInvoice: function(invoiceId) {
        IrisDialog.info("идет оплата счета...", {
            width: 250,
            height: 100,
            showProgress: true
        });

        this.request({
            method: 'payInvoice',
            parameters: {
                invoiceId: invoiceId
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

                IrisDialog.alert(data.message, {
                    width: 350,
                    height: null,
                    ok: function(win) {
                        return true;
                    }
                });
                refresh_grid('grid');
                refresh_grid('detail');
            }
        });
    }
});
