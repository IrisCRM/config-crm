//********************************************************************
// Раздел "E-mail". Карточка.
//********************************************************************

irisControllers.classes.c_Email = IrisCardController.extend({

    isDefaultValuesLoading: false,

    events: {
        'lookup:changed #ContactID': 'onChangeContactID',
        'change #EmailTypeID': 'onChangeEmailTypeID',
        'lookup:changed #EmailTemplateID': 'onChangeEmailTemplateID',
        'change #EmailAccountID': 'onChangeEmailAccountID'
    },

    state: {
        initialCardHeight: null,
        initialCkEditorHeight: null
    },

    onOpen: function() {
        var self = this;
        var form = $(this.el.id).down('form');
        var windowId = get_window_id(form);
        var cardParams = this.getCardParameters();
        var emailType = form.EmailTypeID.options[form.EmailTypeID.selectedIndex].getAttribute('code');

        try {
            // TODO: в функции onClickEmailLink передавать параметры не через parentId
            var parent = form._parent_id.value;
            var parentTable = parent.split('#;')[0];
            var parentId = parent.split('#;')[1];
            var parentName = parent.split('#;')[2];
            var emailAddress = parent.split('#;')[3];

            if (parentId) {
                form._parent_id.value = ''; // miv 2015.07.16: clear field for fix GetGridInfo error
            }

            if ('iris_Contact' == parentTable) {
                form.ContactID.value = parentName;
                form.ContactID.setAttribute('lookup_value', parentId);
                form.ContactID.setAttribute('original_value', parentName);
                $(form.e_to).value = emailAddress;
            }
            if ('iris_Account' == parentTable) {
                form.AccountID.value = parentName;
                form.AccountID.setAttribute('lookup_value', parentId);
                form.AccountID.setAttribute('original_value', parentName);
                $(form.e_to).value = emailAddress;
            }
        }
        catch (e) {
        }

        try {
            //Если редактируем входящее письмо
            if ((form._mode.value == 'update') && (emailType == 'Inbox')) {
                //Добавим себя в список прочитавших
                this.addSelfToReaders(form);
            }
        }
        catch (e) {
        }

        //Сделаем недоступным для редактирования входящее письмо
        if (emailType == 'Inbox') {
            this.disableInboxMessageFields(form);
        }
        if (emailType == 'Sent') {
            this.disableBodyField(form);
        }

        //Если создаем новое письмо
        if (form._mode.value == 'insert') {
            // если карточку открыли в режиме "ответить", то заполним нужные поля и выйдем
            if (cardParams.replyEmailId) {
                this.isDefaultValuesLoading = true; // miv 13.01.2011: чтобы не вызывалось событие, так как оно вешается до того, как приходят данные
                this.setReplyFields(form, cardParams.replyEmailId, cardParams.replyToAll);
            }
            if (cardParams.forwardEmailId) {
                this.isDefaultValuesLoading = true;
                this.setForwardFields(form, cardParams.forwardEmailId);
            }

            //Выберем ящик отправки письма по умолчанию
            this.fieldValue('EmailAccountID', jQuery('#EmailAccountID').find('option[is_primary="1"]').val());
            this.onChangeEmailAccountID();

            var elem_code;
            //Уберем типы писем и оставим те, которые могут быть у нового письма
            for (var n=form.EmailTypeID.options.length-1; n>=0; n--) {
                elem_code = form.EmailTypeID.options[n].getAttribute('code');
                if ((elem_code=='Inbox') || (elem_code=='Sent') || (elem_code=='Mailing_outbox') || (elem_code=='Mailing_sent')) {
                    form.EmailTypeID.options[n] = null;
                }
                if (elem_code=='Outbox') {
                    form.EmailTypeID.selectedIndex = n;
                }
            }

            //Значения по умолчанию
            if (form.getAttribute('reply_mode') != 'yes') {
                this.fillTemplate(form, true); // если карточку открыли не в режиме ответить, то заполним поля по шаблону
            }
        }

        if ((form._mode.value == 'update') && (emailType == 'Inbox')) {
            // нарисуем кнопку "создать инцидент"
            addCardFooterButton(windowId, 'top', T.t('Создать инцидент'), this.instanceName()+'.createIncident()');

            // нарисуем кнопку ответить
            addCardFooterButton(windowId, 'top', T.t('Ответить'), this.instanceName()+'.replyMessage()', T.t('Ответить на письмо'));
        }

        //Если новое письмо или с типом исходящее, то поле from должно быть выпадающим списком
        if ((form._mode.value == 'insert') || (emailType == 'Outbox')) {
            // Скрываем поле От[почтовый адрес]
            this.hideField('e_from');
            // Делаем поле От[учетная запись] обязательным
            this.fieldProperty('EmailAccountID', 'mandatory', 'yes');
            // Изменяем интерфейс для поля "кому"
            this.changeToField(form);
        }
        else {
            // Скрываем поле От[почтовый аккаунт]
            this.hideField('EmailAccountID');
        }

        // miv 25.08.2010: добавлена кнопка отправить
        if ((form._mode.value == 'insert') || (emailType == 'Outbox')) {
            addCardFooterButton(windowId, 'top', T.t('Отправить письмо'), this.instanceName()+'.saveAndSend(this)', '');
        }

        // Подстраиваем высоту ckeditor под высоту карточки
        var window = Windows.getWindow(windowId);
        this.state.initialCardHeight = window.height;
        var self = this;
        window.options.onResize = function () {
            self.onCardResize();
        };
        window.options.onMaximize = function () {
            self.onCardResize();
        };

        form._hash.value = GetCardMD5(get_window_id(form));
    },

    onCardResize: function() {
        var window = Windows.getWindow(this.el.id);
        var form = $(this.el.id).down('form');
        var editor = CKEDITOR.instances[form.body.getAttribute('actualelement')];
        if (!this.state.initialCkEditorHeight) {
            this.state.initialCkEditorHeight = jQuery('#'+this.el.id).find('#body').parent().height();
        }
        Windows.notify('onResize', window);
        editor.resize('100%', this.state.initialCkEditorHeight + window.height - this.state.initialCardHeight);
    },

    onAfterSave: function(recordId, mode, params, windowId) {
        var form = $(windowId).getElementsByTagName("form")[0];

        // отправим после отправки
        if (form.getAttribute('send_after_save') == 'yes') {
            this.sendEmail(recordId);
        }
    },

    onChangeContactID: function(event) {
        var form = $(this.el.id).down('form');

        this.onChangeEvent(event, {
            disableEvents: true,
            rewriteValues: false,
            letClearValues: false
        });
        this.fillTemplate(form);
    },

    onChangeEmailTypeID: function(event) {
        this.onChangeEvent(event, {
            disableEvents: true,
            rewriteValues: false,
            letClearValues: false
        });
    },

    onChangeEmailTemplateID: function(event) {
        var form = $(this.el.id).down('form');

        this.fillTemplate(form);
    },

    onChangeEmailAccountID: function(event) {
        this.fieldValue('e_from', this.fieldDisplayValue('EmailAccountID'));
        // form.e_from.value = form.EmailAccountID.options[form.EmailAccountID.selectedIndex].innerHTML;
    },


    disableInboxMessageFields: function(form) {
        this.disableBodyField(form);
        form.EmailTypeID.setAttribute('disabled', 'disabled');
        form.e_from.setAttribute('disabled', 'disabled');
        form.e_to.setAttribute('disabled', 'disabled');
        form.Subject.setAttribute('disabled', 'disabled');
    },

    disableBodyField: function(form) {
        try {
            setTimeout(function(){
                try {
                    var editor = CKEDITOR.instances[form.body.getAttribute('actualelement')];
                    editor.document.$.body.contentEditable = false;
                    editor.document.$.designMode = 'off';
                } catch (e) {}
            }, 1000);
        } catch (e) {}
    },

    addSelfToReaders: function(form) {
        Transport.request({
            section: "Email",
            'class': "g_Email",
            method: 'updateReaders',
            parameters: {
                recordId: form._id.value
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var grid = $(form._parent_id.value); // берем id родительского грида из карточки
                grid.rows[grid.getAttribute('selectedrow')].removeClassName('grid_newmail'); // отмечаем строчку как прочитаную
            }
        });
    },

    fillTemplate: function(form, start, isReplace) {
        var self = this;
        var contactId = this.fieldValue('ContactID');
        var templateId = this.fieldValue('EmailTemplateID');
        var isFillSubject = false;
        var isFillBody = false;

        if (this.isDefaultValuesLoading) {
            return; // miv 10.01.2011: если еще не подгрузились значения по умолчанию, то выйдем
        }

        if (typeof(start) == "undefined") {
            start = false;
        }
        if ('null' == contactId) {
            contactId = null;
        }
        if ('null' == templateId) {
            templateId = null;
        }

        if (!templateId || !contactId || (this.fieldValue('Subject') && this.fieldValue('body'))) {
            return;
        }

        if (!this.fieldValue('Subject')) {
            isFillSubject = true;
        }
        if (!this.fieldValue('body')) {
            isFillBody = true;
        }

        if ((!isReplace) && ((!isFillSubject) || (!isFillBody))) {
            IrisDialog.confirm(
                T.t('Сформировать содержание письма заново из шаблона?'), {
                onOk:function() {
                    IrisDialog.closeInfo();
                    self.fillTemplate(form, start, true);
                }
            });
            return;
        } else {
            isFillSubject = true;
            isFillBody = true;
        }

        Transport.request({
            section: "Email",
            'class': "c_Email",
            method: 'fillTemplate',
            parameters: {
                contactId: contactId,
                templateId: templateId,
                isFillSubject: isFillSubject,
                isFillBody: isFillBody,
                address: this.fieldValue('e_to')
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                c_Common_SetFieldValues_end(transport, form, true, function(){}, true);
                if (form.getAttribute('parent_body') != null) {
                    CKEDITOR.instances[form.body.getAttribute('actualelement')].setData(this.fieldValue('body') + form.getAttribute('parent_body'), function () {
                        form._hash.value = GetCardMD5(get_window_id(form));
                    });
                }
                if (start) {
                    form._hash.value = GetCardMD5(get_window_id(form));
                }
            }
        });
    },

    getCardParameters: function() {
        var params = this.parameter('params');
        params = params.isJSON() ? params.evalJSON() : {};
        return params;
    },

    setReplyFields: function(form, replyEmailId, replyToAll) {
        if (replyToAll === 'undefined') {
            replyToAll = false;
        }
        var self = this;

        $(form._params).insert({'after': '<input id="_reply_email_id" type="hidden" value="'+replyEmailId+'">'});
        form.setAttribute('reply_mode', 'yes');

        Transport.request({
            section: "Email",
            'class': "c_Email",
            method: 'getReplyFields',
            parameters: {
                replyEmailId: replyEmailId,
                replyToAll: replyToAll === true
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var data = transport.responseText.evalJSON().data;

                self.setFields(data, {
                    disableEvents: true,
                    rewriteValues: true,
                    letClearValues: false
                });

                this.isDefaultValuesLoading = false;

                // сохраняем текст письма, на которое отвечаем
                form.setAttribute('parent_body', GetFieldValueByFieldName(data.FieldValues, '_parent_body'));
                form._hash.value = GetCardMD5(get_window_id(form));
            }
        });
    },

    setForwardFields: function(form, forwardEmailId) {
        var self = this;

        Transport.request({
            section: "Email",
            'class': "c_Email",
            method: 'getForwardFields',
            parameters: {
                forwardEmailId: forwardEmailId
            },
            onSuccess: function(transport) {
                var data = transport.responseText.evalJSON().data;

                self.setFields(data);

                this.isDefaultValuesLoading = false;

                form.setAttribute('parent_body', GetFieldValueByFieldName(data.FieldValues, '_parent_body'));
                form._hash.value = GetCardMD5(get_window_id(form));
            }
        });
    },

    changeToField: function(form) {
        //Ячейка таблицы, в которой находится поле "кому"
        var e_to_cell = form.e_to.parentNode;
        //html текст элемента "кому"
        var e_to_html = e_to_cell.innerHTML;
        var e_to_value = form.e_to.value;  //mnv

        var new_html  = "<table style='width: 100%'><tbody><tr>";
        //1 столбец - текстовое поле "кому"
        new_html += "<td>"+e_to_html+"</td>";
        //2 столбец - выпадающий список компания / контакт
        var select_html  = "<select id='_to_mode' " +
            this.getElementProps("select") +
            " elem_type='select' style='width: 100%; margin-left: 2px;' mandatory='no'>";
        select_html += "<option value='Contact'>"+T.t('Контакт')+"</option>";
        select_html += "<option value='Account'>"+T.t('Компания')+"</option>";
        select_html += "</select>";
        new_html += "<td style='width: 120px'>"+select_html+"</td>";
        //3 столбец - скрытое поле lookup и кнопка выбора адреса "..."

        var hidden_elem_html = '<input type="text" elem_type="lookup" original_value="" value="" lookup_value="null" lookup_column="Email" lookup_grid_source_name="Contact" lookup_grid_source_type="grid" is_lookup="Y" style="display: none"  mandatory="no" id="_emailaddress"/>';
        var button_html = '<input ' + this.getElementProps("input") +
            ' type="button" onclick="openlookupwindow(this)" value="+" id="_emailaddress_btn"/>';
        new_html += "<td style='width: 20px'>"+hidden_elem_html+button_html+"</td>";
        new_html += "</tr></tbody></table>";
        e_to_cell.innerHTML = new_html;
        form.e_to.value = e_to_value;  //mnv

        // при смене select изменить параметры lookup
        $(form._to_mode).observe('change', function() {
            $(form._emailaddress).setAttribute('lookup_grid_source_name', p_form._to_mode.options[p_form._to_mode.selectedIndex].value);
        });

        // TODO: повесить событие на изменение lookup - ++ к основному и тут же очистить
        $(form._emailaddress).observe('lookup:changed', function() {
            if (form.e_to.value == '') {
                form.e_to.value = form._emailaddress.value;
            }
            else
            if (form.e_to.value.include(form._emailaddress.value) == false) {
                form.e_to.value += ', '+form._emailaddress.value;
            }

            // TODO: реализовать логику без использования c_Common_LinkedField_OnChange
            //Событие на добавление email (выбор контакта, компании...)
            // c_Common_LinkedField_OnChange(form, '_emailaddress', c_Email_ScriptFileName, false, function() {});

            form._emailaddress.value = '';
            form._emailaddress.setAttribute('lookup_value', 'null');
            form._emailaddress.setAttribute('original_value', '');
        });
    },

    // TODO: вынести в view
    getElementProps: function(type) {
        var isBootstrap = g_vars.template == 'bootstrap';
        var classicProps = {
            "input": "class=\"button\" style=\"margin: 0 0 0 4px; width: 20px;\"",
            "select": "class=\"edttext\" onblur= \"this.className = 'edtText';\" onfocus=\"this.className = 'edtText_selected';\"",
        };

        var bootstrapProps = {
            "input": "class=\"form-control input-sm edtText\" style=\"margin: 0 0 0 4px; width: 33px;\"",
            "select": "class=\"form-control input-sm edtText\"",
        };

        return isBootstrap ? bootstrapProps[type] : classicProps[type];
    },

    createIncident: function(element) {
        var params = {
            mode: 'incident_from_email',
            emailid: this.parameter('id')
        };

        this.parameter('hash', 'close');
        CloseCardWindow(jQuery("#" + this.el.id).find('form').get(0));

        openCard({
            source_name: 'Incident',
            card_params: Object.toJSON(params)
        });
    },

    // ответить на письмо в текущем окне
    replyMessage: function() {
        var form = $(this.el.id).down('form');
        var windowId = get_window_id(form);

        switchShadowCard(form, 'show'); // устанавливаем тень на карточке
        openCard({
            source_type: 'grid',
            source_name: 'Email',
            rec_id: '',
            // parent_id: '#'+this.parameter('id')+'#'+this.parameter('parent_id'),
            card_params: Object.toJSON({replyEmailId: this.parameter('id')}),
            replace_window_id   : windowId
        });
    },

    saveAndSend: function(element) {
        var form = $(this.el.id).down('form');

        form.setAttribute('send_after_save', 'yes');
        if ($(form.btn_ok)) {
            form.btn_ok.onclick();
            element.setAttribute('disabled', 'disabled');
        }
    },

    sendEmail: function(recordId) {
        var self = this;
        Transport.request({
            section: "Email",
            'class': "g_Email",
            method: 'sendEmail',
            parameters: {
                recordId: recordId,
                sendMode: 'Outbox'
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText;
                var message;
                if (result.isJSON() == true) {
                    message = result.evalJSON().data.message;
                }
                else {
                    message = T.t('Возникла ошибка при отправке почты');
                }
                self.notify(message);
            }
        });
    }
});
