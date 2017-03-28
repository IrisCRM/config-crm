/**
 * Скрипт карточки заказа
 */

irisControllers.classes.c_Project = IrisCardController.extend({

  events: {
    'lookup:changed #AccountID': 'onChangeAccountID',
    'lookup:changed #ContactID': 'onChangeContactID',
    'lookup:changed #ObjectID': 'onChangeObjectID',
    'change #ProjectStageID': 'onChangeEvent',
    'change #ProjectStateID': 'onChangeProjectStateID',
    'keyup #Income, #Expense': 'onChangeAmounts',
    'keyup #PlanIncome, #PlanExpense': 'onChangePlanAmounts',
    'change #RemindDate': 'onChangeRemindDate',
    'change #IsRemind': 'onChangeIsRemind'
  },

  /**
   * Названия проекта - обновление
   */
  updateName: function() {
    var l_tire = (this.fieldDisplayValue('AccountID') == '') 
        || (this.fieldValue('Number') == '') ? '' : ' - ';
    this.fieldValue('Name', this.fieldValue('Number') + l_tire + 
        this.fieldDisplayValue('AccountID'));
  },

  onChangeAccountID: function(event) {
    this.updateName(); 
    
    // При выборе компании заполним контакт из основного контакта компани
    // И установим фильтр на контакты - отображать только контакты компании
    var account_id = this.fieldValue('AccountID');
    if (account_id == '') {
      this.getField('ContactID').removeAttr('filter_where');
    }
    else {
      this.getField('ContactID').attr('filter_where', 
          "T0.accountid = '" + account_id + "'");
      this.onChangeEvent(event, {
        disableEvents: true,
        rewriteValues: false,
        letClearValues: false
      });
    }
  },

  onChangeContactID: function(event) {
    var self = this;
    this.onChangeEvent(event, {
      disableEvents: true,
      rewriteValues: false,
      letClearValues: false,
      onApply: function() {
        self.updateName(); 
      }
    });
  },

  onChangeObjectID: function(event) {
    var self = this;
    this.onChangeEvent(event, {
      disableEvents: true,
      rewriteValues: false,
      letClearValues: false,
      onApply: function() {
        self.updateName(); 
      }
    });
  },

  onChangeProjectStateID: function(event) {
    this.onChangeEvent(event, {disableEvents: true});
  },

  /**
   * Расчитать прибыль = Доход - Расходы
   */
  onChangeAmounts: function() {
    // Если полей нет на карточке, то ничего не рассчитываем и выйдем
    try {
      var l_income = parseFloat(this.fieldValue('Income'));
      var l_expense = parseFloat(this.fieldValue('Expense'));
    }
    catch (e) {
      return;
    };
    
    if (isNaN(l_income)) {
      l_income = 0;
    } 

    if (isNaN(l_expense)) {
      l_expense = 0;
    } 
    
    var l_profit = parseFloat(l_income - l_expense);

    if (isNaN(l_profit)) {
      l_profit = 0;
    } 
    
    this.fieldValue('Profit', l_profit.toFixed(2));
  },

  onChangePlanAmounts: function() {
    // Расчитать прпланируемую прибыль = план Доход - план Расходы
    try {
      var l_income = parseFloat(this.fieldValue('PlanIncome'));
      var l_expense = parseFloat(this.fieldValue('PlanExpense'));
    }
    catch (e) {
      return;
    };
    
    if (isNaN(l_income)) {
      l_income = 0;
    } 

    if (isNaN(l_expense)) {
      l_expense = 0;
    } 
    
    var l_profit = parseFloat(l_income - l_expense);

    if (isNaN(l_profit)) {
      l_profit = 0;
    } 
    this.fieldValue('PlanProfit', l_profit.toFixed(2));
  },

  onChangeIsRemind: function() {
    c_Common_IsRemind_OnChange($(this.el.id).down('form'), 
        Array('StartDate', 'PlanStartDate'), 60*24 - 60*10);
  },

  onChangeRemindDate: function() {
    c_Common_RemindDate_OnChange($(this.el.id).down('form'));
  },

  onOpen: function () {
    var p_wnd_id = this.el.id;
    var l_form = $(this.el.id).down('form');

    // Дизаблим
    l_form.Number.setAttribute('disabled', 'disabled');

    // На карточке "Мои заказы" этих полей нет
    try {
      l_form.Profit.setAttribute('disabled', 'disabled');
      l_form.PlanProfit.setAttribute('disabled', 'disabled');
    } 
    catch (e) {};

    if (g_session_values['userrolecode'] == 'Client') {
      try {
        l_form.ProjectStateID.setAttribute('disabled', 'true');
        l_form.ProjectStageID.setAttribute('disabled', 'true');
        l_form.CurrencyID.setAttribute('disabled', 'true');
        l_form.StartDate.setAttribute('disabled', 'true');
        l_form.FinishDate.setAttribute('disabled', 'true');
        l_form.ContactID.setAttribute('disabled', 'true');
        l_form.ContactID_btn.setAttribute('disabled', 'true'); 
        l_form.AccountID.setAttribute('disabled', 'true');
        l_form.AccountID_btn.setAttribute('disabled', 'true'); 
        l_form.PlanIncome.setAttribute('disabled', 'true');
        l_form.Name.setAttribute('disabled', 'true');
        l_form.OwnerID.setAttribute('disabled', 'true');
        l_form.OwnerID_btn.setAttribute('disabled', 'true');
      }
      catch (e) {}; 
    }
    
    // Если редактируем запись
    if (l_form._mode.value == 'update') {
      // Задизаблим поля, которые вычисляются автоматически
      try {
        l_form.PlanIncome.setAttribute('disabled', 'true');
        l_form.PlanExpense.setAttribute('disabled', 'true');
        l_form.Expense.setAttribute('disabled', 'true');
        l_form.Income.setAttribute('disabled', 'true');
      } 
      catch (e) {};

      if ('Client' != g_session_values['userrolecode']) {
        // Раздизаблим поля с суммами, которые можно менять 
        // (которые не расчитаны автоматически)
        this.request({
          method: 'getEnabledFields', 
          parameters: {
            id: this.parameter('id')
          },
          onSuccess: function (transport) {
            try {
              var l_res = transport.responseText.evalJSON();
              res = l_res.data;

              //считаем ответ
              var PlanIncome = res.ProjectEnabled.PlanIncome;
              var Income = res.ProjectEnabled.Income;
              var Expense = res.ProjectEnabled.Expense;

              // Раздизаблим
              if (PlanIncome) {
                l_form.PlanIncome.removeAttribute('disabled');
                l_form.PlanExpense.removeAttribute('disabled');
              }
              if (Income) {
                l_form.Income.removeAttribute('disabled');
              }
              if (Expense) {
                l_form.Expense.removeAttribute('disabled');
              }
            } 
            catch (e) {}
          }
        });

        if (l_form._source_name.value != 'Myproject') {

          addCardHeaderButton(this.el.id, 'top', [{
            name: T.t('Создать') + '&hellip;', 
            buttons: [
              {
                name: T.t('Коммерческое предложение'), 
                onclick: "if (common_cardIsSaved('" + this.el.id + "')) { " +
                    "common_createProjectOffer('card', '" + this.el.id + "'); }"
              },
              {
                name: T.t('Договор'), 
                onclick: "if (common_cardIsSaved('" + this.el.id + "')) { " + 
                    "common_createProjectPact('card', '"+ this.el.id + "'); }"
              },
              {
                name: T.t('Счет'), 
                onclick: "if (common_cardIsSaved('" + this.el.id + "')) { " + 
                    "common_createProjectInvoice('card', '"+ this.el.id + "'); }"
              }
            ]
          }]);

          printform_createCardHeaderButton(p_wnd_id, 'top', T.t('Печать') + '&hellip;');
          
          addCardHeaderButton(p_wnd_id, 'top', 'Диаграмма работ', 
              'irisControllers.objects.c_Project' + p_wnd_id + 
              '.showWorkDiagramm()', 
              'Просмотр диаграммы Ганта для работ');
        }
      }

      // miv 07.09.2010: сохраняем текущую стадию заказа, чтобы уведимить клиента, ЕСЛИ она изменилась
      l_form.setAttribute('project_stage', c_Common_GetElementValue(l_form.ProjectStageID));
    }

    bind_select_element(l_form.ProjectTypeID, l_form.ProjectStageID, 'ProjectTypeID');  
  },


  showWorkDiagramm: function() {
    var p_wnd_id = this.el.id;
    var form = $(p_wnd_id).getElementsByTagName("form")[0];
    var gantt_wnd_id = "wnd"+(Math.random()+"").slice(3);
    var wnd = prepareCardWindow(gantt_wnd_id, 'Диаграмма работ', 700, 500);

    var gridscale = 20; // масштаб. 1 день = gridscale пикселей
    var self = this;

    Transport.request({
        section: "Work",
        'class': "s_Work",
        method: 'getWorkDiagramData',
        parameters: {
            projectId: this.parameter('id')
        },
        skipErrors: ['class_not_found', 'file_not_found'],
        onSuccess: function(transport) {
            var result = transport.responseText.evalJSON();

            if (!result.data) {
                return;
            }

            if ((result.data.works == null) || (result.data.project.planstartdate == null) || (result.data.project.days == null)) {
                wnd_alert('Чтобы построить диаграмму, убедитесь, что у заказа указаны планируемое начало и завершение и есть хотя бы одна работа у которой указаны планируемые сроки');
                Windows.close(gantt_wnd_id);
            }

            var container_html = '';
            container_html += '<div class="gantt-area">';
            container_html += '<div class="gantt-left">';
            container_html += '<div class="gantt-left-header">';
            container_html += '</div>';
            container_html += '<div class="gantt-left-data">';
            container_html += '</div>';
            container_html += '</div>';
            container_html += '<div class="gantt-right">';
            container_html += '<div class="gantt-right-header">';
            container_html += '<div class="gantt-right-header-years">';
            container_html += '</div>';
            container_html += '<br>';
            container_html += '<div class="gantt-right-header-months">';
            container_html += '</div>';
            container_html += '<br>';
            container_html += '<div class="gantt-right-header-days">';
            container_html += '</div>';
            container_html += '</div>';
            container_html += '<div class="gantt-right-data">';
            container_html += '</div>';
            container_html += '</div>';
            container_html += '</div>';

            var workHTML = '';
            var capHTML = '';
            var lastWork = 0;
            for (var i = 0; i < result.data.works.length; i++) {
                workHTML += '<div class="gantt-work-outer" style="margin-left: '+result.data.works[i].startday*gridscale+'px; width: '+result.data.works[i].days*gridscale+'px"><div class="gantt-work-inner">'+result.data.works[i].number+'</div></div>';
                capHTML += '<div>'+result.data.works[i].number + ' ' + result.data.works[i].name+'</div>';
                if (lastWork <(result.data.works[i].startday + result.data.works[i].days))
                    lastWork = result.data.works[i].startday + result.data.works[i].days; // количество дней, через которое закончится последняя работа
            }
            if (result.data.project.days < lastWork)
                result.data.project.days = lastWork; // длительность проекта в днях = максимальному из его конечной даты и датой окончания самой последней работы

            wnd.setHTMLContent(container_html);
            $(gantt_wnd_id).down('div.gantt-left-data').update(capHTML);
            $(gantt_wnd_id).down('div.gantt-right-data').update(workHTML);
            var timeline = self.getTimeLine(result.data.project.planstartdate, result.data.project.days, gridscale);
            var dummy = 0;
            self.renderTimeLine($(gantt_wnd_id).down('div.gantt-area'), timeline, gridscale);
        }
    });
  },

  getTimeLine: function(p_startdate, p_days, p_scale) {
    //var date_arr = p_startdate.split('.');
    //var startDate = new Date(date_arr[0], date_arr[2], date_arr[1]);
    var startDate = new Date(Date.parse(p_startdate));

    //var timeline = new Array();
    var timeline = {days: new Array(), months: new Array(), years: new Array()};
    var di = 0, is_newmonth = 1, mi = 0, yi = 0;
    var olddate = 0, newdate = 0, currentDays = 0;
    var oldyear = startDate.getFullYear(), newyear = 0, currentYearDays = 0;

    olddate = startDate.getDate();
    for (var i = 0 ; i <= p_days; i++) {
      timeline.days[di++] = {date: startDate.getDate(), day: startDate.getDay()}; // вносим день

      timeline.months[mi] = {num: startDate.getMonth(), days: (currentDays + 1)}; // вносим месяц
      currentDays = timeline.months[mi].days;

      timeline.years[yi] = {num: startDate.getFullYear(), days: (currentYearDays + 1)}; // вносим год
      currentYearDays = timeline.years[yi].days;

      olddate = startDate.getDate();
      oldyear = startDate.getFullYear();
      startDate.setDate(olddate+1); // продвинемся на 1 день вперед
      newdate = startDate.getDate();
      newyear = startDate.getFullYear();

      if (olddate > newdate) {
        mi++; // сменился месяц
        currentDays = 0;
      }

      if (oldyear < newyear) {
        yi++; // сменился год
        currentYearDays = 0;
      }

    }
    return timeline;
  },


  renderTimeLine: function(p_gantt, p_timeline, p_gridscale) {
    var header = p_gantt.down('div.gantt-right-header');
    header.setStyle({width: (p_timeline.days.length*p_gridscale)+ 'px'});
    var monthCaps = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
    var isFreeDay = '';

    // нарисуем года
    var yearHTML = '';
    for (var i = 0; i < p_timeline.years.length; i++) {
      yearHTML += '<div style="width: '+(p_timeline.years[i].days*p_gridscale)+'px">' + p_timeline.years[i].num + '</div>';
    }
    header.down('div.gantt-right-header-years').update(yearHTML);


    // нарисуем месяцы
    var monthHTML = '';
    for (var i = 0; i < p_timeline.months.length; i++) {
      monthHTML += '<div style="width: '+(p_timeline.months[i].days*p_gridscale)+'px">' + monthCaps[p_timeline.months[i].num] + '</div>';
    }
    header.down('div.gantt-right-header-months').update(monthHTML);

    // нарисуем дни
    var dayHTML = '';
    for (var i = 0; i < p_timeline.days.length; i++) {
      isFreeDay = ((p_timeline.days[i].day == 0) || (p_timeline.days[i].day == 6)) ? 'class="free"' : '';
      dayHTML += '<div ' + isFreeDay + 'style="width: ' + p_gridscale + 'px">' + ((p_gridscale >= 20) ? p_timeline.days[i].date : '&nbsp;') + '</div>';
    }
    header.down('div.gantt-right-header-days').update(dayHTML);
  }

});
