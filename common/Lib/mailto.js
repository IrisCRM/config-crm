/*
* Скрипт, заменяющий стандартную функцию ядра mail_to, которая вызывается при нажатии на конвертик письма
* вместо вызова стандартного почтового клиента откроется карточка нового письма
*/

function mail_to(p_this) {
/*
	// даем конвертику id, равный rnd ...
	if ($(p_this).getAttribute("id") == null) {
		var rnd_id = "rnd_"+(Math.random()+"").slice(3);
		$(p_this).setAttribute("id", rnd_id);
	}
	else {
		// если id уже есть то не меняем его, а просто передаем
		var rnd_id = $(p_this).getAttribute("id");
	}
*/
	var form = $(p_this.up('form'));
	var table = form._table.value;
	var recordid = form._id.value;
	var recordname = form.Name.value.gsub('"', '&quot;');
	var email = form.Email.value;
	var params = table+'#;'+recordid+'#;'+recordname+'#;'+email+'#;';
	
	openCard('grid', 'Email', '', params);
//	openCard('grid', 'Email', '', rnd_id);
}
