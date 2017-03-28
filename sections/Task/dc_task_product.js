/**
 * Скрипт карточки платежа
 */

irisControllers.classes.dc_Task_Product = IrisCardController.extend({

    events: {
        'keyup #Count, #Price': 'onChangeAmounts',
        'lookup:changed #ProductID': 'onChangeProductID'
    },

    onChangeAmounts: function () {
        var p_form = $(this.el.id).down('form');
        var count = parseFloat(p_form.Count.value);
        var price = parseFloat(p_form.Price.value);

        if ((count.toString() == "NaN") || (price.toString() == 'NaN')) {
            p_form.Amount.value = '';
        }
        else {
            p_form.Amount.value = (count * price).toFixed(2);
        }
    },

    onChangeProductID: function (event) {
        this.onChangeEvent(event, {
            disableEvents: true
        });
    }
});
