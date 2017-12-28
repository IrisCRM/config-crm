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
		var fkNameLabel = this.getFieldLabel("fkName");
		var pkNameLabel = this.getFieldLabel("pkName");
		var IndexNameLabel = this.getFieldLabel("IndexName");
		var labelCss = { "cursor": 'pointer', "color": "#3E569C" };

		// Автозаполнение ключей и индексов
    fkNameLabel.css(labelCss).on('click', function() {
      self.fillForeignKeys();
    });

    pkNameLabel.css(labelCss).on('click', function() {
      self.fillPrimaryKey();
    });

    IndexNameLabel.css(labelCss).on('click', function() {
      self.fillIndex();
    });
	},

	onChangeCode: function() {
		var newValue = this.fieldValue('Code').toLowerCase().gsub(/([а-я]|\s|\W)/, '');
		if (this.fieldValue('Code') != newValue) {
			this.fieldValue('Code', newValue);
			showNotify('В поле "Код (название в БД)" можно использовать только незаглавные латинские символы и цифры');
		}
	},

	fillForeignKeys: function() {
		if (this.fieldValue('fkName')) {
			return;
		}
		this.fieldValue('fkName', 'fk_' + 
			this.fieldDisplayValue('TableID') + '_' + this.fieldValue('Code'));
		this.fieldValue('OnDeleteID', '9f8bccc8-923a-3e15-6484-f7f4168294b2');
		this.fieldValue('OnUpdateID', '9f8bccc8-923a-3e15-6484-f7f4168294b2');
	},

	fillPrimaryKey: function() {
		if (this.fieldValue('pkName')) {
			return;
		}
		this.fieldValue('pkName', 'pk_' +
			this.fieldDisplayValue('TableID') + '_' + this.fieldValue('Code'));
	},

	fillIndex: function() {
		if (this.fieldValue('IndexName')) {
			return;
		}
		this.fieldValue('IndexName',
			this.fieldDisplayValue('TableID') + '_' + this.fieldValue('Code') + '_i');
	}
});
