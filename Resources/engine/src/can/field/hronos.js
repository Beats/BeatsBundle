/**
 * Beats Field Hronos
 */
(function ($) {

  /******************************************************************************************************************/

  var _ = {
    pad: function (value) {
      return (value < 10 ? '0' : '') + value
    }
  }

  /******************************************************************************************************************/

  Beats.Field.Hronos = Beats.Field.extend({
    defaults: {
      selectpicker: null,
      incomplete: null,
      validator: null,
      invalid: 'The date is invalid',
      clear: null,
      tplV: {
        label: null,
        error: null,
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
            { val: '01', lbl: 'Jan'},
            { val: '02', lbl: 'Feb'},
            { val: '03', lbl: 'Mar'},
            { val: '04', lbl: 'Apr'},
            { val: '05', lbl: 'May'},
            { val: '06', lbl: 'Jun'},
            { val: '07', lbl: 'Jul'},
            { val: '08', lbl: 'Aug'},
            { val: '09', lbl: 'Sep'},
            { val: '10', lbl: 'Oct'},
            { val: '11', lbl: 'Nov'},
            { val: '12', lbl: 'Dec'}
          ],
          d: [
            { val: '01', lbl: '1'},
            { val: '02', lbl: '2'},
            { val: '03', lbl: '3'},
            { val: '04', lbl: '4'},
            { val: '05', lbl: '5'},
            { val: '06', lbl: '6'},
            { val: '07', lbl: '7'},
            { val: '08', lbl: '8'},
            { val: '09', lbl: '9'},
            { val: '10', lbl: '10'},
            { val: '11', lbl: '11'},
            { val: '12', lbl: '12'},
            { val: '13', lbl: '13'},
            { val: '14', lbl: '14'},
            { val: '15', lbl: '15'},
            { val: '16', lbl: '16'},
            { val: '17', lbl: '17'},
            { val: '18', lbl: '18'},
            { val: '19', lbl: '19'},
            { val: '20', lbl: '20'},
            { val: '21', lbl: '21'},
            { val: '22', lbl: '22'},
            { val: '23', lbl: '23'},
            { val: '24', lbl: '24'},
            { val: '25', lbl: '25'},
            { val: '26', lbl: '26'},
            { val: '27', lbl: '27'},
            { val: '28', lbl: '28'},
            { val: '29', lbl: '29'},
            { val: '30', lbl: '30'},
            { val: '31', lbl: '31'}
          ],
          h: [
            { val: '00', lbl: '00'},
            { val: '01', lbl: '01'},
            { val: '02', lbl: '02'},
            { val: '03', lbl: '03'},
            { val: '04', lbl: '04'},
            { val: '05', lbl: '05'},
            { val: '06', lbl: '06'},
            { val: '07', lbl: '07'},
            { val: '08', lbl: '08'},
            { val: '09', lbl: '09'},
            { val: '10', lbl: '10'},
            { val: '11', lbl: '11'},
            { val: '12', lbl: '12'},
            { val: '13', lbl: '13'},
            { val: '14', lbl: '14'},
            { val: '15', lbl: '15'},
            { val: '16', lbl: '16'},
            { val: '17', lbl: '17'},
            { val: '18', lbl: '18'},
            { val: '19', lbl: '19'},
            { val: '20', lbl: '20'},
            { val: '21', lbl: '21'},
            { val: '22', lbl: '22'},
            { val: '23', lbl: '23'}
          ],
          i: [
            { val: '00', lbl: '00'},
            { val: '05', lbl: '05'},
            { val: '10', lbl: '10'},
            { val: '15', lbl: '15'},
            { val: '20', lbl: '20'},
            { val: '25', lbl: '25'},
            { val: '30', lbl: '30'},
            { val: '35', lbl: '35'},
            { val: '40', lbl: '40'},
            { val: '45', lbl: '45'},
            { val: '50', lbl: '50'},
            { val: '55', lbl: '55'}
          ],
          s: [
            { val: '00', lbl: '00'},
            { val: '05', lbl: '05'},
            { val: '10', lbl: '10'},
            { val: '15', lbl: '15'},
            { val: '20', lbl: '20'},
            { val: '25', lbl: '25'},
            { val: '30', lbl: '30'},
            { val: '35', lbl: '35'},
            { val: '40', lbl: '40'},
            { val: '45', lbl: '45'},
            { val: '50', lbl: '50'},
            { val: '55', lbl: '55'}
          ],
          H: [
            { val: '01', lbl: '1'},
            { val: '02', lbl: '2'},
            { val: '03', lbl: '3'},
            { val: '04', lbl: '4'},
            { val: '05', lbl: '5'},
            { val: '06', lbl: '6'},
            { val: '07', lbl: '7'},
            { val: '08', lbl: '8'},
            { val: '09', lbl: '9'},
            { val: '10', lbl: '10'},
            { val: '11', lbl: '11'},
            { val: '12', lbl: '12'}
          ],
          p: [
            { val: '0', lbl: 'AM'},
            { val: '1', lbl: 'PM'}
          ]
        },
        clear: {
          y: 'Year',
          m: 'Month',
          d: 'Day',
          h: 'Hours',
          i: 'Minutes',
          s: 'Seconds',
          H: 'Hours',
          p: 'AM/PM'
        }
      }
    },

    isoDate2structure: function (value) {
      if (Beats.empty(value)) {
        return null
      }
      var date = value.split('-')
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
        , hour = time[0] % 12
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
      var part = value.split(' ')
      return $.extend({}, this.isoDate2structure(part[0]), this.isoTime2structure(part[1]))
    },

    structure2isoDate: function (value) {
      if (jQuery.count(value) < 3) {
        return null
      }
      return [value.y, _.pad(value.m), _.pad(value.d)].join('-')
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
      return [_.pad(value.h), _.pad(value.i), _.pad(value.s)].join(':')
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

    _iso2structure: function (iso) {
      return this.constructor.isoDateTime2structure(iso)
    },

    _structure2iso: function (structure) {
      return this.constructor.structure2isoDateTime(structure)
    },

    _setupValue: function (value) {
      var self = this
      self.options.value = value
      self.structure(self._iso2structure(value))
      return value
    },

    _beforeRender: function () {
      var self = this
      self._super.apply(self, arguments)
      self.options.tplV.clear = self.options.clear
    },

    _afterRender: function () {
      var self = this
      self._super.apply(self, arguments)
      if (self.options.selectpicker) {
        self.$selects().selectpicker(self.options.selectpicker)
      }

      self.$selects().on('change', function () {
        var valid = self.validate()
          , value = self.element.val()
        self.element.trigger(jQuery.Event('beats.field.change'), [value, valid])
      })
      self.$clear().on('click', function () {
        self.structure(self._iso2structure(self.options.value = ''))
      })

      self.structure(self._iso2structure(self._setupValue(self.element.val())))
      self.validate()

    },

    $widget: function () {
      return this.element.next()
    },

    $selects: function () {
      return this.$widget().find('select')
    },

    $errors: function () {
      return this.$widget().find('.help-block')
    },

    $clear: function () {
      return this.$widget().find('button[aria-hidden]')
    },

    isClearable: function () {
      return this.$clear().length > 0
    },

    initialize: function () {
      var self = this
        , value = self.element.val()
      if (Beats.empty(value) && !self.isClearable()) {
        value = Date.now().toISODateTime()
      }
      self.options.value = value
      self.structure(self._iso2structure(value))
    },

    structure: function (structure) {
      var self = this
      if (structure === undefined) {
        structure = {
        }
        self.$selects().each(function () {
          var $el = $(this)
            , val = $el.val()
          if ($.isNumeric(val)) {
            structure[$el.data('fld')] = val | 0
          }
        })
        if (structure.H) {

        }
        return structure
      } else {
        var fn
        if (Beats.empty(structure)) {
          fn = function () {
            var $el = $(this)
              , key = $el.data('fld')
              , val = ''
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
              , val = structure[key]
            if ($el.data('selectpicker')) {
              $el.selectpicker('val', val)
            } else {
              $el.val(val)
            }
          }
        }
        self.$selects().each(fn)
        return structure
      }
    },

    reset: function () {
      var self = this
      self.structure(self._iso2structure(self.options.value))
      return self
    },

    _isInvalid: function (value, structure) {
      if (structure.m !== undefined) {
        var date = Date.fromISO(value)
        return (date.getMonth() != structure.m - 1)
      } else {
        return false
      }
    },

    validate: function () {
      var self = this
        , structure = self.structure()
        , value = self._structure2iso(structure)

        , $widget = self.$widget()
        , $errors = self.$errors()
        , error = false

      $widget.removeClass('has-error')
      $errors.empty()
      self.element.val('')

      if (Beats.empty(value)) {
        if (!Beats.empty(structure)) {
          error = self.options.incomplete
        }
        self.$clear().hide()
      } else {
        if (self._isInvalid(value, structure)) {
          error = 'The date is invalid'
        } else if ($.isFunction(self.options.validator)) {
          error = self.options.validator(value, structure)
        }
        self.$clear().toggle(!error)
      }

      if (error) {
        $widget.addClass('has-error')
        $errors.html(error)
        return false
      } else {
        self.element.val(value)
        return true
      }
    },

    isValid: function () {
      return !this.$widget().hasClass('has-error')
    }

  })

  /******************************************************************************************************************/

  return Beats.Field.Date

})(jQuery)
