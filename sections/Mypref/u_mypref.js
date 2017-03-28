/**
 * Скрипт раздела "Настройки" (личный кабинет)
 */

irisControllers.classes.u_Mypref = IrisCardController.extend({
    onOpen: function () {
        g_Prepare_Custom_Section(this.getSectionHTML());
    },

    getSectionHTML: function () {
        var result  = '<div class="myprefs_div">';
        result += '<div class="myprefs_item myprefs_contact" onclick="' + this.instanceName() + '.openUserCard();">Мои данные</div>';
        result += '<div class="myprefs_item myprefs_pwd" onclick="' + this.instanceName() + '.openChangePasswordDialog();">Сменить пароль</div>';
        result += '</div>';

        return result;
    },

    openUserCard: function() {
        openCard({
            source_type: 'grid',
            source_name: 'Mycontact',
            rec_id: g_session_values['userid']
        });
    },

    openChangePasswordDialog: function() {
        // id будущего окна. должно быть случайное, без символа _ !!!
        var win_id = "wnd"+(Math.random()+"").slice(3);

        var win = new Window( {
            id: win_id,
            className: "iris_win",
            title: "Смена пароля",
            width: 450,
            height: 110
        });
        $(win).setConstraint(true, {
            left: 5,
            right: 5,
            top: 5,
            bottom: 5
        });

        var form_html = '';
        form_html += '<form>';
        form_html += '<table class="form_table" width="100%">';
        form_html += '<tbody>';
        form_html += '	<tr class="form_row">';
        form_html += '		<td class="form_table" width="1%" align="left">	<nobr><b>Текущий пароль</b><br/> </nobr> </td>';
        form_html += '		<td class="form_table" width="75%" colspan="3">';
        form_html += '			<input id="curpwd" class="edtText" type="password" autocomplete="off" elem_type="text" onblur="this.className = \'edtText\';" onfocus="this.className = \'edtText_selected\';" value="" mandatory="yes" style="width: 100%;"/>';
        form_html += '		</td>';
        form_html += '	</tr>';

        form_html += '	<tr class="form_row">';
        form_html += '		<td class="form_table" width="1%" align="left">	<nobr><b>Новый пароль</b><br/> </nobr> </td>';
        form_html += '		<td class="form_table" width="75%" colspan="3">';
        form_html += '			<input id="newpwd1" class="edtText" type="password" autocomplete="off" elem_type="text" onblur="this.className = \'edtText\';" onfocus="this.className = \'edtText_selected\';" value="" mandatory="yes" style="width: 100%;"/>';
        form_html += '		</td>';
        form_html += '	</tr>';

        form_html += '	<tr class="form_row">';
        form_html += '		<td class="form_table" width="1%" align="left">	<nobr><b>Подтверждение</b><br/> </nobr> </td>';
        form_html += '		<td class="form_table" width="75%" colspan="3">';
        form_html += '			<input id="newpwd2" class="edtText" type="password" autocomplete="off" elem_type="text" onblur="this.className = \'edtText\';" onfocus="this.className = \'edtText_selected\';" value="" mandatory="yes" style="width: 100%;"/>';
        form_html += '		</td>';
        form_html += '	</tr>';

        form_html += '<tr class="form_table_buttons_panel">';
        form_html += '<td colspan="4">';
        form_html += '<table class="form_table_buttons_panel">';
        form_html += '<tbody>';
        form_html += '<tr>';
        form_html += '<td align="right">';
        form_html += '<input type="button" onclick="' + this.instanceName() + '.changePassword(this);" value="Сменить пароль" style="width: 140px;" class="button" id="btn_ok"/>';
        form_html += '<input type="button" onclick="Windows.close(get_window_id(this))" value="Отмена" style="width: 70px;" class="button" id="btn_cancel"/>';
        form_html += '</td>';
        form_html += '</tr>';
        form_html += '</tbody>';
        form_html += '</table>';
        form_html += '</td>';
        form_html += '</tr>';

        form_html += '</tbody>';
        form_html += '</table>';
        form_html += '</form>';

        $(win).getContent().update(form_html);

        $(win).setDestroyOnClose();
        $(win).toFront();
        $(win).setZIndex(Windows.maxZIndex + 1);// для исправления глюка IE с просвечиванием списков
        $(win).showCenter(0);
    },

    changePassword: function(element) {
        var form = $(element).form;
        var successMessage = T.t('Ваш пароль успешно изменен');
        var errorMessage = T.t('Невозможно сменить пароль');

        this.request({
            method: 'changePassword',
            parameters: {
                current: form.curpwd.value,
                new1: form.newpwd1.value,
                new2: form.newpwd2.value
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText.evalJSON();
                var data;
                var message;

                if (!result.data) {
                    wnd_alert(errorMessage);
                    return;
                }

                data = result.data;

                if (data.isOk) {
                    message = successMessage;
                    Windows.close(get_window_id($(element)));
                }
                else {
                    message = data.errorMessage;
                }

                wnd_alert(message);
            },
            onFail: function(transport) {
                wnd_alert(errorMessage);
            }
        });
    }
});
