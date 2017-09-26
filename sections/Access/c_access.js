/**
 * Скрипт карточки Access
 */

irisControllers.classes.c_Access = IrisCardController.extend({
    onOpen: function() {
        var form = $(this.el.id).down('form');
        var windowId = get_window_id(form);
        var params = jQuery.parseJSON(this.parameter('params') || '{}');

        if (this.parameter('mode') == 'insert') {
            this.fieldValue('R', '0');
            this.fieldValue('W', '0');
            this.fieldValue('D', '0');
            this.fieldValue('A', '0');
        }

        // массовая смена доступа
        if (params.mode == 'mass_update') {
            this.initMassChangeMode(windowId, form, params);
        }

        // устанавливаем значение RecordID
        form.RecordID.value = this.parameter('detail_column_value');
        // скрываем первый столбец, который содержит ID родительской записи
        jQuery(form.RecordID).parents('.form_row').hide();
        // UpdateCardHeight(windowId);

        this.parameter('hash', GetCardMD5(windowId));
    },

    initMassChangeMode: function(windowId, form, params) {
        var window = Windows.getWindow(windowId);
        window.setTitle("<b>Массовая смена доступа</b>");
        window.getContent().down('tr.card_header_middle_row').update('');

        $(form.RecordID).up('tr[class="form_row"]').insert({after: '<tr class="form_row"><td class="form_table" style="text-align: center" colspan=4>Укажите правило доступа, которое будет применено к '+params.id_list.length+' '+getNumberCaption(params.id_list.length, ['выбранной записи', 'выбранным записям', 'выбранным записям'])+'</td></tr>'});
        $(form.btn_ok).hide_().insert({after: '<input type="button" class="button" value="'+T.t('Применить доступ')+'" onclick="'+this.instanceName()+'.applyMassAccess(this)"/>'});

        var button_cont = $($(form.btn_cancel).up('table.form_table_buttons_panel').rows[0].cells[0]);
        var tmp_id = '_'+(Math.random()+'').slice(3);
        form.setAttribute('cb_id', tmp_id);
        var dj = "{&quot;value&quot;:&quot;&quot;,&quot;row_type&quot;:{&quot;0&quot;:&quot;domain&quot;},&quot;domain_values&quot;:[&quot;0&quot;,&quot;1&quot;],&quot;domain_captions&quot;:[&quot;\u041d\u0435\u0442&quot;,&quot;\u0414\u0430&quot;]}";
        button_cont.insert({top: '<input id="'+tmp_id+'" type="checkbox" class="checkbox" domain_json="'+dj+'" checked_index="0" checked/> <span style="margin: 3px"><label style="cursor: pointer" for="'+tmp_id+'" title="Исключить из доступа: Если вы полностью снимаете доступ, то указанный пользователь (роль) будет исключён из доступа к записи. Не исключать из доступа: Если вы полностью снимаете доступ, то указанный пользователь (роль) всё ещё может иметь доступ к записи, если если это позволяют другие записи во вкладке &quot;Доступ&quot;.">Исключить из доступа</label></span>'});
    },


    applyMassAccess: function() {
        var params = jQuery.parseJSON(this.parameter('params') || '{}');
        var form = $(this.el.id).down('form');

        Transport.request({
            section: "Access",
            'class': "c_Access",
            method: 'applyAccess',
            parameters: {
                tableId: params.table,
                ids: params.id_list,
                access: {
                    accessroleId: this.fieldValue('AccessRoleID'),
                    userId: this.fieldValue('ContactId'),
                    r: this.fieldValue('R'),
                    w: this.fieldValue('W'),
                    d: this.fieldValue('D'),
                    a: this.fieldValue('A'),
                    mode: ((form[form.getAttribute('cb_id')].checked == true) ? 'strict' : 'soft')
                }
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                try {
                    var result = transport.responseText.evalJSON().data;
                    if (result.success === 0)
                        wnd_alert(result.message, 350);
                    else {
                        Dialog.confirm(result.message,{onOk:function() {Dialog.closeInfo(); form._hash.value = 'close'; form.btn_cancel.onclick()}, className: "iris_win", width: 300, height:null, buttonClass:"button", okLabel:"Ок", cancelLabel:"Продолжить"});
                    }
                } catch (e) {
                    wnd_alert('Внимание! Не удалось изменить доступ у выбранных записей', 350);
                }
            }
        });
    }
});

