/**
 * Beats Field DateTime
 */
(function ($) {

  /******************************************************************************************************************/

  var __ = {
    pad: function (value) {
      return (value < 10 ? '0' : '') + value
    },
    setup: function (self) {
      switch (self.options.type) {
        case 'datetime':
          this.setupBoth(self);
          break;
        case 'date':
          this.setupDate(self);
          break;
        case 'time':
          this.setupTime(self);
          break;
        default:
          throw new Beats.Error(self, 'Invalid datetime type: ' + self.options.type)
      }
    },
    setupBoth: function (self) {
      if (Beats.empty(self.options.fields)) {
        self.options.fields = [
          ['m', 'd', 'y'],
          ['H', 'i', 'p']
        ]
      }
      if (Beats.empty(self.options.clearButton)) {
        self.options.clearButton = _('beats.engine.can.field.datetime.clear.button')
      }
      $.extend(self, {
        _iso2structure: function (iso) {
          return this.constructor.isoDateTime2structure(iso)
        },
        _structure2iso: function (structure) {
          return this.constructor.structure2isoDateTime(structure)
        },
        _defaulter: function (value) {
          var self = this;
          if (Beats.empty(value) && !self.isClearable()) {
            var date = Date.now();
            date = new Date(self.constructor.roundMinutes(date.getTime(), 5));
            value = date.toISODateTime()
          }
          return value
        },
        _isInvalid: function (value, structure) {
          var date = Date.fromISO(value);
          return (date.getMonth() != structure.m - 1)
        }
      })
    },
    setupDate: function (self) {
      if (Beats.empty(self.options.fields)) {
        self.options.fields = [
          ['m', 'd', 'y']
        ]
      }
      if (Beats.empty(self.options.clearButton)) {
        self.options.clearButton = _('beats.engine.can.field.datetime.clear.button')
      }
      $.extend(self, {
        _iso2structure: function (iso) {
          return this.constructor.isoDate2structure(iso)
        },
        _structure2iso: function (structure) {
          return this.constructor.structure2isoDate(structure)
        },
        _defaulter: function (value) {
          var self = this;
          if (Beats.empty(value) && !self.isClearable()) {
            value = Date.now().toISODate()
          }
          return value
        },
        _isInvalid: function (value, structure) {
          var date = Date.fromISO(value);
          return (date.getMonth() != structure.m - 1)
        }
      })
    },
    setupTime: function (self) {
      if (Beats.empty(self.options.fields)) {
        self.options.fields = [
          ['H', 'i', 'p']
        ]
      }
      if (Beats.empty(self.options.clearButton)) {
        self.options.clearButton = _('beats.engine.can.field.datetime.clear.button')
      }

      $.extend(self, {
        _iso2structure: function (iso) {
          return this.constructor.isoTime2structure(iso)
        },
        _structure2iso: function (structure) {
          return this.constructor.structure2isoTime(structure)
        },
        _defaulter: function (value) {
          var self = this;
          if (Beats.empty(value) && !self.isClearable()) {
            var date = Date.now();
            date = new Date(self.constructor.roundMinutes(date.getTime(), 5));
            value = date.toISOTime()
          }
          return value
        },
        _isInvalid: function (value, structure) {
          return false
        }
      })
    }
  };

  /******************************************************************************************************************/

  Beats.Field.DateTime = Beats.Field.extend({
    pluginName: 'beats_field_datetime',
    defaults: {
      type: 'datetime',
      incomplete: _('beats.engine.can.field.datetime.incomplete'),
      invalid: _('beats.engine.can.field.datetime.invalid'),
      selectpicker: null,
      yearsUpper: null,
      yearsLower: null,
      fields: null,
      clearButton: _('beats.engine.can.field.datetime.clear.button'),
      clear: {
        y: _('beats.engine.can.field.datetime.clear.y'),
        m: _('beats.engine.can.field.datetime.clear.m'),
        d: _('beats.engine.can.field.datetime.clear.d'),
        h: _('beats.engine.can.field.datetime.clear.h'),
        i: _('beats.engine.can.field.datetime.clear.i'),
        s: _('beats.engine.can.field.datetime.clear.s'),
        H: _('beats.engine.can.field.datetime.clear.H'),
        p: _('beats.engine.can.field.datetime.clear.p')
      },
      view: 'beats.can.field.datetime.ejs',
      tplV: {
        fields: null,
        label: null,
        alert: null,
        clear: null,
        clearButton: null,
        value: {
          y: null,
          m: null,
          d: null,
          h: null,
          i: null,
          s: null,
          H: null,
          p: null
        },
        parts: {
          y: [],
          m: [
            {val: '01', lbl: _('beats.engine.can.field.datetime.month.M.01')},
            {val: '02', lbl: _('beats.engine.can.field.datetime.month.M.02')},
            {val: '03', lbl: _('beats.engine.can.field.datetime.month.M.03')},
            {val: '04', lbl: _('beats.engine.can.field.datetime.month.M.04')},
            {val: '05', lbl: _('beats.engine.can.field.datetime.month.M.05')},
            {val: '06', lbl: _('beats.engine.can.field.datetime.month.M.06')},
            {val: '07', lbl: _('beats.engine.can.field.datetime.month.M.07')},
            {val: '08', lbl: _('beats.engine.can.field.datetime.month.M.08')},
            {val: '09', lbl: _('beats.engine.can.field.datetime.month.M.09')},
            {val: '10', lbl: _('beats.engine.can.field.datetime.month.M.10')},
            {val: '11', lbl: _('beats.engine.can.field.datetime.month.M.11')},
            {val: '12', lbl: _('beats.engine.can.field.datetime.month.M.12')}
          ],
          d: [
            {val: '01', lbl: '1'},
            {val: '02', lbl: '2'},
            {val: '03', lbl: '3'},
            {val: '04', lbl: '4'},
            {val: '05', lbl: '5'},
            {val: '06', lbl: '6'},
            {val: '07', lbl: '7'},
            {val: '08', lbl: '8'},
            {val: '09', lbl: '9'},
            {val: '10', lbl: '10'},
            {val: '11', lbl: '11'},
            {val: '12', lbl: '12'},
            {val: '13', lbl: '13'},
            {val: '14', lbl: '14'},
            {val: '15', lbl: '15'},
            {val: '16', lbl: '16'},
            {val: '17', lbl: '17'},
            {val: '18', lbl: '18'},
            {val: '19', lbl: '19'},
            {val: '20', lbl: '20'},
            {val: '21', lbl: '21'},
            {val: '22', lbl: '22'},
            {val: '23', lbl: '23'},
            {val: '24', lbl: '24'},
            {val: '25', lbl: '25'},
            {val: '26', lbl: '26'},
            {val: '27', lbl: '27'},
            {val: '28', lbl: '28'},
            {val: '29', lbl: '29'},
            {val: '30', lbl: '30'},
            {val: '31', lbl: '31'}
          ],
          h: [
            {val: '00', lbl: '00'},
            {val: '01', lbl: '01'},
            {val: '02', lbl: '02'},
            {val: '03', lbl: '03'},
            {val: '04', lbl: '04'},
            {val: '05', lbl: '05'},
            {val: '06', lbl: '06'},
            {val: '07', lbl: '07'},
            {val: '08', lbl: '08'},
            {val: '09', lbl: '09'},
            {val: '10', lbl: '10'},
            {val: '11', lbl: '11'},
            {val: '12', lbl: '12'},
            {val: '13', lbl: '13'},
            {val: '14', lbl: '14'},
            {val: '15', lbl: '15'},
            {val: '16', lbl: '16'},
            {val: '17', lbl: '17'},
            {val: '18', lbl: '18'},
            {val: '19', lbl: '19'},
            {val: '20', lbl: '20'},
            {val: '21', lbl: '21'},
            {val: '22', lbl: '22'},
            {val: '23', lbl: '23'}
          ],
          i: [
            {val: '00', lbl: '00'},
            {val: '05', lbl: '05'},
            {val: '10', lbl: '10'},
            {val: '15', lbl: '15'},
            {val: '20', lbl: '20'},
            {val: '25', lbl: '25'},
            {val: '30', lbl: '30'},
            {val: '35', lbl: '35'},
            {val: '40', lbl: '40'},
            {val: '45', lbl: '45'},
            {val: '50', lbl: '50'},
            {val: '55', lbl: '55'}
          ],
          s: [
            {val: '00', lbl: '00'},
            {val: '05', lbl: '05'},
            {val: '10', lbl: '10'},
            {val: '15', lbl: '15'},
            {val: '20', lbl: '20'},
            {val: '25', lbl: '25'},
            {val: '30', lbl: '30'},
            {val: '35', lbl: '35'},
            {val: '40', lbl: '40'},
            {val: '45', lbl: '45'},
            {val: '50', lbl: '50'},
            {val: '55', lbl: '55'}
          ],
          H: [
            {val: '01', lbl: '1'},
            {val: '02', lbl: '2'},
            {val: '03', lbl: '3'},
            {val: '04', lbl: '4'},
            {val: '05', lbl: '5'},
            {val: '06', lbl: '6'},
            {val: '07', lbl: '7'},
            {val: '08', lbl: '8'},
            {val: '09', lbl: '9'},
            {val: '10', lbl: '10'},
            {val: '11', lbl: '11'},
            {val: '12', lbl: '12'}
          ],
          p: [
            {val: '0', lbl: 'AM'},
            {val: '1', lbl: 'PM'}
          ]
        }
      }
    },

    isoDate2structure: function (value) {
      if (Beats.empty(value)) {
        return null
      }
      var date = value.split('-');
      return {
        y: date[0],
        m: date[1],
        d: date[2]
      };
    },
    isoTime2structure: function (value) {
      if (Beats.empty(value)) {
        return null
      }
      var time = (value).split(':')
        , hour = time[0] % 12;
      return {
        h: time[0],
        i: time[1],
        s: time[2],
        H: hour ? (hour < 10 ? '0' : '') + hour : '12',
        p: (time[0] < 12) ? 0 : 1
      }
    },
    isoDateTime2structure: function (value) {
      if (Beats.empty(value)) {
        return null
      }
      var part = value.split(' ');
      return $.extend({}, this.isoDate2structure(part[0]), this.isoTime2structure(part[1]))
    },

    structure2isoDate: function (value) {
      if (jQuery.count(value) < 3) {
        return null
      }
      return [value.y, __.pad(value.m), __.pad(value.d)].join('-')
    },
    structure2isoTime: function (value) {
      if (jQuery.count(value) < 3) {
        return null
      }
      if (value.H !== undefined && value.p !== undefined) {
        value.h = (value.H | 0) + (value.p ? 12 : 0)
      }
      if (value.s === undefined) {
        value.s = 0
      }
      return [__.pad(value.h), __.pad(value.i), __.pad(value.s)].join(':')
    },
    structure2isoDateTime: function (value) {
      if (jQuery.count(value) < 6) {
        return null
      }
      return [this.structure2isoDate(value), this.structure2isoTime(value)].join(' ')
    },

    roundMinutes: function (time, mins) {
      var mils = mins * 60000;
      // Rounding the time to the nearest mins
      return Math.floor((time + (mils / 2)) / mils) * mils
    }

  }, {

    init: function () {
      var self = this;
      __.setup(self);
      self._super.apply(self, arguments)
    },

    _beforeRender: function () {
      var self = this
        , year;
      self._super.apply(self, arguments);

      self.options.tplV.fields = self.options.fields;
      self.options.tplV.clear = self.options.clear;
      self.options.tplV.clearButton = self.options.clearButton;

      if (!self.options.yearsUpper) {
        self.options.yearsUpper = Date.now().getFullYear()
      }
      if (!self.options.yearsLower) {
        self.options.yearsLower = self.options.yearsUpper + 2
      }
      if (self.options.yearsUpper < self.options.yearsLower) {
        for (year = self.options.yearsUpper; year <= self.options.yearsLower; year++) {
          self.options.tplV.parts.y.push({val: year, lbl: year})
        }
      } else {
        for (year = self.options.yearsUpper; self.options.yearsLower <= year; year--) {
          self.options.tplV.parts.y.push({val: year, lbl: year})
        }
      }
    },

    _afterRender: function () {
      var self = this;

      if (self.options.selectpicker) {
        self.$selects().selectpicker(self.options.selectpicker)
      }

      self.structure(self._iso2structure(self.options.preset));
      self._super.apply(self, arguments);

      self.$selects().on('change', function (evt) {
        evt.stopPropagation();
        self._update()
      });
      self.$clear().on('click', function (evt) {
        evt.stopPropagation();
        self.structure(self._iso2structure());
        self._update()
      })

    },

    $selects: function () {
      return this.$group().find('select')
    },

    $clear: function () {
      return this.$group().find('button[aria-hidden]')
    },

    isClearable: function () {
      return !Beats.empty(this.options.clear)
    },

    structure: function (structure) {
      var self = this;
      if (structure === undefined) {
        structure = {};
        self.$selects().each(function () {
          var $el = $(this)
            , val = $el.val();
          if ($.isNumeric(val)) {
            structure[$el.data('fld')] = val | 0
          }
        });
        if (structure.H) {

        }
        return structure
      } else {
        var fn;
        if (Beats.empty(structure)) {
          fn = function () {
            var $el = $(this)
              , key = $el.data('fld')
              , val = '';
            if ($el.data('selectpicker')) {
              $el.selectpicker('val', val)
            } else {
              $el.val(val)
            }
          }
        } else {
          fn = function () {
            var $el = $(this)
              , key = $el.data('fld')
              , val = structure[key];
            if ($el.data('selectpicker')) {
              $el.selectpicker('val', val)
            } else {
              $el.val(val);
              if ($el.is(':hidden')) {
                $el.find('option[value="' + val + '"]').attr('selected', 'selected');
              }
            }
          }
        }
        self.$selects().each(fn);
        return structure
      }
    },

    reset: function () {
      var self = this;
      self.structure(self._iso2structure(self.options.preset));
      self._update();
      return self
    },

    _isInvalid: function (value, structure) {
      var date = Date.fromISO(value);
      return (date.getMonth() != structure.m - 1)
    },

    _update: function (initial) {
      var self = this
        , oldValue = self.element.val();
      return self._super.apply(self, arguments)
        .always(function (failure, newValue) {
          self.$clear().toggle(!Beats.empty(newValue));
          if (oldValue !== newValue) {
            self.element.val(newValue).trigger(jQuery.Event('change'), [newValue, !failure])
          }
        })
    },

    _validate: function (oldValue, initial) {
      var self = this
        , structure = self.structure()
        , newValue = self._structure2iso(structure)
        , error = false;
      if (Beats.empty(newValue)) {
        if (!Beats.empty(structure)) {
          error = self.options.incomplete
        }
      } else if (self._isInvalid(newValue, structure)) {
        error = self.options.invalid
      }
      if (error) {
        newValue = '';
        return $.Deferred().rejectWith(self, [error, newValue])
      }
      return self._super.apply(self, [newValue])
    }

  });

  /******************************************************************************************************************/

  return Beats.Field.DateTime

})(jQuery);
