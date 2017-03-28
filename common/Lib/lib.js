/**************************************************
 * Функции общего назначения                      *
 **************************************************/

var table_prefix = "iris_";
var fields_in_lower_case = false;

if ('bootstrap' == g_vars.template) {
  var card_tab_selector = '.iris-card-tab';
  var card_row_selector = '.form-group';
}
else {
  var card_tab_selector = 'li.card_page';
  var card_row_selector = 'tr.form_row';
}

var IrisDomain = {
  control_type: {
    spacer: 1,
    splitter: 2,
    email: 3,
    url: 4,
    common: 5,
    textarea: 6,
    lookup: 7,
    select: 8,
    checkbox: 9,
    phone: 10,
    password: 11,
    radiobutton: 12,
    button: 13,
    detail: 14,
    matrix: 15
  },

  data_type: {
    string: 1,
    int: 2,
    decimal: 3,
    date: 4,
    datetime: 5,
    file: 6,
    id: 7
  },

  field_type: {
    common: 1,
    date: 2,
    file: 3,
    fk_column: 4,
    domain: 5
  },

  column_type: {
    common: 1,
    domain: 2,
    fk_column: 3,
    fk_column_extended: 4
  }
};

//Проверка, пустое ли значение
function IsEmptyValue(Value) 
{
	return typeof(Value)=="undefined" || Value==null || Value=="";  
}


//Проверка, пустое ли значение id
function IsEmptyGUIDValue(Value) 
{
	return typeof(Value)=="undefined" || Value==null || Value=="" || Value=="null";  
}


//Нарисовать окно
function ShowCustomWindow(Content, Title, Width, Height)
{
	if (IsEmptyValue(Width)) {
		Width = 400;
	}
	if (IsEmptyValue(Height)) {
		Height = 200;
	}
	if (IsEmptyValue(Title)) {
		Title = 'Внимание';
	}

	// id будущего окна. должно быть случайное, без символа _ !!!
	var win_id = "wnd"+(Math.random()+"").slice(3); 
	var win = new Window({
		id: win_id, 
		className: "iris_win", 
		title: "<b>"+Title+"</b>", 
		width: Width, 
		height: Height
	}); 
	win.setConstraint(true, {
		left: 5, 
		right: 5, 
		top: 5, 
		bottom: 5
	});

	win.getContent().insert(Content);
	win.setDestroyOnClose(); 
	win.toFront();
	win.setZIndex(Windows.maxZIndex + 1);// для исправления глюка IE с просвечиванием списков
	win.showCenter(0);

	return win_id;
}


// Строит окно с кнопками
// p_title - заголовок окна
// p_content - содержимое, текст
// p_buttons - массив кнопок (объектов со свойствами Название:Функция)
// [p_width=400] - ширина
// [p_height=200] - высота
function Common_ShowCustomWindow(p_title, p_content, p_buttons, p_width, p_height)
{
	var data = {
		content: p_content,
		buttons: []
	};
	for (var name in p_buttons) {
		data.buttons.push({
			name: name,
			onclick: p_buttons[name]
		});
	}
	var text_html = _.template(jQuery('#dialog').html(), {data: data});
	
	ShowCustomWindow(text_html, p_title, p_width, p_height);
}


function GetFieldValueByFieldName(p_values, p_field_name)
{
	var parent_id = null;
	try {
		var values = p_values;//.evalJSON();
		values.each(function(field) {
			if (p_field_name.toLowerCase() == (field.Name).toLowerCase()) {
				parent_id = field.Value;
			}
		});
	}
	//Если не получили массив в параметре например, то это ошибка
	catch (e) {
	}
	return parent_id;
}

function Common_GetJSON(p_string, p_extend_by) {
	var result = {};
	try {
		result = p_string.evalJSON();
	} catch (e) {
		if (p_extend_by != undefined)
			result = Object.extend(result, p_extend_by);
	}
	return result;
}

function lc(p_string)
{
	return fields_in_lower_case ? p_string.toLowerCase() : p_string;
}