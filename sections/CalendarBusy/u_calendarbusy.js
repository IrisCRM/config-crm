/**
 * Скрипт раздела Календарь
 */

irisControllers.classes.u_CalendarBusy = IrisCardController.extend({

  onOpen: function() {
    var CALENDAR_ID = 'calendar';
    var self = this;
    g_Prepare_Custom_Section('<div id="' + CALENDAR_ID + '"></div>');


    this.customFilters({
      section: 'Calendar',
      'class': 'u_Calendar',
      method: 'getBusyFiltersHTML',
      containerId: 'filters_area',
      onDraw: function(filters) {
        self.Calendar.setFilters(filters);
        self.Calendar.controller = self;
        self.Calendar.init(CALENDAR_ID);
      },
      onChange: function(filters) {
        self.Calendar.setFilters(filters);
        self.Calendar.refresh();
      }
    });
  },

  Calendar: {
    controller: null,
    users: null,
    containerId: null,
    calendar: null,
    filters: [], // [Номер, значение][] выбранных фильтров

    init: function(p_containerId) {
      var self = this;
      Transport.request({
        section: 'Calendar',
        'class': 'u_Calendar',
        method: 'getUsers',
        parameters: {
          filters: self.filters,
        },
        onSuccess: function(transport) {
          var data = transport.responseText.evalJSON().data;
          if (!data || data.length == 0) {
            self.controller.notify(T.t('Список сотрудников пуст'));
          }

          self.users = data;

          self.containerId = p_containerId;
          self.initCalendar();
          self.initResizeEvent();

        },
        onFail: function() {
          self.notify(T.t('Не удалось получить список сотрудников'));
        }
      });
    },

    setFilters: function(data) {
      this.filters = data;
    },

    refresh: function() {
      jQuery('#' + this.containerId)
          .fullCalendarBusy('removeEventSources');
      jQuery('#' + this.containerId)
          .fullCalendarBusy('removeEvents');
      jQuery('#' + this.containerId)
          .fullCalendarBusy('addEventSource', this.getEventSource());
      // calendar.fullCalendar('refetchEvents');
    },

    destroy: function() {
      jQuery('#' + this.containerId).fullCalendarBusy('destroy');
    },

    getEventSource: function() {
      // calendar нужен для доступа к filters
      var calendar = this;
      return {
        events: this.eventsFunction(calendar)
      };
    },

    eventsFunction: function(calendar) {
      return function (start, end, timezone, callback) {
        Transport.request({
          section: 'Calendar',
          'class': 'u_Calendar',
          method: 'getEvents',
          parameters: {
            from: start.format(),
            to: end.format(),
            filters: calendar.filters
          },
          onSuccess: function (transport) {
            var data = transport.responseText.evalJSON().data;
            if (!data) {
              return;
            }
            callback(data);
          }
        });
      }
    },

    getCalendarLanguage: function() {
      // TODO: более корректно проверять язык
      return (T.language || {}).name == 'English' ? 'en' : 'ru';
    },

    updateEventWithJSONData: function(event, data) {
      // TODO: найти корректный способ перевода JSON в Event object
      data.start = moment.utc(data.start);
      data.end = moment.utc(data.end);

      for (var prop in data) {
        if (!data.hasOwnProperty(prop)) {
          continue;
        }
        event[prop] = data[prop];
      }

      if (!data.color) {
        delete event.color;
      }
      if (!data.textColor) {
        delete event.textColor;
      }
    },

    initCalendar: function() {
      var self1 = this;
      this.calendar = jQuery('#' + this.containerId).fullCalendarBusy({
        users: self1.users,
        lang: this.getCalendarLanguage(),
        height: this.calculateCalendarHeight(),
        header: {
          left: 'prev,next today',
          center: 'title',
          right: 'month,irisWeek'
        },
        defaultView: 'irisWeek',
        slotEventOverlap: false,
        defaultDate: moment().format('YYYY-MM-DD'),

        editable: true,
        selectable: true,
        selectHelper: true,

        events: this.getEventSource(),

        loading: function(bool) {
          jQuery('#' + self1.containerId).css('opacity', bool ? 0.5 : 1);
        },

        // move
        eventDrop: function(event, delta, revertFunc) {
          Transport.request({
            section: 'Calendar',
            'class': 'u_Calendar',
            method: 'moveEvent',
            parameters: {
              id: event.id,
              //start: moment.utc(event.start).add(8, 'hours').format(),
              start: event.start.format(),
              userid: event.user
            },
            onSuccess: function(transport) {
              var data = transport.responseText.evalJSON().data;
              if (!data.isOk) {
                revertFunc();
              }
            },
            onFail: function() {
              revertFunc();
            }
          });
        },

        // resize
        eventResize: function(event, delta, revertFunc) {
          Transport.request({
            section: 'Calendar',
            'class': 'u_Calendar',
            method: 'resizeEvent',
            parameters: {
              id: event.id,
              end: moment.utc(event.end).format()
              //end: moment.utc(event.end).add(-1, 'second').format()
            },
            onSuccess: function(transport) {
              var data = transport.responseText.evalJSON().data;
              if (!data.isOk) {
                revertFunc();
              }
            },
            onFail: function() {
              revertFunc();
            }
          });
        },

        // edit
        eventClick: function(event, element) {
          openCard({
            source_name: 'Task',
            rec_id: event.id,
            ondestroy: function() {
              Transport.request({
                section: 'Calendar',
                'class': 'u_Calendar',
                method: 'getEventById',
                parameters: {
                  id: event.id
                },
                onSuccess: function(transport) {
                  var data = transport.responseText.evalJSON().data;
                  if (data.id) {
                    self1.updateEventWithJSONData(event, data);
                    self1.calendar.fullCalendarBusy('updateEvent', event);
                  }
                }
              });
            }
          });
        },

        // add new
        select: function(start, end, ev) {
          Transport.request({
            section: 'Calendar',
            'class': 'u_Calendar',
            method: 'generateEventId',
            onSuccess: function(transport) {
              var data = transport.responseText.evalJSON().data;
              if (!data.id) {
                self1.calendar.fullCalendarBusy('unselect');
                return;
              }

              openCard({
                source_name: 'Task',
                card_params: Object.toJSON({
                  mode: 'addFromCalendar',
                  id: data.id,
                  start: moment.utc(start).format('DD.MM.YYYY HH:mm'),
                  end: moment.utc(end).format('DD.MM.YYYY HH:mm'),
                  //start: moment.utc(start).add(8, 'hours').format('DD.MM.YYYY HH:mm'),
                  //end: moment.utc(end).add(-1, 'second').format('DD.MM.YYYY HH:mm'),
                  user: ev.user,
                  username: ev.username
                }),
                ondestroy: function() {

                  Transport.request({
                    section: 'Calendar',
                    'class': 'u_Calendar',
                    method: 'getEventById',
                    parameters: {
                      id: data.id
                    },
                    onSuccess: function(transport) {
                      var data = transport.responseText.evalJSON().data;
                      if ((data || {}).id) {
                        self1.calendar.fullCalendarBusy('renderEvent', data, false);
                      }
                    }
                  });

                }
              });
            }
          });
        }

      });



    },

    // TODO: use stadart core function
    calculateCalendarHeight: function() {
      //return jQuery('#filters_area').height();

      var height = jQuery('body').height();
      height -= jQuery('#menu_panel').outerHeight(true);
      height -= jQuery('.header').outerHeight(true);
      if (jQuery('.h_div').length > 0) {
        height -= jQuery('.h_div').outerHeight(true);
      }
      height -= jQuery('#dock').outerHeight(true);
      // TODO: IE
      if (!jQuery.support.boxModel) {
        height -= 4;
      }

      return height;
    },

    initResizeEvent: function() {
      var self = this;
      // TODO: $ Prototypejs заменить на jQuery
      Event.observe(window, "resize", function() {
        self.calendar.fullCalendarBusy('option', 'height', 
            self.calculateCalendarHeight());
      });

/*
      var self = this;
      jQuery(window).on('resize', function() {
        self.calendar.fullCalendar('option', 'height', 
            self.calculateCalendarHeight());
      });
*/
    }

  }

});
