//********************************************************************
// Раздел "E-mail". Таблица.
//********************************************************************

irisControllers.classes.g_Email = IrisGridController.extend({
    events: {
        'click [rec_id] td:nth-child(4)': 'onOpenMail',
        'click .mail_header_title_js': 'onCloseMail'
    },

    onOpenMail: function(e) {
        if (e.ctrlKey || e.shiftKey) {
            return;
        }
        var el = jQuery(e.currentTarget).parents('[rec_id]');
        var recordId = el.attr('rec_id');
        var wrapper = el.parents('.grid');
        wrapper.find('.grid_row_js').show();
        el.hide();
        wrapper.find('.record_wrapper_js').hide();
        var recordContent = wrapper.find('tr[parent_id="' + recordId + '"]');
        if (recordContent.length === 1) {
            recordContent.show();
            this.scrollToWrapper(el, recordContent);
            return;
        }
        var columnCount = el.find('>td').length;
        var recordWrapper = _.template(jQuery('#grid-record-wrapper').html(), {data: {
            id: recordId,
            columns: columnCount,
            content: 'Loading...'
        }});
        var mailContent = jQuery(recordWrapper).insertAfter(el);
        this.loadEmailData(recordId, mailContent, el);
    },

    loadEmailData: function(recordId, mailContent, el) {
        var self = this;
        this.request({
            method: 'getMailData',
            parameters: {
                id: recordId
            },
            onSuccess: function (transport) {
                var data = transport.responseText.evalJSON().data;
                mailContent.find('.content_wrapper_js').html(_.template(jQuery('#grid-email').html(), {
                    id: recordId,
                    baseUrl: g_path,
                    subject: data.subject,
                    from: data.from,
                    to: data.to,
                    cc: data.cc,
                    bcc: data.bcc,
                    contactName: data.contactName,
                    accountName: data.accountName,
                    emailTypeCode: data.emailTypeCode,
                    instanceName: self.instanceName(),
                    files: data.files
                }));
                self.scrollToWrapper(el, mailContent);
                el.removeClass('grid_newmail');
            }
        });
    },

    // fix mailContent shown out of scroll range in chrome
    scrollToWrapper: function(el, mailContent) {
        var innerDiv = el.closest('div[conttype="inner"]');
        var mailContentInvisibleOffset =
            mailContent.offset().top - innerDiv.offset().top;

        if (mailContentInvisibleOffset < 0) {
            innerDiv.scrollTop(innerDiv.scrollTop() + mailContentInvisibleOffset);
        }
    },

    onCloseMail: function(e) {
        var wrapper = jQuery(e.currentTarget).parents('.grid');
        wrapper.find('.record_wrapper_js').hide();
        wrapper.find('[rec_id]').show();
    },

    isEmailFetching: 0,

    onOpen: function() {
        var grid = $(this.el.id);

        this.highlightRows(grid.rows);

        // если грид нарисован для lookup элемента, то кнопки рисовать не будем
        if (grid.getAttribute('parent_elem_id') != '') {
            return;
        }

        //Добавление кнопок на панель
        g_InsertUserButtons(this.el.id, [
            {
                name: T.t('Отправить'),
                onclick: this.instanceName() + ".sendEmail();"
            },
            {
                name: T.t('Проверить почту'),
                onclick: this.instanceName() + ".fetchEmail(this);"
            }
        ], 'iris_Email');
    },

    highlightRows: function(rows) {
        var files_col_idx = getItemIndexByParamValue(rows[0].cells, 'db_field', 'files');
        var star_col_idx = getItemIndexByParamValue(rows[0].cells, 'db_field', 'star');
        var reply_col_idx = getItemIndexByParamValue(rows[0].cells, 'db_field', 'reply');

        // со второй строки, первая - заголовок
        for (var i = 1; i < rows.length; i++) {
            if ((rows[i].getAttribute('rec_id') == '') || (rows[i].getAttribute('rec_id') == null)) {
                break;
            }

            code_str = rows[i].getAttribute('et_code');

            //Отметим непрочитанные письма
            if ((code_str == 'Inbox') && (rows[i].getAttribute('t0_has_readed').indexOf(g_session_values['userid']) == -1)) {
                $(rows[i]).addClassName('grid_newmail');
            }

            // прорисуем вложения для писем
            try {
                var files_tr = $(rows[i].cells[files_col_idx]);
                if (files_tr.down('span').innerHTML == '@')
                    var files_html = '<div class="email_attachment_logo"></div>';
                else
                    var files_html = '';
                files_tr.update('').setStyle({padding: '0'}).update(files_html);
            } catch (e) {}

            // прорисуем звездочки для писем
            try {
                var star_td = $(rows[i].cells[star_col_idx]);
                var star_classname = 'email_star_logo';
                if (star_td.down('span').innerHTML == '*')
                    star_classname += ' email_star_logo_on';
                star_td.update('').setStyle({padding: '0'}).update('<div class="'+star_classname+'" onclick="'+this.instanceName()+'.triggerStar(event)" ondblclick="Event.stop(event);"></div>');
            } catch (e) {}
            // прорисуем значки для писем, на которые есть ответы
            try {
                var reply_td = $(rows[i].cells[reply_col_idx]);
                if (parseInt(reply_td.up('tr').getAttribute('replycnt'), 10) > 0)
                    reply_td.update('').setStyle({padding: '0'}).update('<div class="email_reply_logo" onclick="'+this.instanceName()+'.openReply(event)" ondblclick="Event.stop(event);" title="Нажмите, чтобы посмотреть ответ(ы) на письмо"></div>');
                else
                    reply_td.update('');
            } catch (e) {}
        }
    },

    reply: function(data) {
        var grid = $(this.el.id);
        var row = grid.getAttribute('selectedrow');
        var recordId = grid.rows[row].getAttribute('rec_id');

        if (!data) {
            data = {};
        }
        data.replyEmailId = recordId;

        openCard({
            source_type: 'grid',
            source_name: 'Email',
            rec_id: '',
            card_params: Object.toJSON(data)
        });
    },

    replyMessage: function() {
        this.reply();
    },

    replyToAll: function() {
        this.reply({
            replyToAll: true
        });
    },

    forwardMessage: function() {
        var grid = $(this.el.id);
        var row = grid.getAttribute('selectedrow');
        var recordId = grid.rows[row].getAttribute('rec_id');

        openCard({
            source_type: 'grid',
            source_name: 'Email',
            rec_id: '',
            card_params: Object.toJSON({
                forwardEmailId: recordId
            })
        });
    },

    fetchEmail: function(element) {
        var self = this;

        this.toggleFetchButton(element, false);

        if (this.isEmailFetching === 1) {
            return;
        }

        Transport.request({
            section: "Email",
            'class': "g_Email",
            method: 'fetchEmail',
            parameters: {},
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                console.log('fetchEmail onSuccess');
                var response = transport.responseText;

                self.isEmailFetching = 0;

                if (element) {
                    self.toggleFetchButton(element, true);
                }

                if (!response.isJSON()) {
                    debug('ошибка проверки почты');
                    self.notify(T.t('Возникла ошибка при получении почты'));
                    return;
                }

                self.notify(T.t('Отправлено задание на чтение почты'));
            },
            onFail: function(transport) {
                console.log('fetchEmail onFail');
                self.toggleFetchButton(element, true);
                self.isEmailFetching = 0;
                self.notify(T.t('Возникла ошибка при получении почты'));
            }
        });
        this.isEmailFetching = 1;
    },

    toggleFetchButton: function(element, isEnabled) {
        if (isEnabled) {
            element.removeAttribute("disabled");
            element.value = T.t('Проверить почту');
        } else {
            element.value = T.t('Загрузка писем') + '…';
            element.setAttribute("disabled", "disabled");
        }
    },

    sendEmail: function() {
        var self = this;
        var recordId = getGridSelectedID(this.el.id);

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
                var response = transport.responseText;
                var message;

                if (response.isJSON()) {
                    message = response.evalJSON().data.message;
                }
                else {
                    message = T.t('Возникла ошибка при отправке почты');
                }
                self.notify(message);
            }
        });
    },

    triggerStar: function(event) {
        var cell = Event.element(event);
        Event.stop(event); // прерываем просачивание события, чтобы не происходил выбор строки таблицы записей

        if (cell.hasClassName('email_star_logo_loading') == true) {
            return; // если в данный момент происходит смена состояния звездочки, то выйдем
        }

        cell.addClassName('email_star_logo_loading');
        Transport.request({
            section: "Email",
            'class': "g_Email",
            method: 'triggerStar',
            parameters: {
                recordId: cell.up('tr').getAttribute('rec_id'),
                currentValue: cell.hasClassName('email_star_logo_on')
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                var result = transport.responseText;
                cell.removeClassName('email_star_logo_loading');
                if (result.isJSON() == true) {
                    var data = result.evalJSON().data;
                    if (data.success == 1) {
                        cell.toggleClassName('email_star_logo_on');
                    }
                }
            }
        });
    },

    openReply: function(event) {
        var cell = Event.element(event);
        var row = cell.up('tr');

        Event.stop(event); // прерываем просачивание события, чтобы не происходил выбор строки таблицы записей
        if (row.getAttribute('replycnt') == '1')
            openCard({source_name: 'Email', rec_id: row.getAttribute('replyfirstid')});
        else {
            // TODO: изменить способ передачи условия
            opengridwindow(Math.random(), '', 'grid', 'Email', " T0.id in (select E.id from iris_email E where E.parentemailid = '"+row.getAttribute('rec_id')+"')");
        }
    }
});
