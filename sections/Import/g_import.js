//********************************************************************
// Раздел "Импорт". Таблица.
//********************************************************************

irisControllers.classes.g_Import = IrisGridController.extend({
    // Инициализация таблицы
    onOpen: function () {
        // Кнопка печать...
        g_InsertUserButtons(this.el.id, [
            {
                name: T.t('Действия') + '&hellip;',
                buttons: [
                    {
                        name: T.t('Выполнить импорт'),
                        onclick: this.instanceName() + ".checkDict();"
                    }
                ]
            }
        ], 'iris_Import');
    },

    // Проверить сопоставление справочников
	checkDict: function() {
		var self = this;
		var button = $(this.el.id);
		var form = button.down('form');
		var recordId = this.getSelectedId();

		button.setAttribute('disabled', 'disabled'); 
		Transport.request({
			section: "Import",
			'class': "g_Import",
			method: 'checkDict',
			parameters: {
                recordId: recordId
			}, 
			skipErrors: ['class_not_found', 'file_not_found'],
			onSuccess: function(transport) {
				self.checkDict_end(transport, button, recordId, self);
			}
		});
	},

	checkDict_end: function(transport, button, recordId, self) {
        var data = transport.responseText.evalJSON().data;

         // miv 19.08.2010: если не нужно сопоставлять справочники, то сразу предложим выполнить импорт
        if (!data.length) {
            IrisDialog.confirm("Все готово для выполнения импорта. Выполнить импорт данных из этого файла?", {
                onOk: function() {
                    self.startImportNow(recordId);
                    IrisDialog.closeInfo();
                }
            });
            return;
        }

        //Если возникла ошибка
        if (!IsEmptyValue(data.Error)) {
            wnd_alert("Внимание! Возникла ошибка при сопоставлении значений справочников.<br/><br/>"+data.Error, 400, 120);
            return;
        }

        if (!data.CheckDict) {
            return;
        }

        var win = prepareCardWindow("wnd"+(Math.random()+"").slice(3), "<b>Сопоставление значений справочников</b>", 600, 400);

        var win_html  = '';
        var win_html2  = '';
        var defaultPanelCode = null;

        //Контейнер всей карточки
        win_html += '<table  style="width: 100%; height: 100%;"><tbody>';

        win_html += '<tr class="info"><td class="info">';
        win_html += 'В этих справочниках найдены значения, которых еще нет в справочниках IRIS CRM.<br/>';
        win_html += 'Чтобы не засорять справочники CRM системы, просмотрите новые значения, если необходимо, <b>подкорректируйте значения в импортируемом файле</b> или добавьте новые значения в БД.<br/>';
        win_html += 'Если не синхронизировать справочники, то после импорта эти поля не будут заполнены.<br/>';
        win_html += '</td></tr>';


        //Контейнер div
        win_html += '<tr style="width: 100%; height: 100%;"><td style="padding: 0 10px 0 10px;">';

        win_html += '<div class="tabs10">';
        win_html += '	<ul id="tabSet1">';

        var divHeadTemplate = new Template('<li><a href="##{code}"><span>#{name}</span></a></li>');
        var rowTemplate = new Template('<tr class="grid"><td class="grid_row_string">#{name}</td></tr>');

        //Пройдемся по всем возвращенным справочникам
        data.CheckDict.each(function(dict) {
            if (defaultPanelCode == null) {
                defaultPanelCode = dict.DictCode;
            }
            win_html += divHeadTemplate.evaluate({code: dict.DictCode, name: dict.DictName});

            win_html2 += '<div id="'+dict.DictCode+'" class="panel" style="height: 230px; overflow-y: scroll">';
            win_html2 += '<table><tbody><tr>';

            //Таблица с текущими значениями справочника
            win_html2 += '<td>';
            win_html2 += '<table class="grid" style="border-bottom-width: 1px; border-top-width: 1px;"><tbody>';
            win_html2 += '<tr class="grid"><th class="grid">Текущие значения</th></tr>';
            if (dict.OldValues != undefined) {
                dict.OldValues.each(function(oldval) {
                    win_html2 += rowTemplate.evaluate({name: oldval.Name});
                });
            }
            win_html2 += '</tbody></table>';
            var dictname = dict.DictCode.replace(table_prefix, ''); //убрать префикс _iris
            win_html2 += '<a href="#" onclick="opengridwindow(\'\', \'\', \'dict\', \''+dictname+'\')">Открыть справочник</a>';
            win_html2 += '</td>';

            win_html2 += '<td width=10>';
            win_html2 += '</td>';

            //Таблица с новыми значениями справочника
            win_html2 += '<td>';
            win_html2 += '<table class="grid" style="border-bottom-width: 1px; border-top-width: 1px;"><tbody>';
            win_html2 += '<tr class="grid"><th class="grid">Новые значения</th></tr>';
            if (dict.NewValues != undefined) {
                dict.NewValues.each(function(newval) {
                    win_html2 += rowTemplate.evaluate({name: newval.Name});
                });
            }
            win_html2 += '</tbody></table>';
            //Кнопка "Добавить значения в справочник"
            win_html2 += '<input type="button" onclick="' + self.instanceName() + '.insertDictValues(\''+dict.DictCode+'\', \''+dict.DictTableID+'\', \''+encodeURIComponent(Object.toJSON(dict.NewValues))+'\');" value="Добавить значения в справочник"  class="button" id="btn_'+dict.DictCode+'"/>';
            win_html2 += '</td>';

            win_html2 += '</tr></tbody></table>';
            win_html2 += '</div>';
        });


        win_html += '	</ul>';
        win_html += '</div>';
        win_html += win_html2;


        win_html += '</td></tr>';

        //Кнопки
        win_html += '</td></tr>';
        win_html += '<tr><td>';
        win_html += '<table class="form_table_buttons_panel" style="height: 1%;"><tbody><tr><td style="vertical-align: middle;"/> ';
        win_html += '<td align="right">';
        win_html += '<input type="button" onclick="' + self.instanceName() + '.startImportNow(\''+recordId+'\');" value="Начать импорт" style="width: 120px;" class="button" id="btn_start_import"/>';
        win_html += '<input type="button" onclick="Windows.close(get_window_id(this))" value="Закрыть" style="width: 120px;" class="button" id="btn_cancel"/>';
        win_html += '</td></tr></tbody></table>';

        win_html += '</td></tr>';
        win_html += '</tbody></table>';

        win.setHTMLContent(win_html);

        //Вкладки
        var tabSet1 = new ProtoTabs('tabSet1', {
            defaultPanel: defaultPanelCode
        });
	},

    //Вставка значений справочника
    insertDictValues: function(dictCode, tableId, values) {
        var self = this;
        var win_html = '';
        win_html += '<p>Выполненяется импорт справочника...</p>';
        $(dictCode).update(win_html);

        Transport.request({
            section: "Import",
            'class': "g_Import",
            method: 'importDictValues',
            parameters: {
                tableId: tableId,
                values: decodeURIComponent(values)
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                self.insertDictValues_end(transport, dictCode);
            }
        });
    },

    insertDictValues_end: function(transport, dictCode) {
        var data = transport.responseText.evalJSON().data;
        var win_html = '';

        if (!IsEmptyValue(data.Error)) {
            win_html += '<p>Возникла ошибка.</p>';
            win_html += '<tr class="error"><td class="error">';
            win_html += data.Error;
            win_html += '</td></tr>';
        }
        else {
            win_html += '<p>Выполнено.</p>';
        }

        win_html += '<tr class="info"><td class="info">';
        win_html += 'Не забудьте пройтись по оставшимся справочникам!';
        win_html += '</td></tr>';
        $(dictCode).update(win_html);
    },

    //Выполнить импорт - кнопка в гриде
    // startImport: function (p_grid_id, p_button) {
    //     p_button.setAttribute('disabled', 'disabled');
    //
    //     var ResArray = new Array();
    //     ResArray = GetGridInfo(p_grid_id, 'update');
    //     var record_id = ResArray['selected_rec_id'];
    //
    //     g_Import_StartImportNow(record_id);
    // }

    //Начать импорт
    startImportNow: function(recordId) {
        var self = this;
        var wnd_id = ShowCustomWindow('<p>Подождите, пока выполняется импорт...</p>');

        Transport.request({
            section: "Import",
            'class': "g_Import",
            method: 'StartImport',
            parameters: {
                recordId: recordId
            },
            skipErrors: ['class_not_found', 'file_not_found'],
            onSuccess: function(transport) {
                self.startImport_end(transport, wnd_id)
            }
        });
    },

    // Начать импорт - ответ
    startImport_end: function (transport, windowId) {
        //Закроем окно "Подождать импорт"
        if (!IsEmptyValue(windowId)) {
            Windows.close(windowId);
        }

        var data = transport.responseText.evalJSON().data;
        var win_html = '';
        if (!IsEmptyValue(data.Error)) {
            win_html += '<p>Возникла ошибка!</p>';
            win_html += '<tr class="error"><td class="error">';
            win_html += data.Error;
            win_html += '</td></tr>';
            wnd_alert(win_html, 300, 200);
        }
        else {
            win_html += '<p>Импорт выполнен успешно.</p>';
            wnd_alert(win_html, 300, 200);
        }
    }
});
