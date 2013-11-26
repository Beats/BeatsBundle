/**
 * Beats Field DateTimePicker
 */
(function ($) {

  function _zero_pad(value) {
    return value < 10 ? '0' + value : value
  }

  var _ = {
    /**
     * @param Beats.Field.DatePicker
     */
    gather: function (self) {
      var parts = {}
      self.element.find('select').each(function () {
        var $el = $(this)
        parts[$el.data('field')] = $el.val()
      })
      return parts
    }
  }

  /******************************************************************************************************************/

  Beats.Field('Beats.Field.DatePicker', {

    defaults: {
      year: {
        min: -20,
        max: Date.now().getUTCFullYear()
      },
      empty: {
        y: { val: 0, lbl: 'YYYY'},
        m: { val: 0, lbl: 'MM'  },
        d: { val: 0, lbl: 'DD'  }
      },
      view: 'beats.field.datepicker.ejs',
      tplV: {
        value: {
          y: null,
          m: null,
          d: null
        },
        empty: {
          y: null,
          m: null,
          d: null
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
          hidden: ''
        }
      }
    }

  }, {

    init: function (el, opts) {
      this._super.apply(this, arguments)
      var self = this

      self.options.tplV.empty = self.options.empty

      if (self.options.year.min < 0) {
        self.options.year.min = self.options.year.max + self.options.year.min;
      }
      var year = self.options.year.max;
      do {
        self.options.tplV.parts.y.push({val: year, lbl: year})
      } while (--year > self.options.year.min)

      if (Beats.empty(self.options.value)) {
        if (self.options.empty) {
          self.options.value = ''
        } else {
          self.options.value = Date.now().toISODate(true)
        }
      }

      self.options.tplV.hidden = self.options.value
      self.options.tplV.value = Beats.Date.iso2partsDate(self.options.value)

      self.element.html(self.options.view, self.options.tplV, Beats.view({
        options: function (opts, val, empty) {
          var parts = []
          if (empty) {
            parts.push('<option value="', empty.val, '"', ' class="empty"')
            if (empty.val == val) {
              parts.push('selected="selected"')
            }
            parts.push('>', empty.lbl, '</option>', '\n')
          }
          for (var i in opts) {
            var opt = opts[i]
            parts.push('<option value="', opt.val, '"')
            if (opt.val == val) {
              parts.push('selected="selected"')
            }
            parts.push('>', opt.lbl, '</option>', '\n')
          }
          return parts.join('');
        }
      }), function () {
        var $fld = self.element.find('.beats_hidden_field')
        if (self.options.reditor) {
          $fld.beats_reditor(self.options.reditor)
        }
        self.element.find('select').each(function (idx, fld) {
          var $select = $(fld)
          $select.bind('focus', function () {
            $fld.trigger('init')
          })
          $select.bind('click', function () {
            $fld.trigger('init')
          })
          $select.bind('blur', function () {
            $fld.trigger('done')
          })
        })
      })

    },

    getValue: function () {
      var self = this
        , $fld = self.element.find('.beats_hidden_field')
      return $fld.val()
    },

    setValue: function (newValue) {
      var self = this
        , $fld = self.element.find('.beats_hidden_field')
        , oldValue = $fld.val()
      if (oldValue !== newValue) {
        $fld.val(newValue)
        $fld.trigger(jQuery.Event('change', {
          newValue: newValue,
          oldValue: oldValue
        }), [newValue, oldValue])
      }
      return self
    },

    'select change': function ($el, evt) {
      var self = this
        , $fld = self.element.find('.beats_hidden_field')
        , value = ''
        , empty = self.element.find('select option.empty:selected')

      if (empty.length) {
        if (empty.length != self.element.find('select').length) {
          $fld.val(null)
          self.element.trigger(jQuery.Event('beats.field.failure', {
            value: value
          }), ['Date incomplete'])
        } else {
          self.setValue(value)
          self.element.trigger(jQuery.Event('beats.field.success', {
            value: value
          }), [value])
        }
      } else {
        var parts = _.gather(self)
          , date = Beats.Date.parts2date(parts)
        if (date.getMonth() != parts.m - 1) {
          $fld.val(null)
          self.element.trigger(jQuery.Event('beats.field.failure', {
            value: value
          }), ['<i class="icon-exclamation"></i> Invalid date specified'])
        } else {
          value = Beats.Date.parts2isoDate(parts)
          self.setValue(value)
          self.element.trigger(jQuery.Event('beats.field.success', {
            value: value
          }), [value])
        }
      }
    },

    'beats.field.failure': function ($el, evt, error) {
      var self = this
        , $group = self.element.find('.control-group')
        , $help = $group.find('.help-inline')
      $group.addClass('error').removeClass('success').removeClass('info')
      $help.html(error).show()
    },

    'beats.field.success': function ($el, evt, value) {
      var self = this
        , $group = self.element.find('.control-group')
        , $help = $group.find('.help-inline')
      $group.removeClass('error')
      $help.empty().hide()
    }

  })

  /******************************************************************************************************************/

  return Beats.Field.DatePicker;

})(jQuery)
