/**
 * Раздел "Сообщения". Карточка.
 */
irisControllers.classes.c_Message = IrisCardController.extend({

    onOpen: function () {
		var card_form = $(this.el.id).down('form');
		var self = this;

        // делаем поля неактивными
        // дата
        card_form.MessageDate.setAttribute("disabled", "disabled");
        // статус
        card_form.StatusID.setAttribute("disabled", "disabled");
        // автор
        card_form.AutorID.setAttribute("disabled", "disabled");
        try {
            // кнопки "..." может и не быть если открыли в закладке
            card_form.AutorID_btn.setAttribute("disabled", "disabled");
        }
        catch (e) {}


        //Если создается новая запись
        if (card_form._mode.value == 'insert') {
            //Заполнение полей значениями по умолчанию
            this.setDefaultValues(card_form);

            if (card_form._parent_id.value.charAt(0) == '#') {
                // если карточку открыли в режиме "ответить", то заполним нужные поля и выйдем
                this.setReplyFields(card_form);
                return;
            }

            try {
                // если закладка раздела Решения
                if (card_form.AnswerID.value != "") {
                    self.setRecipient(card_form, 'GetRecipientFromAnswer', card_form.AnswerID.getAttribute('lookup_value'));
                }
            }
            catch (e1) {}

            try {
                // если закладка раздела Замечания
                if (card_form.BugID.value != "") {
					self.setRecipient(card_form, 'GetRecipientFromBug', card_form.BugID.getAttribute('lookup_value'));
                }
            }
            catch (e2) {}

            // если закладка Проекты, то - ajax запрос который вернет получателя сообщения (проект.ответственный или проект.клиент)
            if (card_form.ProjectID.value != "") {
				self.setRecipient(card_form, 'GetRecipientFromProject', card_form.ProjectID.getAttribute('lookup_value'));
            }
            else {
                //Если не проекты, но закладка, то это Контакты. Тогда Кому - контакт.
                if (card_form._detail_column_value.value != "") {
					self.setRecipient(card_form, 'GetRecipientFromContact', card_form._detail_column_value.value);
                }
                else {
                    //Если не закладка, то значит раздел
                    if (g_session_values['userrolecode'] == 'Client') {
                        //Установим получателя
						self.setRecipient(card_form, 'GenerateNewOwner', '');
                    }
                    else {
                        //Если это не клиент, то при выборе кому будет выбираться последний активный заказ
                        $(card_form.RecipientID).observe('lookup:changed', function() {
							self.setProject(card_form, card_form.RecipientID.getAttribute('lookup_value'));
                        });
                    }
                }
            }

            //Обновим хеш карточки, чтобы при отмене не задавался лишний вопрос
            card_form._hash.value = GetCardMD5(get_window_id(card_form));
        }
        //Если редактируем карточку
        else {
            this.WriteWhoReaded(card_form._parent_id.value, card_form._id.value); // запишем кто прочел сообщение

            //Если пользователь = адресат сообщения, то выставим статус сообщение "прочитано"
            if (getElementValue(card_form.RecipientID) == g_session_values['userid']) {
                SetSelectValueByAttribute(card_form.StatusID, 'code', 'Readed');
                // miv 02.04.2010: Обновим хеш карточки, чтобы при отмене не задавался лишний вопрос
                card_form._hash.value = GetCardMD5(get_window_id(card_form));
                // miv 02.04.2010: обновим значение в гриде
                var grid = $('grid');
                for (var i=1; i < grid.rows.length; i++) {
                    if (grid.rows[i].getAttribute('rec_id') == card_form._id.value) {
						$(grid.rows[i].cells[(grid.getAttribute('source_name') == 'Message' ? 5 : 4)])
							.down('span')
							.update(card_form.StatusID.options[card_form.StatusID.selectedIndex].innerHTML);
					}
                    if (grid.rows[i].getAttribute('rec_id') == null) {
						break;
					}
                }
            }

            // miv 30.08.2010: добавляем кнопку ответить
            addCardFooterButton(
            	this.el.id,
				'top',
				T.t('Ответить'),
				"irisControllers.objects.c_Message" + this.el.id + ".reply('" + this.el.id + "')",
				T.t('Ответить отправителю на данное сообщение')
			);
        }

        // miv 15.11.2010: если карточка в режиме чтения, то поле текст сообщения сделаем read only
        if ($(card_form.message).readAttribute('disabled') == 'disabled') {
            card_form.message.removeAttribute('disabled');
            card_form.message.setAttribute('readOnly', 'readOnly');
        }
    },

	/**
	 * Установить заказ
	 * @param p_form
	 * @param p_client_id
	 */
	setProject: function(p_form, p_client_id) {
		this.request({
			method: 'SetProject',
			parameters: {
				'client_id': p_client_id
			},
			onSuccess: function(transport) {
				c_Common_SetFieldValues_end(transport, p_form, true);
			}
		});
	},


	/**
	 * Функция определения получателя
	 * Если сообщение создали из закладки в разделе проекты, то получатели или клиент или ответственный в зависимости от текущего пользователя
	 * Если создали не из закладки, то просто назначим ответственного на основании выбраной услуги
	 */
	setRecipient: function(p_form, p_func, p_record_id) {
		this.request({
			method: p_func,
			parameters: {
				'record_id': p_record_id,
				'user_id': g_session_values['userid'],
				'user_type': g_session_values['userrolecode']
			},
			onSuccess: function(transport) {
				c_Common_SetFieldValues_end(transport, p_form, true);
			}
		});
	},

    setDefaultValues: function(p_form) {
        //Дата сообщения
        var today = new Date();
        p_form.MessageDate.value = today.toFormattedString(true); // 'DA-MO-YE НО:MI'

        //Статус
        var i;
        for (i=0; i<p_form.StatusID.options.length; i++) {
            if (p_form.StatusID.options[i].getAttribute('code') == 'New') {
                p_form.StatusID.selectedIndex = i;
                break;
            }
        }

        //Важность
        for (i=0; i<p_form.ImportanceID.options.length; i++) {
            if (p_form.ImportanceID.options[i].getAttribute('code') == 'Medium') {
                p_form.ImportanceID.selectedIndex = i;
                break;
            }
        }

        //Автор
        p_form.AutorID.setAttribute('original_value', g_session_values['username']);
        SetLookupValue(p_form.AutorID, g_session_values['userid']);
        p_form.AutorID.value = g_session_values['username'];
    },

    onAfterSave: function(p_rec_id, p_mode) {
        if (p_mode == 'insert') {
            //Изменить доступ (чтение)
            this.request({
                method: 'ChangeAccess',
                parameters: {
                    'rec_id': p_rec_id
                }
            });
        }
    },

    /**
     * Отметить прочитавших
     */
    WriteWhoReaded: function(p_grid, p_rec_id) {
        this.request({
            method: 'SaveReaded',
            parameters: {
                'rec_id': p_rec_id
            }
        });

        try {
            var grid = $(p_grid);
            var row = grid.getAttribute('selectedrow');
            if (row < 0)
                return;
            $(grid.rows[row]).removeClassName('grid_newmessage'); // отмечаем строчку как прочитаную
        } catch (e) {}
    },

    /**
     * Устанавливает поля тема, кому, заказ (если карточку открыли в режиме "ответить")
     */
    setReplyFields: function(p_form) {
        var res_array = p_form._parent_id.value.split('#');
        p_form._parent_id.value = res_array[2];
        var rec_id = res_array[1];
        var self = this;

        this.request({
            method: 'GetReplyFields',
            parameters: {
                'message_id': rec_id
            },
            onSuccess: function(transport) {
                var result = transport.responseText.evalJSON().data;
                //Тема
                p_form.Subject.value = result.subject;
                //Заказ
                if (Object.isUndefined(result.project_id) == false) {
                    self.setLookupValue(p_form.ProjectID, result.project_id, result.project_name);
                }
                //Кому
                if (Object.isUndefined(result.recipient_id) == false) {
                    self.setLookupValue(p_form.RecipientID, result.recipient_id, result.recipient_name);
                }

                //Обновим хеш карточки, чтобы при отмене не задавался лишний вопрос
                p_form._hash.value = GetCardMD5(get_window_id(p_form));
            }
        });
    },

    reply: function(p_wnd_id) {
        var card_form = $(p_wnd_id).getElementsByTagName("form")[0];
        openCard('grid', 'Message', '', '#'+card_form._id.value+'#'+card_form._parent_id.value);
        CloseCardWindow(card_form.btn_cancel);
    },

    setLookupValue: function(p_elem, p_value, p_caption) {
        p_elem.setAttribute('original_value', p_caption);
        p_elem.value = p_caption;
        SetLookupValue(p_elem, p_value);
    }

});
