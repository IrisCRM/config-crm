//********************************************************************
// Функции для карточек редактирования записей
//********************************************************************

function c_Common_SetOnBlur(p_Element, p_form, p_FieldName, p_RequestFileName, p_rewrite, p_func, p_rewritezero, p_OnUpdate) {
	c_Common_SetOnEvent('blur', p_Element, p_form, p_FieldName, p_RequestFileName, p_rewrite, p_func, p_rewritezero, p_OnUpdate);
}

/**
 * @deprecated use IrisCardController::onChangeEvent() instead
 */
function c_Common_SetOnChange(p_Element, p_form, p_FieldName, p_RequestFileName, p_rewrite, p_func, p_rewritezero, p_OnUpdate) {
	var eventName = (p_Element.getAttribute('elem_type') == 'lookup') ? 'lookup:changed' : 'change';
    eventName = ((p_Element.getAttribute('elem_type') == 'select') && (p_Element.getAttribute('is_radio') == 'yes')) ? 'radiobutton:changed' : eventName; // miv 04.01.2011: для radiobutton
	c_Common_SetOnEvent(eventName, p_Element, p_form, p_FieldName, p_RequestFileName, p_rewrite, p_func, p_rewritezero, p_OnUpdate);
}

//Назначение события p_event с учетом возможности немедленного срабатывания
function c_Common_SetOnEvent(p_event, p_Element, p_form, p_FieldName, p_RequestFileName, p_rewrite, p_func, p_rewritezero, p_OnUpdate)
{
	if (typeof p_OnUpdate == "undefined") {
		p_OnUpdate = false;
	}

	$(p_Element).observe(p_event, function() {
		c_Common_LinkedField_OnChange(p_form, p_FieldName, p_RequestFileName, p_rewrite, p_func, p_rewritezero);
	} );
	if (c_Common_GetElementValue(p_Element) != null) {
		if ((p_form._mode.value == 'insert') || p_OnUpdate) {
			c_Common_LinkedField_OnChange(p_form, p_FieldName, p_RequestFileName, p_rewrite, p_func, p_rewritezero);
		}
	}
}

//Изменение поля lookup
/* @deprecated. Надо заменять на this.onChangeEvent()
    Если c_Common_LinkedField_OnChange() без параметров, то 
    this.onChangeEvent(event, {
      disableEvents: true,
      rewriteValues: false,  // p_rewrite
      letClearValues: false, // p_rewritezero
      onApply: function() {  // p_func
      }
    });

    Значения по умолчанию:
    this.onChangeEvent(event, {
      disableEvents: false,
      rewriteValues: true,
      letClearValues: true,
    });

    !!! И на php тогда обработчики надо переносить в s_ 
    !!! И перемеиновывать в них onChange на onBeforePost
    !!! И в параметрах $value брать так
    !!! $value = $this->fieldValue($params['old_data'], 'ContactID');

    !!! И в обработчике заменять GetLinkedValues() на $this->getLinkedValues
*/
function c_Common_LinkedField_OnChange(p_form, p_FieldName, p_RequestFileName, p_rewrite, p_func, p_rewritezero)
{
	//значение по умолчанию для необязательного аргумента p_rewrite
	if (typeof p_rewrite == "undefined") {
		p_rewrite = false;
	}
	
	//Если функция не передана, то вызов пустышки
	if (typeof p_func == "undefined") {
		p_func = function () {};
	}	
		
	//значение по умолчанию для необязательного аргумента p_rewrite
	if (typeof p_rewritezero == "undefined") {
		p_rewritezero = false;
	}
	
	var l_RecordID = c_Common_GetElementValue(p_form.elements[p_FieldName]);
//	if ((l_RecordID != '') && (l_RecordID != null) && (l_RecordID != 'null')) {
	if (!IsEmptyGUIDValue(l_RecordID)) {
		// Если карточка одгого раздела открыается из другого
		var source_name = p_form._source_name.value;
		if (p_form._source_name_real != undefined && 
				p_form._source_name_real.value) {
			source_name = p_form._source_name_real.value;
		}

		Transport.request({
			section: source_name, 
			'class': p_form._code.value, 
			method: 'onChange' + p_FieldName, 
			parameters: {
				id: p_form._id.value,
				name: p_FieldName, //deprecated
				value: l_RecordID
			}, 
			skipErrors: ['class_not_found', 'file_not_found'],
			onSuccess: function(transport) {
				var response = transport.responseText.evalJSON();

				// Если класс не найден, то вызовем обработчик по старому
				if ('error' in response) {
				    new Ajax.Request(g_path + p_RequestFileName, {
				    	parameters: {
				    		_func: 'FieldOnChange',
				    		_p_FieldName: p_FieldName,
				    		_p_FieldValue: l_RecordID,
				    		_p_id: p_form._id.value
				    	},
				    	onSuccess: function (transport) {
							c_Common_LinkedField_OnChange_end(transport, 
									p_form, p_rewrite, p_func, p_rewritezero); 
				    	}
				    });
				}
				else {
					c_Common_LinkedField_OnChange_end(transport, 
							p_form, p_rewrite, p_func, p_rewritezero); 
				}
			}
		});
    }
}


//Изменение поля lookup - ответ
function c_Common_LinkedField_OnChange_end(p_req, p_form, p_rewrite, p_func, p_rewritezero)
{
	//значение по умолчанию для необязательного аргумента p_rewrite
	if (typeof p_rewrite == "undefined") {
		p_rewrite = false;
	}
	
	//Если функция не передана, то вызов пустышки
	if (typeof p_func == "undefined") {
		p_func = function () {};
	}	
		
	//значение по умолчанию для необязательного аргумента p_rewritezero
	if (typeof p_rewritezero == "undefined") {
		p_rewritezero = false;
	}
	var l_res = c_Common_SetFieldValues_end(p_req, p_form, p_rewrite, p_func, p_rewritezero);

	return l_res;
}
 

//Заполнение значений по умолчанию
function c_Common_SetDefaultValues(p_form, p_RequestFileName, p_func, p_params)
{
	//Если функция не передана, то вызов пустышки
	if (typeof p_func == "undefined") {
		p_func = function () {};
	}	

	if (typeof p_params == "undefined") {
		p_params = new Array();
	}	
	
	var l_defreq = CreatePOSTReqest(p_RequestFileName);
	l_defreq.onreadystatechange = function() { c_Common_SetFieldValues_end(l_defreq, p_form, true, p_func); };	
	var phpfunc = '_func=GetDefaultValues';
	for (var i=0; i<p_params.length; i++) {
		phpfunc += '&' + p_params[i].Name + '=' + p_params[i].Value;
	}
   	l_defreq.send(phpfunc);
}


//Заполняем поля значениями из FieldValues
function c_Common_SetFieldValues_end(p_req, p_form, p_rewrite, p_func, p_rewritezero)
{
	if (p_req.readyState == 4) {
 		var l_res = p_req.responseText.evalJSON();
		if (l_res == null) {
			return false;
		}
		// Совместимость с новым форматом
		if ('data' in l_res) {
			l_res = l_res.data;
		}
		// miv: 06.07.2010: функция переписана и находится в engine/js/common.js
		return applyFieldValues(l_res, p_form, p_rewrite, p_func, p_rewritezero);
	}

	return false;
}


//Заполнение значений по умолчанию для грида
function g_Common_InsertDefaultRowValues(p_row, p_RequestFileName, p_func, p_params_str)
{
	//Если функция не передана, то вызов пустышки
	//if (typeof p_func == "undefined") {
	//	p_func = function () {};
	//}	

	//if (typeof p_params == "undefined") {
	//	p_params = new Array();
	//}
	// Если название серверного скрипта указано, то работаем по-старому
	if (p_RequestFileName != undefined) {
		var l_defreq = CreatePOSTReqest(p_RequestFileName);
		l_defreq.onreadystatechange = function() { g_Common_InsertRowFieldValues_end(l_defreq, p_row, p_func); };	
		var phpfunc = '_func=InsertRecord';
		//for (var i=0; i<p_params.length; i++) {
		//	phpfunc += '&' + p_params[i].Name + '=' + p_params[i].Value;
		//}
		phpfunc += '&' + p_params_str;
		l_defreq.send(phpfunc);
		return;
	}

	// Новая схема
	var table = p_row.parentNode.parentNode;
	new Ajax.Request(g_path+'/web.php', {
		parameters: {
			'_func': 'CreateNewGridRow', 
			'_p_source_name': table.getAttribute('source_name'), 
			'_p_parent_type': table.getAttribute('grid_type'), 
			'_p_detail_name': table.getAttribute('detail_name'),
			'_p_parent_id': table.getAttribute('detail_parent_record_id')
		}, 
		onSuccess: function(transport) {
			g_Common_InsertRowFieldValues_end(transport, p_row);
		}
	});
}


//Заполняем поля грида значениями из FieldValues
function g_Common_InsertRowFieldValues_end(p_req, p_row, p_func)
{
	if (p_req.readyState == 4) {
 		var l_res = p_req.responseText.evalJSON();
		if (l_res == null) {
			return false;
		}       

		var i;
		var FieldName;
		var FieldValue;
		var FieldCaption;
		var id = null;
		
		//значение по умолчанию для необязательного аргумента p_rewrite
		if (typeof p_rewrite == "undefined") {
			p_rewrite = false;
		}

		//Если функция не передана, то вызов пустышки
		//if (typeof p_func == "undefined") {
		//	p_func = function () {};
		//}	

		var l_table = p_row.parentNode.parentNode;	//таблица грида

       	//Считаем ответ
		for (i=0; i<l_res.FieldValues.length; i++) {
			FieldName = l_res.FieldValues[i].Name;
			FieldValue = l_res.FieldValues[i].Value;			
			FieldCaption = l_res.FieldValues[i].Caption != null ?  l_res.FieldValues[i].Caption : FieldValue;
			if (FieldName == 'ID') {
				id = FieldValue;
			}
			
			//Если ответ не пустой, то присвоим
			if ((FieldName != '') && (FieldName != null)) {
		       	//прорисуем значения по умолчанию
	        	var col_idx = getItemIndexByParamValue(l_table.rows[0].cells, 'db_field', FieldName);
	        	if (col_idx != -1) {
					//Форматируем значение для отображения
					if (jQuery(p_row.cells[col_idx]).hasClass('grid_row_decimal')) {
	    				FieldCaption = parseFloat(FieldCaption);
	    				FieldCaption = FieldCaption.toString() == 'NaN' ? '' : FieldCaption.toFixed(2);						
					}
					//Рисуем
					getGridValueContainer(p_row.cells[col_idx]).text(FieldCaption);
	    	    }
			}
		}

		//После вставки выберем элемент и зададим свойства (события)
		if (id != null) {
			p_row.setAttribute('rec_id', id);
		}
		selectrow(p_row);
		
		//p_func();
		if (p_func != undefined) {
			eval(p_func)
		}
		return true;
	}

	return false;
}


//Получить значение элемента
function c_Common_GetElementValue(p_Element) {
	var l_ElementValue = null;
				
	if (p_Element != null) {
		// Получим текущее значение
		var l_FieldType = p_Element.getAttribute('elem_type');
		if (l_FieldType == 'select' && p_Element.hasAttribute('multiple')) {
			l_ElementValue = jQuery(p_Element).val() || [];
			if (l_ElementValue.length == 1 && l_ElementValue[0] == '') {
				l_ElementValue = [];
			}
		}
		else
		if (l_FieldType == 'select') {
			if (p_Element.selectedIndex >= 0) {
				l_ElementValue = p_Element.options[p_Element.selectedIndex].value;
			}
		}
		else
		if (l_FieldType == 'phone') {
			l_ElementValue = p_Element.getAttribute('phone_value');
		} 
		else 
		if (l_FieldType == 'lookup') {
			l_ElementValue = p_Element.getAttribute('lookup_value');
			if (l_ElementValue == 'null') {
				l_ElementValue = null;
			}
		}
		else
		if (p_Element.getAttribute('is_wysiwyg') == 'yes') {
			// miv 25.08.2010: получение значения для элементов WYSIWYG
			if (CKEDITOR.instances[p_Element.getAttribute('actualelement')].checkDirty() == true) {
				l_ElementValue = CKEDITOR.instances[p_Element.getAttribute('actualelement')].getData(); // значение из ckeditor
			}
			else {
				l_ElementValue = p_Element.value; // если значение не менялось, то возьмем его из textarea		
			}
		}		
		else {
			if (p_Element.getAttribute('type') == 'checkbox') {
				var domain_json = p_Element.getAttribute('domain_json').evalJSON();
				var on_index = parseInt(p_Element.getAttribute('checked_index'), 10);
				var value_index = 0;
				if (((p_Element.checked == false) && (on_index == 0)) || ((p_Element.checked == true) && (on_index == 1))) {
					value_index = 1;
				}
				l_ElementValue = domain_json.domain_values[value_index];			
			}
			else {
				l_ElementValue = p_Element.value;
			}
		}
	}

	return l_ElementValue;
}

function c_Common_GetSelectCaptions(p_Element) {
	var result = [];

	if (!p_Element) {
		return null;
	}

	jQuery(p_Element).find('option:selected').each(function(i, val) {
  	result.push(jQuery(this).text());
  });

  return result.join(', ');
}


//Установить значение элемента
function c_Common_SetElementValue(p_Element, p_FieldValue) {
	if (p_Element != null) {
		//Получим тип. Присвоим в зависимости от типа поля.
		var l_FieldType = p_Element.getAttribute('elem_type');
		var l_FieldValue = p_FieldValue.Value;

		if (l_FieldType == 'phone') {
			SetPhoneValue(p_Element, l_FieldValue);
		}
		else
		if (l_FieldType == 'lookup') {
			var l_FieldCaption = typeof(p_FieldValue.Caption) != 'undefined' ? p_FieldValue.Caption : '';
			//p_Element.setAttribute('lookup_value', l_FieldValue);
			//p_Element.setAttribute('original_value', l_FieldCaption);
			//p_Element.value = l_FieldCaption;
			SetLookupValue(p_Element, l_FieldValue, l_FieldCaption)
		}
		else 
		if (l_FieldType == 'select') {
			if ((l_FieldValue == '') || (l_FieldValue == null)) {
				l_FieldValue = 'null';
			}
			SetSelectValueByID(p_Element, l_FieldValue);
		}
		else
		if (l_FieldType == 'datetime') {
			p_Element.value = new Date(Date.parseFormattedString(l_FieldValue));
		}
		else
		if (p_Element.getAttribute('type') == 'checkbox') {
			if (p_FieldValue = 0) {
				p_Element.checked = false;
			}
			else {
				p_Element.checked = p_FieldValue;
			}
		}
		else {
			// miv 25.08.2010: если элемент - WYSIWYG
			if (p_Element.getAttribute('is_wysiwyg') == 'yes') {
				CKEDITOR.instances[p_Element.getAttribute('actualelement')].setData(l_FieldValue);
			}			
			else {
				//Это было, в случае чего оставить только это 
				p_Element.value = l_FieldValue;
			}
		}
		jQuery(p_Element).trigger('field:edited');
	}
}

function c_Common_SetElementMandatory(p_element, p_flag) {
	var elemCap = $(p_element).up('td.form_table').previous().down('span');
	
	if ((p_flag == 1) || (p_flag == 'yes')) {
		p_element.setAttribute('mandatory', 'yes');
		elemCap.addClassName('card_elem_mandatory');
	} else {
		p_element.setAttribute('mandatory', 'no');
		elemCap.removeClassName('card_elem_mandatory');
	}
}

//Редактируем поле "Напоминание"
function c_Common_IsRemind_OnChange(p_form, p_FieldNames, p_DecValue)
{
	//Коррекция поля "Время напоминания" = "Начало" минус p_DecValue минут
	if ((p_form.IsRemind.value == 1) || (p_form.IsRemind.checked)) {
		var l_date = new Date();//'Invalid Date';
		var l_date1 = new Date();
		var i;
		for (i=0; i<p_FieldNames.length; i++) {
			l_date = new Date(Date.parseFormattedString(p_form.elements[p_FieldNames[i]].value));
			if (l_date != 'Invalid Date') {
				break;
			}
		}

		for (i=0; i<p_FieldNames.length; i++) {
			l_date1 = new Date(Date.parseFormattedString(p_form.elements[p_FieldNames[i]].value));
			if ((l_date1 != 'Invalid Date') && (l_date1 < l_date)) {
				l_date = l_date1;
			}
		}

		if (l_date != 'Invalid Date') {
			var l_date_remind = new Date(l_date);
			l_date_remind.setMinutes(l_date.getMinutes() - p_DecValue);
			p_form.RemindDate.value = l_date_remind.toFormattedString(true);
		}  
	}
}


//Редактируем поле "Дата напоминания"
function c_Common_RemindDate_OnChange(p_form)
{
	l_date = new Date(Date.parseFormattedString(p_form.RemindDate.value));
	//Коррекция поля "Время напоминания" ведет к установке напоминания
	if (l_date != 'Invalid Date') {
		var l_value = [];
		l_value['Value'] = 1; 
		c_Common_SetElementValue(p_form.IsRemind, l_value);
	}  
}


//Получить значение массива по значению одного из параметров
function GetArrayValueByParameter(p_Array, p_ParamName, p_ParamValue, p_FindParamName) {
	for (var i=0; i<p_Array.length; i++) {
		if (p_Array[i][p_ParamName] == p_ParamValue) {
			return p_Array[i][p_FindParamName];					
		}
	}
	return null;
}


//Получить html код элемента
// TODO: в представление и в объект. В идеале удалить и использовать представления, которые описаны на php
function GetElementHTMLCode(p_type, p_name, p_field, p_value, p_table, p_caption, p_filter_column, p_filter_value)
{
	var elem_html = '';
	if (p_type == 'string') {
		elem_html += '<td class="form_table" style="width: 1%"><nobr>'+p_name+'</nobr></td>';
		elem_html += '<td class="form_table" style="width: 75%">';
		elem_html += '<table width="100%" cellspacing="0"><tbody><tr><td>'+
			'<input type="text" elem_type="text" onblur="this.className = \'edtText\';" '+
			'onfocus="this.className = \'edtText_selected\';" value="'+p_value+'" '+
			'id="'+p_field+'" mandatory="no" class="edtText" style="width: 100%;"/>'+
			'</td></tr></tbody></table>'
		elem_html += '</td>';
	}

	if (p_type == 'date') {
		elem_html += '<td class="form_table" style="width: 1%"><nobr>'+p_name+'</nobr></td>';
		elem_html += '<td class="form_table" style="width: 75%">';
		elem_html += '<table width="100%" cellspacing="0"><tbody><tr><td><input type="text" elem_type="text" onblur="this.className = \'edtText\';" onfocus="this.className = \'edtText_selected\';" value="'+p_value+'" id="'+p_field+'" mandatory="no" maxlength="10" class="edtText" style="width: 100%;"/></td><td width="20"><div onclick="new CalendarDateSelect( $(this).parentNode.parentNode.getElementsByTagName(\'input\')[0], {time: false, buttons:false, embedded:false, year_range:10} );" class="calendar_img"/></td></tr></tbody></table>'
		elem_html += '</td>';
	}

	if (p_type == 'lookup') {
		elem_html += '<td class="form_table" style="width: 1%"><nobr>'+p_name+'</nobr></td>';
		elem_html += '</td>';
		elem_html += '<td class="form_table" style="width: 75%">';
		elem_html += '<table width="100%" cellspacing="0"><tbody><tr><td><input '+((p_filter_column != '' ? ' filter_column="'+p_filter_column+'"' : ''))+(p_filter_value != '' ? ' filter_value="'+p_filter_value+'"' : '')+' type="text" elem_type="lookup" onblur="this.className = \'edtText\'; TryToCloseAutoCompleteElem(this);" onfocus="this.className = \'edtText_selected\';" onkeyup="DrawAutoComplete(this, event)" original_value="'+p_value+'" value="'+p_caption+'" lookup_value="'+p_value+'" lookup_column="Name" lookup_grid_source_name="'+p_table+'" lookup_grid_source_type="grid" is_lookup="Y" style="width: 100%;" class="edtText" mandatory="no" id="'+p_field+'"/></td><td width="20"><input type="button" onclick="openlookupwindow(this)" value="..." id="'+p_field+'_btn" style="margin: 0px 0px 0px 1px; width: 20px;" class="button"/></td></tr></tbody></table>';
		elem_html += '</td>';
	}

	if (p_type == 'select') {
		elem_html += '<td class="form_table" style="width: 1%"><nobr>'+p_name+'</nobr></td>';
		elem_html += '</td>';
		elem_html += '<td class="form_table" style="width: 75%">';
		elem_html += '<table width="100%" cellspacing="0"><tbody><tr><td><input '+p_filter_column+' filter_value="'+p_filter_value+'" type="text" elem_type="lookup" onblur="this.className = \'edtText\'; TryToCloseAutoCompleteElem(this);" onfocus="this.className = \'edtText_selected\';" onkeyup="DrawAutoComplete(this, event)" original_value="'+p_value+'" value="'+p_caption+'" lookup_value="'+p_value+'" lookup_column="Name" lookup_grid_source_name="'+p_table+'" lookup_grid_source_type="dict" is_lookup="Y" style="width: 100%;" class="edtText" mandatory="no" id="'+p_field+'"/></td><td width="20"><input type="button" onclick="openlookupwindow(this)" value="..." id="'+p_field+'_btn" style="margin: 0px 0px 0px 1px; width: 20px;" class="button"/></td></tr></tbody></table>';
		elem_html += '</td>';
	}
	return elem_html;
}



//Показывет поле со значением в зависимости от типа колонки
function ShowValueFieldByType(p_res, p_row, p_colnum, p_caption)
{
	var res = p_res;
	var element_value = '';
	if ('guid' == res.ColumnInfo.Type) {
		if (res.ColumnInfo.SourceType) {
			element_value += '<table cellspacing="0" width="100%"><tbody><tr><td>';
			element_value += '<input id="GUIDValue" class="edtText" type="text" elem_type="lookup" onblur="this.className=\'edtText\'; TryToCloseAutoCompleteElem(this);" onfocus="this.className=\'edtText_selected\';" onkeyup="DrawAutoComplete(this, event);" original_value="" value="" lookup_value="" lookup_column="Name" lookup_grid_source_name="'+res.ColumnInfo.SourceName+'" lookup_grid_source_type="'+res.ColumnInfo.SourceType+'" autocomplete="off" is_lookup="Y" style="width: 100%;" mandatory="no"/>';
			element_value += '</td><td width="20">';
			element_value += '<input id="GUIDValue_btn" class="button" type="button" onclick="openlookupwindow(this);" value="..." style="margin: 0px 0px 0px 1px; width: 20px;" />';
			element_value += '</td></tr></tbody></table>';
			//element_value += '<input id="#GUIDValue" type="hidden" value="id"/>';
		}
		else {
			element_value += '<table style="width: 100%; table-layout: fixed;"><tbody><tr><td>';
			element_value += '<input id="GUIDValue" class="edtText" type="text" autocomplete="off" elem_type="text" onblur="this.className=\'edtText\';" onfocus="this.className=\'edtText_selected\';" value="" mandatory="no" style="width: 100%;"/>';
			element_value += '</td></tr></tbody></table>';
			//element_value += '<input id="#GUIDValue" type="hidden" value="string"/>';
		}
	}
	if (('string' == res.ColumnInfo.Type) || ('char' == res.ColumnInfo.Type) || ('text' == res.ColumnInfo.Type)) {
		element_value += '<table style="width: 100%; table-layout: fixed;"><tbody><tr><td>';
		element_value += '<input id="StringValue" class="edtText" type="text" autocomplete="off" elem_type="text" onblur="this.className=\'edtText\';" onfocus="this.className=\'edtText_selected\';" value="" mandatory="no" style="width: 100%;"/>';
		element_value += '</td></tr></tbody></table>';
		//element_value += '<input id="#StringValue" type="hidden" value="string"/>';
	}
	if ('int' == res.ColumnInfo.Type) {
		element_value += '<table style="width: 100%; table-layout: fixed;"><tbody><tr><td>';
		element_value += '<input id="IntValue" class="edtText" type="text" autocomplete="off" elem_type="text" onblur="this.className=\'edtText\';" onfocus="this.className=\'edtText_selected\';" value="" mandatory="no" style="width: 100%;"/>';
		element_value += '</td></tr></tbody></table>';
		//element_value += '<input id="#IntValue" type="hidden" value="int"/>';
	}
	if ('float' == res.ColumnInfo.Type) {
		element_value += '<table style="width: 100%; table-layout: fixed;"><tbody><tr><td>';
		element_value += '<input id="FloatValue" class="edtText" type="text" autocomplete="off" elem_type="text" onblur="this.className=\'edtText\';" onfocus="this.className=\'edtText_selected\';" value="" mandatory="no" style="width: 100%;"/>';
		element_value += '</td></tr></tbody></table>';
		//element_value += '<input id="#FloatValue" type="hidden" value="decimal"/>';
	}
	if (('date' == res.ColumnInfo.Type) || ('datetime' == res.ColumnInfo.Type)) {
		element_value += '<table cellspacing="0" width="100%"><tbody><tr><td>';
		element_value += '<input id="DateValue" class="edtText" type="text" autocomplete="off" elem_type="text" onblur="this.className=\'edtText\';" onfocus="this.className=\'edtText_selected\';" value="" mandatory="no" maxlength="16" style="width: 100%;"/>';
		element_value += '</td><td width="20">';
		element_value += '<div class="calendar_img" onclick="new CalendarDateSelect($(this).parentNode.parentNode.getElementsByTagName(\'input\')[0], {time: true, buttons: true, embedded: false, year_range: 10} );" />';
		element_value += '</td></tr></tbody></table>';
		//element_value += '<input id="#DateValue" type="hidden" value="datetime"/>';
	}
	$(p_row.cells[p_colnum]).update('<nobr>'+p_caption+'</nobr>');
	$(p_row.cells[p_colnum+1]).update(element_value);
}



 /*
//Преобразование даты в строку
function iris_DateTimeToString(p_date)
{
	var l_Date = p_date;

	l_day_str = l_Date.getDate() + '';
	l_month_str = (l_Date.getMonth() + 1) + '';
	l_year_str = l_Date.getFullYear() + '';

	l_hour_str = l_Date.getHours() + '';
	l_minute_str = l_Date.getMinutes() + '';

	if (l_day_str.length == 1) {
    		l_day_str = '0' + l_day_str;
	}
	
	if (l_month_str.length == 1) {
    		l_month_str = '0' + l_month_str;
	}


	if (l_hour_str.length == 1) {
    		l_hour_str = '0' + l_hour_str;
	}
	
	if (l_minute_str.length == 1) {
    		l_minute_str = '0' + l_minute_str;
	}

	l_date_str = l_day_str + '.' + l_month_str + '.' + l_year_str;

	l_time_str = l_hour_str + ':' + l_minute_str;

	return l_date_str + ' ' + l_time_str;
}
*/

//Сформировать запись FieldVlaue
function c_Common_MakeFieldValue(p_Value, p_Caption)
{
	var l_FieldValue = new Object();
	l_FieldValue.Value = p_Value;
	if (typeof(p_Caption) != 'undefined') {
		l_FieldValue.Caption = p_Caption;
	}
	return l_FieldValue;
}

//Объект "Карточка". В него будут добавляться со временем новые методы
/**
 * @deprecated
 * @type {{showField: IrisCard.showField, hideField: IrisCard.hideField, showTab: IrisCard.showTab, hideTab: IrisCard.hideTab, disableOptions: IrisCard.disableOptions, enableOptions: IrisCard.enableOptions}}
 */
var IrisCard = {
  //Покахать/скрыть поле на карточке
  showField: function (p_form, p_fieldid, p_visible) {
  	if (typeof(p_visible) == 'undefined') {
  		p_visible = true;
  	}
    if (p_visible) {
      p_form.down(lc('#'+p_fieldid)).up(card_row_selector).show();
    }
    else {
      p_form.down(lc('#'+p_fieldid)).up(card_row_selector).hide_();
    }
  },
  hideField: function (p_form, p_fieldid) {
  	this.showField(p_form, p_fieldid, false);
  },

  //Показать/скрыть закладку на карточке
  showTab: function (p_form, p_tabnumber, p_visible) {
  	if (typeof(p_visible) == 'undefined') {
  		p_visible = true;
  	}
    if (p_visible) {
      p_form.down(card_tab_selector, p_tabnumber).show();
    }
    else {
      p_form.down(card_tab_selector, p_tabnumber).hide_();
    }
  },
  hideTab: function (p_form, p_tabnumber) {
  	this.showTab(p_form, p_tabnumber, false);
  },

  disableOptions: function (p_form, p_fieldname, p_numbers) {
  	for (var i = 0; i < p_numbers.length; i++) {
	    p_form.down(lc('#'+p_fieldname)).down('option', p_numbers[i]).writeAttribute('disabled', true);
  	}
  },
  enableOptions: function (p_form, p_fieldname, p_numbers) {
  	for (var i = 0; i < p_numbers.length; i++) {
	    p_form.down(lc('#'+p_fieldname)).down('option', p_numbers[i]).removeAttribute('disabled');
	  }
  }
}