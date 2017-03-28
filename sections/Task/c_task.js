/**
 * Скрипт карточки дела
 */

irisControllers.classes.c_Task = IrisCardController.extend({

  events: {
    'field:edited #ContactID, #ObjectID, #ProjectID': 'onChangeLookup',
    'field:edited #IssueID, #BugID, #IncidentID': 'onChangeLookup',
    'field:edited #OfferID, #PactID, #InvoiceID': 'onChangeLookup',
    'field:edited #PaymentID, #FactInvoiceID, #DocumentID': 'onChangeLookup',
    'field:edited #AccountID': 'onChangeAccountID',
    'field:edited #TaskStateID': 'onChangeTaskStateID',
    'field:edited #TaskResultID': 'onChangeEvent',
    'field:edited #TaskTargetID': 'onChangeTaskTargetID',
    'field:edited #NextTaskTargetID': 'onChangeEvent',
    'field:edit #StartDate': 'onChangeStartDate',
    'field:edit #RemindDate': 'onChangeRemindDate',
    'field:edit #IsRemind': 'onChangeIsRemind'
  },

  onChangeLookup: function(event) {
    this.onChangeEvent(event, {
      disableEvents: true,
      rewriteValues: false,
      letClearValues: false
    });
  },

  onChangeAccountID: function () {
    // Поле Контакт зависит от поля Компания
    this.bindFields('AccountID', 'ContactID');
    this.setTaskName();
  },

  onChangeTaskStateID: function() {
    var code = this.getField('TaskStateID')
        .find('[value=' + this.fieldValue('TaskStateID') + ']').attr('code');
    this.showIncubator(code == 'Finished' || code == 'Canceled'
        || this.fieldValue('NextTaskTargetID') != 'null');
  },

  onChangeTaskTargetID: function() {
    var task_target_id = this.fieldValue('TaskTargetID');
    var next_target = this.getField('NextTaskTargetID');
    next_target.find('option').each(function() {
      var option = jQuery(this);
      if (!option.data('original-caption')) {
        option.data('original-caption', option.html());
      }
      if (option.val() != 'null' && option.val() == task_target_id) {
        option.html('&rarr; ' + option.html());
      }
      else {
        option.html(option.data('original-caption'));
      }
    });
    this.setTaskName();
  },

  onChangeStartDate: function() {
    var prevStartDate = this.$el.data('prevStartDate');
    var finishDate = this.fieldValue('FinishDate');
    var l_date_end;
    //Коррекция поля "Завершение" = "Начало" + 2 часа
    var l_date = new Date(Date.parseFormattedString(this.fieldValue('StartDate')));
    if (l_date != 'Invalid Date') {
      if (!prevStartDate || !finishDate) {
        l_date_end = new Date(l_date);
        l_date_end.setMinutes(l_date.getMinutes() + 120);
      }
      else {
        // Если указана и дата начала и дата завершения, 
        // то не меняя длительности подправим дату завершения
        l_date_end = new Date(Date.parseFormattedString(finishDate));
        var prev = new Date(Date.parseFormattedString(prevStartDate));
        l_date_end.setSeconds(l_date_end.getSeconds() + (l_date - prev) / 1000);
      }
      this.fieldValue('FinishDate', l_date_end.toFormattedString(true));

      //Скорректируем время напоминания
      var p_form = $(this.el).down('form');
      c_Common_IsRemind_OnChange(p_form, Array('StartDate'), 15);
    }
    this.$el.data('prevStartDate', this.fieldValue('StartDate'));
  },

  onChangeIsRemind: function() {
    c_Common_IsRemind_OnChange($(this.el).down('form'), 
        Array('StartDate'), 15);
  },

  onChangeRemindDate: function() {
    c_Common_RemindDate_OnChange($(this.el).down('form'));
  },

  setTaskName: function() {
    var name = this.fieldDisplayValue('Name');
    var account = this.fieldDisplayValue('AccountID');
    var target = this.fieldDisplayValue('TaskTargetID');
    if (!name && account && target) {
      this.fieldValue('Name', account + ': ' + target);
    }
  },

  onOpen: function () {
    // Поле Контакт зависит от поля Компания
    this.bindFields('AccountID', 'ContactID');
    this.bindFields('TaskTypeID', 'TaskResultID');
  
    var cardParams = jQuery.parseJSON(this.parameter('params') || '{}');
    if (cardParams.mode == 'addFromCalendar') {
      this.addFromCalendar(cardParams);
    }

    this.fieldProperty('CreateID', 'readonly', true);
    this.onChangeTaskStateID();
    this.onChangeTaskTargetID();

    if (this.parameter('HaveNextTask') > 0) {
      this.fieldProperty('NextTaskTargetID', 'readonly', true);
      this.fieldProperty('NextStartDate', 'readonly', true);
      this.fieldProperty('_select_next_target', 'readonly', true);
    }
    this.$el.data('prevStartDate', this.fieldValue('StartDate'));
  },

  showIncubator: function(show) {
    if (show == undefined) {
      show = true;
    }
    this.showField('NextTaskTargetID', show);
    this.showField('NextStartDate', show);
    this.showField('next_task_splitter', show);
    this.showField('_select_next_target', show);
  },

  selectNextTarget: function() {
    var self = this;

    this.customGrid({
      method: 'renderSelectRecordDialog',
      parameters: {
        taskid: this.parameter('id'),
        projectid: this.fieldValue('ProjectID'),
        targetid: this.fieldValue('TaskTargetID'),
        nexttargetid: this.fieldValue('NextTaskTargetID')
      },
      onSelect: function(record_id) {
        self.fieldValue('NextTaskTargetID', record_id);
      }
    });
  },

  addFromCalendar: function(params) {
    //var formatDateStr = function(date) {
      //return moment.utc(date).format('DD.MM.YYYY HH:mm');
    //};

    this.parameter('id', params.id);
    //this.fieldValue('StartDate', formatDateStr(params.start));
    //this.fieldValue('FinishDate', formatDateStr(params.end));
  }

});
