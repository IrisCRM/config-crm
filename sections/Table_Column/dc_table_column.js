/**
 * Скрипт карточки "Колонка таблицы"
 */

irisControllers.classes.dc_Table_Column = IrisCardController.extend({

	events: {
		'field:edited #Code': 'onChangeCode'
	},

	onOpen: function () {
		var card_form = document.getElementById(this.el.id).getElementsByTagName("form")[0];
		var self = this;

		// Автозаполнение ключей и индексов
		$(card_form.fkName).up('td.form_table').previous().down().setStyle({"cursor": 'pointer', "color": "#3E569C"}).observe('click', function() {
			if (self.fieldValue('fkName') == '') {
				self.fieldValue('fkName', 'fk_' + self.fieldDisplayValue('TableID') + '_' + self.fieldValue('Code'));
				self.fieldValue('OnDeleteID', '9f8bccc8-923a-3e15-6484-f7f4168294b2');
				self.fieldValue('OnUpdateID', '9f8bccc8-923a-3e15-6484-f7f4168294b2');
			}
		});
		$(card_form.pkName).up('td.form_table').previous().down().setStyle({"cursor": 'pointer', "color": "#3E569C"}).observe('click', function() {
			if (self.fieldValue('pkName') == '') {
				self.fieldValue('pkName', 'pk_' + self.fieldDisplayValue('TableID') + '_' + self.fieldValue('Code'));
			}
		});
		$(card_form.IndexName).up('td.form_table').previous().down().setStyle({"cursor": 'pointer', "color": "#3E569C"}).observe('click', function() {
			if (self.fieldValue('IndexName') == '') {
				self.fieldValue('IndexName', self.fieldDisplayValue('TableID') + '_' + self.fieldValue('Code') + '_i');
			}
		});
	},

	onChangeCode: function() {
		var newValue = this.fieldValue('Code').toLowerCase().gsub(/([а-я]|\s|\W)/, '');
		if (this.fieldValue('Code') != newValue) {
			this.fieldValue('Code', newValue);
			showNotify('В поле "Код (название в БД)" можно использовать только незаглавные латинские символы и цифры');
		}
	}

});
