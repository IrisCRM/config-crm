/**
 * Скрипт карточки Пользователи
 */
irisControllers.classes.c_users = IrisCardController.extend({

  events: {
    'click #btn_ok': 'create_password_and_apply'
  },

  onOpen: function() {
    // Поле Пароль делаем небазовым, создаем небазовую для подтверждения 
    // и базовую скрытую для значения
    var l_pwd = this.fieldValue('Password');
    this.fieldValue('Password', '');
    this.fieldProperty('Password', 'id', '_Password_1');

    var card_form = $(this.el.id).getElementsByTagName("form")[0];
    var l_table = $(card_form).down('table');

    $($(l_table).rows[3].cells[0]).update('Подтверждение');
    $($(l_table).rows[3].cells[1]).update(
      '<input id="_Password_2" type="password" onblur="this.className = '+"'"+'edtText'+"'"+'" onfocus="this.className = '+"'"+'edtText_selected'+"'"+'" value="" mandatory="no" style="width: 100%;" class="edtText"/>' +
      '<input id="Password" type="hidden" value="' + l_pwd + '"/>');

    this.fieldProperty('btn_ok', 'onclick', '');

    // Обновим хеш карточки, чтобы при отмене не задавался лишний вопрос
    this.parameter('hash', GetCardMD5(this.el.id));
  },

  create_password_and_apply: function() {
    var pwd = this.fieldValue('_Password_1');

    // Проверки на правильность заполнения или не заполнения полей
    if (pwd != this.fieldValue('_Password_2')) {
      this.notify('Пароли не совпадают!');
      return;
    }

    if (this.parameter('mode') == 'insert' && (pwd == '' || pwd == null)) {
      this.notify('Пароль не задан!');
      return;
    }

    // Если пароль не меняли, то сохраним что было. 
    // request для получения хеша не нужен
    if ((pwd == '') || pwd == null) {
      apply_card_changes('save_and_close', this.el.id);
      return;
    }
    
    this.fieldValue('Password', hex_md5(pwd));
    apply_card_changes('save_and_close', this.el.id);
  }

});