/**
 * Раздел "Опросы". Вкладка "Вопросы". Карточка.
 */
irisControllers.classes.dc_Poll_Question = IrisCardController.extend({
    onOpen: function () {
        var card_form = document.getElementById(this.el.id).getElementsByTagName("form")[0];
        bind_lookup_element(card_form.PollID, card_form.QuestionID, 'PollID');
    }
});
