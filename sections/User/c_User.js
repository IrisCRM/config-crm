/**
 * Скрипт карточки Пользователи
 */
irisControllers.classes.c_User = IrisCardController.extend({

  events: {
    'field:edited #_Password': 'updateHiddenPassword',
    'click #_showchats': 'openIncomingMessagesDialog'
  },

  onOpen: function() {
    this.originalPassword = this.fieldValue('Password');
    this.fieldValue('Password', '');
    this.fieldProperty('Password', 'id', '_Password');
    this.getField('_Password').parent().
        append('<input id="Password" type="hidden" value="' +
            this.originalPassword + '"/>');

    // Нарисуем иконку
    this.addButtonForField({
      fieldId: 'TelegramChatId',
      buttonId: '_showchats',
      iconClass: 'wrench'
    });

    this.parameter('hash', GetCardMD5(this.el.id));
  },

  updateHiddenPassword: function() {
    var currentPassword = this.fieldValue('_Password');

    this.autoEditEventsEnabled = false;
    this.fieldValue('Password',
        currentPassword ? hex_md5(currentPassword) : this.originalPassword);
    this.autoEditEventsEnabled = false;
  },

  openIncomingMessagesDialog: function() {
    var self = this;
    this.customGrid({
      class: 'g_User',
      method: 'renderIncomingMessagesDialog',
      parameters: {},
      properties: {
        title: "Входящие сообщения боту за последние 24 часа",
        width: 900,
        height: 500,
      },
      onSelect: function(chatId) {
        self.fieldValue('TelegramChatId', chatId);
      }
    });
  }
});