//********************************************************************
// Функции для работы с гридами
//********************************************************************

function editgridselectrow(p_this) {
	if (p_this.getAttribute('is_new') != 'yes')
		selectrow(p_this);
}
function callAddGridRecordFunc(p_this) {
	if (p_this.getAttribute('is_new') == 'yes')
		g_CreateNewGridRecord(p_this);

}

function g_CreateNewGridRecord(p_tr) {
	// Добавляем новую строку вместо "..." (копируем "...")
	Element.insert($(p_tr), {'after': p_tr.outerHTML});
	//p_tr = p_tr.parentNode.rows[p_tr.parentNode.rows.length-3];

	if (p_tr.getAttribute('is_new') == 'yes') {
		p_tr.setAttribute('is_new', 'no');
		//Установить стандартные стили для span
		var i;
		for (i=0; i<p_tr.cells.length; i++) {
			p_tr.cells[i].children[0].className = 'record';
			p_tr.cells[i].children[0].innerHTML = '';
		}		
		var table = $(p_tr).up('table.grid');

		//Вставка записи со значениями по умолчанию
		g_Common_InsertDefaultRowValues(p_tr, 
				table.getAttribute('newrow_default_values_script_name'), 
				table.getAttribute('newrow_after_new_record_function'), 
				table.getAttribute('newrow_params'));
		// меняем атрибут rec_id, чтобы строка таблицы 'добавить запись' появилась помле текущей ячейки
		p_tr.setAttribute('rec_id', 'null'); 
		// добавляем строку 'добавить запись'
		//g_DrawAddRecordRow(table.getAttribute('id'));
	}
}


function getGridFooterTable(p_grid_id) {
	if ($(p_grid_id).up().getAttribute('conttype') == 'inner') {
		// TODO: проверить в IE
		var grid_footer = $(p_grid_id).up('div[conttype="outer"]').up().down('.grid_footer');
		// miv 18.11.2009: в IE не работает .down('table.grid_footer')
		//var grid_footer = $(p_grid_id).up('div[conttype="outer"]').up().lastChild;
	}
	else {
		var grid_footer = $(p_grid_id).up().down('.grid_footer');
	}
	return grid_footer;
}


function g_Prepare_Custom_Section(p_html) {
    $('grid_area').update('<div type="custom_cont" style="height: 100%"></div>');
    // 18.08.2011: данный контейнер будет прокручиваться, 
    // если содержимое специального раздела не помещается на экране
	var custom_cont = $('grid_area').down();
	custom_cont.setStyle({
		//'height': custom_cont.getHeight() + 'px',
		'overflow': 'auto'
	});
	
	try {
    	custom_cont.update(p_html);
    }
    catch (e) {}
    
    try {
    	$('detail_area').update('');
    }
    catch (e) {}

    try {
    	$('tabs_area').update('');
    }
    catch (e) {}

    try {
    	$('filters_area').update('');
    }
    catch (e) {}
	
	RefreshMenuLoading(); // эмуляция окончания рисования закладки
	RefreshMenuLoading(); // эмуляция окончания рисования фильтров

	try {
		updateFiltersHeight($('filters_area').innerHTML);
	} 
	catch (e) {}
}


function g_InsertUserButtons_new(elem, buttons)
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
	jQuery(elem).find('.grid_footer_spacer').append(_.template(
			jQuery('#grid-buttons').html(), {data: buttons}));
}

//Добавление дополнительных кнопок на панель грида
//p_grid_id - id таблицы, куда добавляются кнопки
//p_buttons_html - html текст со списком кнопок
//[p_table_name] - название таблицы, для проверки места вставки
//[p_buttons_id='grid_user_buttons'] - id новой колонки с кнопками
//[p_style] - дополнительные стили для колонки
function g_InsertUserButtons(p_grid_id, p_buttons_html, p_table_name, p_buttons_id, p_style)
{
	var elem = getGridFooterTable(p_grid_id);

	//Перед вставкой кнопок проверим 
	//1. нет ли их там уже
	if ($(elem).down('[id="'+p_buttons_id+'"]')) {
		return;
	}

	//2. туда ли мы их вставляем
	if (p_table_name != undefined) {
		if ($(p_grid_id).getAttribute('table_name').toLowerCase() != p_table_name.toLowerCase()) {
			return;
		}
	}

	if (typeof(p_buttons_html) == 'object') {
		g_InsertUserButtons_new(elem, p_buttons_html);
		return;
	}

	if (typeof(p_buttons_id) == 'undefined') {
		var p_buttons_id = 'grid_user_buttons';
	}

	if (typeof(p_style) == 'undefined') {
		var p_style = '';
	}
	
	if ('' != p_style) {
		p_style = ' style="'+p_style+'"';
	}

	// Кнопки будут лежать в новой колонке
	var buttons_html = '<td class="grid_footer" id="'+p_buttons_id+'"'+p_style+'>' + p_buttons_html + '</td>'; 

	// Вставляем кнопки
	jQuery(elem).find('.grid_footer_spacer').append(buttons_html);
}



function g_GetButtonMenuHTMLElements(p_name, p_functions, p_collapse) {
	var first_name = '';

	var captions = [];
	var actions = [];

	//Пройдем по всем функциям и сформируем список названий и функций
	var i = 0;
	for (var name in p_functions) {
		captions.push(name);
		actions.push(p_functions[name]);

		if (0 == i) {
			first_name = name;
		}
		i++;
	}
	
	if (p_collapse && (i == 1) && (p_name == '')) {
		p_name = first_name;
	}
	
	var onclickhandler = "showButtonMenu(this);";
	//Если нужно свернуть и функция одна, то свернем
	if (p_collapse && (1 == i)) {
		onclickhandler = p_functions[first_name];
	}
	
	return {
		'name': p_name, 
		'onclickhandler': onclickhandler, 
		'captions_json': Object.toJSON(captions),//.replace(/"/g, '&quot;'), 
		'actions_json': Object.toJSON(actions)//.replace(/"/g, '&quot;')
	};
}
