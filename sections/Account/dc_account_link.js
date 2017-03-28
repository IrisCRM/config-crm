/**
 * Раздел "Компании". Закладка "Связи". Карточка.
 * @class Клиентская логика для связей компании
 */ 
irisControllers.classes.dc_Account_Link = IrisCardController.extend({

  onAfterSave: function (p_rec_id, p_mode) {
    // После сохранения добавляем ссылку к связанной компании, 
    // если такая связь отсутствует
    Transport.request({
      section: 'Account', 
      'class': 'dc_Account_Link', 
      method: 'checkReverseLink', 
      parameters: {
        id: p_rec_id
      }
    });
  }

});
