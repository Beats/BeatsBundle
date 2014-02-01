/**
 * Beats Field Hronos Date
 */
(function ($) {

  /******************************************************************************************************************/

  Beats.Field.Hronos.Date = Beats.Field.Hronos.extend({
    pluginName: 'beats_field_date',

    defaults: {
      yearsUpper: null,
      yearsLower: null,
      incomplete: 'The date is incomplete',
      view: 'beats.can.field.hronos.date.ejs'
    }

  }, {

    _iso2structure: function (iso) {
      return this.constructor.isoDate2structure(iso)
    },

    _structure2iso: function (structure) {
      return this.constructor.structure2isoDate(structure)
    },

    _isInvalid: function (value, structure) {
      var date = Date.fromISO(value)
      return (date.getMonth() != structure.m - 1)
    },

    _beforeRender: function () {
      var self = this
      self._super.apply(self, arguments)
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

    _defaulter: function (value) {
      var self = this
      if (Beats.empty(value) && !self.isClearable()) {
        value = Date.now().toISODate()
      }
      return value
    }

  })

  /******************************************************************************************************************/

  return Beats.Field.Hronos.Date

})(jQuery)
