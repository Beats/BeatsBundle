/**
 * Beats Field Number
 */
(function ($) {

  Beats.Field.Number = Beats.Field.extend({
    pluginName: 'beats_field_number',
    defaults: {
      keyCodes: [0, 8, 9, 13, 16, 33, 34, 35, 36, 37, 38, 39, 40, 46],
      min: null,
      max: null,
      minError: "The value must be greater than %d",
      maxError: "The value must be lower than %d",
      hasMin: false,
      hasMax: false
    }
  }, {

    init: function () {
      var self = this
      self._super.apply(self, arguments)
      self.element.on({
        'keypress': function (evt) {
          if (evt.charCode && (evt.charCode < 48 || 57 < evt.charCode )) {
            evt.preventDefault()
          }
          switch (evt.keyCode) {
            case 40: // down
            case 37: // left
              self.element.val((self.element.val() | 0) - (evt.ctrlKey ? 10 : 1))
              break;
            case 38: // up
            case 39: // right
              self.element.val((self.element.val() | 0) + (evt.ctrlKey ? 10 : 1))
              break;
          }
          self._update()
        }

      })

      self.options.hasMin = $.isNumeric(self.options.min)
      self.options.hasMax = $.isNumeric(self.options.max)

      if (self.options.hasMin || self.options.hasMax || $.isFunction(self.options.validator)) {
        self.element.change(function () {
          self._update()
        })
      }
    },

    _validate: function (value) {
      var self = this
      if (self.options.hasMin && value < self.options.min) {
        return $.Deferred().rejectWith(self, [
          Beats.empty(self.options.minError) || self.options.minError.replace('%d', self.options.min)
        ])
      }
      if (self.options.hasMax && self.options.max < value) {
        return $.Deferred().rejectWith(self, [
          Beats.empty(self.options.maxError) || self.options.maxError.replace('%d', self.options.max)
        ])
      }
      return self._super.apply(self, arguments)
    }

  })

  /******************************************************************************************************************/

  return Beats.Field.Number;

})(jQuery)
