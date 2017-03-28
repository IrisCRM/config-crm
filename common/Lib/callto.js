/*
* Скрипт, заменяющий стандартную функцию ядра call_to, которая вызывается при нажатии на иконку звонка
* вместо ссылки callto откроется карточка нового дела с типом исходящий звонок
* если же открыта карточка дела, то будет произведен звонок
*/

function call_to(p_this) {
	var primaryElem = $(p_this).up('table').down('input#' + p_this.getAttribute('id_primary'));
	var addlElem = $(p_this).up('table').down('input#' + primaryElem.getAttribute('id_addl'));
	var number = primaryElem.getAttribute('phone_value'); 
	var addlnumber = '';
	if (addlElem != undefined) {
		addlnumber = addlElem.getAttribute('phone_value');
	}
	if ((number == '') && (addlnumber == '')) {
		showNotify("Чтобы совершить звонок, необходимо указать номер");
		return;
	}
	
	//Если это карточка дела, то сформируем ссылку callto 
	if ((primaryElem.form._source_name.value == 'Task') && (primaryElem.form._detail_name.value == '')) {
		if (addlElem != undefined) {
			if (number == '') {
				number = addlElem.getAttribute('phone_value');
			}
			else if (addlElem.getAttribute('phone_value') != '') {
				number = number+'www'+addlElem.getAttribute('phone_value');
			}
		}

		if (number != '') {
			g_vars.do_exit = true;
			document.location.href='callto:' + number;	
		}
	}
	else {
		// откроем карточку дела с типом "звонок"
		openCard({
			source_name: 'Task', 
			card_params: Object.toJSON({
				mode: 'open_outcoming_call',
				phone: number,
				phoneaddl: addlnumber
			})
		});
	}
}

function skype_to(number) {
	if (number != '') {
		g_vars.do_exit = true;
		document.location.href = 'skype:' + number;	
	}
}