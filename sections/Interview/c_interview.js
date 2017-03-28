/**
 * Раздел "Интервью". Карточка.
 */
irisControllers.classes.c_Interview = IrisCardController.extend({

  events: {
    'field:edited #AccountID, #ContactID': 'onChangeEvent',
    'field:edited #InterviewResultID': 'onChangeInterviewResultID'
  },

  onChangeInterviewResultID: function(event) {
    this.onChangeEvent(event, {
      disableEvents: true,
      letClearValues: false
    });
  }

});
