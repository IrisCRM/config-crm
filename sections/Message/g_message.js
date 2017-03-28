irisControllers.classes.g_Message = IrisGridController.extend({

	onOpen: function () {
		g_InsertUserButtons(this.el.id, [
			{
				name: T.t('Ответить'),
				onclick: "irisControllers.objects.g_Message" + this.el.id + ".replyMessage('" + this.el.id + "', 0);"
			}
		], 'iris_Message');
		this.highlightNewMessages(this.el.id);
	},

	replyMessage: function(p_grid_id) {
		var row = $(p_grid_id).getAttribute('selectedrow');
		if (0 > row) {
			return;
		}
		var rec_id = $(p_grid_id).rows[row].getAttribute('rec_id');

		// открытие карточки, которой в качестве родителя передаем признак...
		openCard('grid', 'Message', '', '#'+rec_id+'#'+p_grid_id);
	},

	highlightNewMessages: function(p_grid_id) {
		var l_grid = $(p_grid_id);
		var l_ids_arr = [];
		//По всем строчкам таблицы
		for (var i=1; i < l_grid.rows.length; i++) {
			l_ids_arr.push(l_grid.rows[i].getAttribute('rec_id'));
		}

		this.request({
			method: 'highlightNewMessages',
			parameters: {
				'ids': Object.toJSON($A(l_ids_arr))
			},
			onSuccess: function(transport) {
				var result = transport.responseText.evalJSON().data;
				//По всем строчкам таблицы
				for (var i=1; i < l_grid.rows.length; i++) {
					if (result.indexOf(l_grid.rows[i].getAttribute('rec_id')) != -1) {
						$(l_grid.rows[i]).addClassName('grid_newmessage');
					}
				}
			}
		});
	}
});
