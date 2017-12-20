/**
 * Скрипт карточки Пользователи
 */
irisControllers.classes.c_User = IrisCardController.extend({

  events: {
    'field:edited #_Password': 'updateHiddenPassword'
  },

  onOpen: function() {
    this.originalPassword = this.fieldValue('Password');
    this.fieldValue('Password', '');
    this.fieldProperty('Password', 'id', '_Password');
    this.getField('_Password').parent().
        append('<input id="Password" type="hidden" value="' +
            this.originalPassword + '"/>');

    this.parameter('hash', GetCardMD5(this.el.id));
  },

  updateHiddenPassword: function() {
    var currentPassword = this.fieldValue('_Password');

    this.autoEditEventsEnabled = false;
    this.fieldValue('Password',
        currentPassword ? hex_md5(currentPassword) : this.originalPassword);
    this.autoEditEventsEnabled = false;
  },
});