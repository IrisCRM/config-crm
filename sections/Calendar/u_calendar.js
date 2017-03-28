/**
 * Скрипт раздела Календарь
 */

irisControllers.classes.u_Calendar = IrisCardController.extend({

  onOpen: function() {
    var CALENDAR_ID = 'calendar';
    var self = this;
    g_Prepare_Custom_Section('<div id="' + CALENDAR_ID + '"></div>');

    this.customFilters({
      section: 'Calendar',
      'class': 'u_Calendar',
      method: 'getFiltersHTML',
      containerId: 'filters_area',
      onDraw: function(filters) {
        self.Calendar.setFilters(filters);
        self.Calendar.init(CALENDAR_ID);
      },
      onChange: function(filters) {
        self.Calendar.setFilters(filters);
        self.Calendar.refresh();
      }
    });
  },

  Calendar: {
    containerId: null,
    calendar: null,
    filters: [], // [Номер, значение][] выбранных фильтров

    init: function(p_containerId) {
      this.containerId = p_containerId;
      this.initCalendar();
      this.initResizeEvent();
    },

    setFilters: function(data) {
      this.filters = data;
    },

    refresh: function() {
      jQuery('#' + this.containerId)
          .fullCalendar('removeEventSources');
      jQuery('#' + this.containerId)
          .fullCalendar('removeEvents');
      jQuery('#' + this.containerId)
          .fullCalendar('addEventSource', this.getEventSource());
      // calendar.fullCalendar('refetchEvents');
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
      data.start = jQuery.fullCalendar.moment.utc(data.start);
      data.end = jQuery.fullCalendar.moment.utc(data.end);

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
      this.calendar = jQuery('#' + this.containerId).fullCalendar({
        lang: this.getCalendarLanguage(),
        height: this.calculateCalendarHeight(),
        header: {
          left: 'prev,next today',
          center: 'title',
          right: 'month,agendaWeek,agendaDay'
        },
        defaultView: 'agendaWeek',
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
              start: event.start.format()
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
              end: event.end.format()
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
                    self1.calendar.fullCalendar('updateEvent', event);
                  }
                }
              });
            }
          });
        },

        // add new
        select: function(start, end) {
          Transport.request({
            section: 'Calendar',
            'class': 'u_Calendar',
            method: 'generateEventId',
            onSuccess: function(transport) {
              var data = transport.responseText.evalJSON().data;
              if (!data.id) {
                self1.calendar.fullCalendar('unselect');
                return;
              }

              openCard({
                source_name: 'Task',
                card_params: Object.toJSON({
                  mode: 'addFromCalendar',
                  id: data.id,
                  start: moment.utc(start).format('DD.MM.YYYY HH:mm'),
                  end: moment.utc(end).format('DD.MM.YYYY HH:mm')
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
                        self1.calendar.fullCalendar('renderEvent', data, false);
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
        self.calendar.fullCalendar('option', 'height', 
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
