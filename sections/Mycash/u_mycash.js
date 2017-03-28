/**
 * Скрипт раздела "Мой кошелек (для клиентов)"
 */

irisControllers.classes.u_Mycash = IrisCardController.extend({
    onOpen: function () {
        this.request({
            method: 'getSectionHTML',
            parameters: {
                instanceName: this.instanceName()
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText.evalJSON();

                if (!result.data) {
                    return;
                }
                g_Prepare_Custom_Section(result.data.html);
            }
        });
    },

    refreshBalance: function(element) {
        // $(element).addClassName('mycash_refresh_act');
        this.request({
            method: 'refreshValue',
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText.evalJSON();
                var balance;

                if (!result.data) {
                    return;
                }

                balance = result.data.balance;

                $('mycash_balancevalue').update(balance);
                // $(element).removeClassName('mycash_refresh_act');
                if (balance > 0) {
                    $('mycash_balancevalue').removeClassName('mycash_balancezero');
                }
                else {
                    $('mycash_balancevalue').addClassName('mycash_balancezero');
                }
            }
        });
    },

    increaseBalance: function() {
        window.open("payment/balance.php?user_id="+g_session_values['userid']);
    },

    dummy: function(vales) {
    }
});
