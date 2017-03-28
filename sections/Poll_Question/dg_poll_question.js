/**
 * Раздел "Опросы". Вкладка "Вопросы". Таблица.
 */
irisControllers.classes.dg_Poll_Question = IrisGridController.extend({

	onAfterDelete: function(p_values) {
		//Получим id родителя, в котором был удаленный продукт
		var parent_id = null;
		try {
			var values = p_values.evalJSON();
			values.each(function(field) {
				if (("string" == typeof(field.Name)) && ('pollid' == (field.Name).toLowerCase())) {
					parent_id = field.Value;
				}
				if (("string" == typeof(field.Name)) && ('orderpos' == (field.Name).toLowerCase())) {
					orderpos = field.Value;
				}
			});
		}
			//Если не получили массив в параметре, например, то это ошибка
		catch (e) {
			return;
		}
		//Если не было в списке колонок pollid
		if ((null == parent_id) || (null == orderpos)) {
			return;
		}

		//Перенумеруем позиции во вкладке, если необходимо
		this.request({
			method: 'Renumber',
			parameters: {
				'_p_id': parent_id,
				'_p_orderpos': orderpos
			}
		});
	}
});
