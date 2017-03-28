//Получить порядковый номер элемента формы по значению параметра
function getItemIndexByParamValue(p_elem, p_param_name, p_param_value)
{
	if (p_param_name == 'class') { //это условие для ie
		for (var i=0; i<p_elem.length; i++) {	
			if (p_elem[i].className == p_param_value) {
				return i;
			}
		}
	}
	else {
		for (var i=0; i<p_elem.length; i++) {	
			if (p_elem[i].getAttribute(p_param_name) == p_param_value) {
				return i;
			}
		}
	}
	return -1;
}

//Установка значения в Select по ID
function SetSelectValueByID(p_control, p_id) {
	if (p_id == null) {
		return;
	}
	//if ((p_id.length == 36) || (p_control.getAttribute('is_radio') == 'yes')) {
		for (var i = 0; i < p_control.length; i++) {
			if (p_control.options[i].value == p_id) {
				p_control.selectedIndex = i;
				break;
			}
		}
        if (p_control.getAttribute('is_radio') == 'yes') {
            refreshRadioButtonValue(p_control);
        }
	//}
}

//Установка значения в Select по атрибуту (code и т.д.)
function SetSelectValueByAttribute(p_control, p_attr_name, p_value) {
	if (Object.isUndefined(p_value) == true) {
		return;
	}
	for (var i = 0; i < p_control.length; i++) {
		if (p_control.options[i].getAttribute(p_attr_name) == p_value) {
			p_control.selectedIndex = i;
			break;
		}
	}
}

// делаем высоту окна по содержимому, чтобы не подгонять
function UpdateCardHeight(p_wnd_id)
{
	Windows.focus(p_wnd_id);
	var card_window = Windows.getFocusedWindow();
	var new_height = 0;

	var new_height = card_window.getContent().children[0].offsetHeight;

	card_window.options.minHeight = new_height;
	card_window.options.maxHeight = new_height;
	card_window.setSize(card_window.getSize().width, new_height);
}

function addCardHeaderButton_new(elem, pos, buttons)
{
	for (var j = 0; j < buttons.length; j++) {
		if (typeof(buttons[j].buttons) == 'object') {
			var captions = [];
			var actions = [];
			for (var i = 0; i < buttons[j].buttons.length; i++) {
				captions.push(buttons[j].buttons[i].name);
				actions.push(buttons[j].buttons[i].onclick);
			}
			buttons[j].captions_json = Object.toJSON(captions).replace(/"/g, '&quot;')
			buttons[j].actions_json = Object.toJSON(actions).replace(/"/g, '&quot;')
			buttons[j].onclick = 'showButtonMenu(this);';
		}
	}
	jQuery(elem).find('div.card_' + pos + '_buttons_div').append(_.template(
			jQuery('#card-header-buttons').html(), {data: buttons}));
}

// добавляет кнопку-ссылку на верхней панели карточуи
function addCardHeaderButton(p_wnd_id, p_position, p_caption, p_onclick_event, p_title, p_functions) {
	var elem = $(p_wnd_id).down('div.card_header_div');
	if (elem == null) {
		return; // если верхней панели нету, то выйдем
	}
	var btn_container = $(p_wnd_id).down('div.card_header_div').down('div.card_'+p_position+'_buttons_div');
	if (btn_container == null) {
		return; // если неверно указана позиция кнопки (top|bottom), то выйдем
	}
	if (btn_container.innerHTML != '') {
		btn_container.insert({'bottom': '<span class="card_header_separator">|</span>'})
	}
	
	if (typeof(p_caption) == 'object') {
		addCardHeaderButton_new(elem, p_position, p_caption);
		return;
	}
	
	if (p_onclick_event != '') {
		p_onclick_event = 'onclick="'+p_onclick_event.gsub('"', '&quot;')+'"';
	}
	if (p_title != '') {
		p_title = 'title="'+p_title.gsub('"', '&quot;')+'"';
	}

	var captions_json = '';
	var actions_json = '';
	if (p_functions != undefined) {
		var elems = g_GetButtonMenuHTMLElements(p_caption, p_functions, 0);
		p_onclick_event = 'onclick="' + elems.onclickhandler + '"';
//		captions_json = '';
//		actions_json = '';
//		captions_json = 'captions_json="' + elems.captions_json + '"';
//		actions_json = 'actions_json="' + elems.actions_json + '"';
		captions_json = 'captions_json="' + elems.captions_json + '"';
		actions_json = 'actions_json="' + elems.actions_json + '"';
		p_caption = elems.name;
	}
		
	btn_container.insert({'bottom': '<span class="card_top_panel_button" '+p_title+' '+p_onclick_event+' '+captions_json+' '+actions_json+' onmouseover="$(this).addClassName(\'card_top_panel_button_hover\')" onmouseout="$(this).removeClassName(\'card_top_panel_button_hover\')">'+p_caption+'</span>'});
	var btn = btn_container.children[btn_container.children.length-1];
//	btn.setAttribute('captions_json', elems.captions_json);
//	btn.setAttribute('actions_json', elems.actions_json);
//	btn.setAttribute('captions_json', Object.toJSON(elems.captions_json).replace(/"/g, '&quot;'));
//	btn.setAttribute('actions_json', Object.toJSON(elems.actions_json).replace(/"/g, '&quot;'));
	return btn_container.children[btn_container.children.length-1];
}

// 25.08.2010: добавляет кнопку в панель кнопок карточки
function addCardFooterButton(p_wnd_id, p_position, p_caption, p_onclick_event, p_title, p_functions) {
	var form = $(p_wnd_id).getElementsByTagName("form")[0];

	var captions_json = '';
	var actions_json = '';
	if (p_functions != undefined) {
		var elems = g_GetButtonMenuHTMLElements(p_caption, p_functions, 1);
		p_onclick_event = elems.onclickhandler;
		captions_json = 'captions_json="' + elems.captions_json + '"';
		actions_json = 'actions_json="' + elems.actions_json + '"';
		p_caption = elems.name;
	}	

	var btn_id = '_'+'btn'+(Math.random()+'').slice(3);
	var btn_html = '<input type="button" '+captions_json+' '+actions_json+' onclick="'+p_onclick_event.gsub('"', '&quot;')+'" value="'+p_caption+'"'+(p_title != '' ? 'title="'+p_title+'"' : '')+' class="button" id="'+btn_id+'">';
	var button_cont = $($(form.btn_cancel).up('table.form_table_buttons_panel').rows[0].cells[0]);
	if (p_position == 'top')
		button_cont.insert({'top': btn_html});
	if (p_position == 'bottom')
		button_cont.insert({'bottom': btn_html});		
	return form[btn_id];
}
