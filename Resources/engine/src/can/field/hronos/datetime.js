/**
 * Beats Field Hronos DateTime
 */
(function ($) {

  /******************************************************************************************************************/

  Beats.Field.Hronos.DateTime = Beats.Field.Hronos.extend({
    pluginName: 'beats_field_datetime',

    defaults: {
      yearsUpper: null,
      yearsLower: null,
      incomplete: 'The date is incomplete',
      view: 'beats.can.field.hronos.datetime.ejs'
    }

  }, {

    _isInvalid: function (value, structure) {
      var date = Date.fromISO(value)
      return (date.getMonth() != structure.m - 1)
    },

    _beforeRender: function () {
      var self = this
      if (!self.options.yearsUpper) {
        self.options.yearsUpper = Date.now().getFullYear()
      }
      if (!self.options.yearsLower) {
        self.options.yearsLower = self.options.yearsUpper + 2
      }

      if (self.options.yearsUpper < self.options.yearsLower) {
        for (var year = self.options.yearsUpper; year <= self.options.yearsLower; year++) {
          self.options.tplV.parts.y.push({val: year, lbl: year})
        }
      } else {
        for (var year = self.options.yearsUpper; self.options.yearsLower <= year; year--) {
          self.options.tplV.parts.y.push({val: year, lbl: year})
        }
      }
    },

    _setupValue: function (value) {
      var self = this
      if (Beats.empty(value) && !self.isClearable()) {
        value = Date.now().toISODate()
      }
      self.options.value = value
      self.structure(self.constructor.isoDate2structure(value))
      return value
    }

  })

  /******************************************************************************************************************/

  return Beats.Field.Hronos.DateTime

})(jQuery)
