/**
 * Раздел "Интервью". Таблица.
 */
irisControllers.classes.g_Interview = IrisGridController.extend({

	// Инициализация таблицы
	onOpen: function () {
		var poll_id = this.$el.attr('detail_parent_record_id');
		if (IsEmptyGUIDValue(poll_id)) {
			// Печатные формы
			printform_createButton(this.el.id, T.t('Печать') + '&hellip;');
			return;
		}

		// Добавим кнопки на панель грида
		g_InsertUserButtons(this.el.id, [
			{
				name: T.t('Добавить...'),
				onclick: "irisControllers.objects.g_Interview" + this.el.id + ".addFromReport('" + poll_id + "');"
			}
		], 'iris_Interview');
	},

	addFromReport: function(poll_id) {
		var self = this;
		showParamsWindow({
			reportcode: 'mailing_contact',
			okLabel: "Добавить",
			onOk: function(p_form) {
				self.addFromReportYes(p_form, poll_id);
			}
		}); // common/Lib/reportlib.js
	},

	addFromReportYes: function(p_form, p_poll_id) {
		var self = this;
		this.request({
			method: 'addFromReport',
			parameters: {
				'report_code': 'mailing_contact',
				'poll_id': p_poll_id,
				'filters': Object.toJSON(getReportFilters(p_form))
			},
			onSuccess: function(transport) {
				var result = transport.responseText.evalJSON().data;
				if (result.errno != 0) {
					wnd_alert(result.errm);
				}
				else {
					Windows.close(get_window_id(p_form));
					redraw_grid(self.el.id);
				}
			}
		});
	}
});
