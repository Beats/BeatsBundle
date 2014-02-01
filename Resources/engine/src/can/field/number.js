/**
 * Beats Field Number
 */
(function ($) {

  Beats.Field.Number = Beats.Field.extend({
    pluginName: 'beats_field_number',
    defaults: {
      keyCodes: [0, 8, 13, 16, 33, 34, 35, 36, 37, 38, 39, 40, 46],
      min: null,
      max: null,
      errorMin: "The value must be greater than %m",
      errorMax: "The value must be lower than %m"
    }
  }, {

    init: function () {
      var self = this
      self._super.apply(self, arguments)
      self.element.on({
        'keypress': function (evt) {
          if (evt.charCode && (evt.charCode < 48 || 57 < evt.charCode )) {
            evt.preventDefault()
          } else {
            switch (evt.keyCode) {
              case 39:
              case 38:
                self.element.val((self.element.val()|0) - 1)
                break;
              case 37:
              case 40:
                self.element.val((self.element.val()|0) + 1)
                break;
              default:
                evt.preventDefault()
            }

          }
        }
      })

      var hasMin = $.isNumeric(self.options.min)
        , hasMax = $.isNumeric(self.options.max)

      if (hasMin || hasMax) {
        if (hasMin && hasMax) {
          function notInRange(value) {
            if (value < self.options.min) {
              return self.options.errorMin.replace('%v', value).replace('%m', self.options.min)
            } else if (self.options.max < value) {
              return self.options.errorMax.replace('%v', value).replace('%m', self.options.max)
            } else {
              return false
            }
          }
        } else if (hasMin) {
          function notInRange(value) {
            if (value < self.options.min) {
              return self.options.errorMin.replace('%v', value).replace('%m', self.options.min)
            } else {
              return false
            }
          }
        } else {
          function notInRange(value) {
            if (self.options.max < value) {
              return self.options.errorMax.replace('%v', value).replace('%m', self.options.max)
            } else {
              return false
            }
          }
        }
        $.extend(self, {
          _validate: function () {
            var self = this
              , error = notInRange(self.element.val())
            if (error) {
              return error
            } else if ($.isFunction(self.options.validator)) {
              return self.options.validator.apply(self, [self.element.val()])
            }
            return false;
          }
        })
        self.element.change(function () {
          self._update()
        })
      }
    }

  })

  /******************************************************************************************************************/

  return Beats.Field.Number;

})(jQuery)
