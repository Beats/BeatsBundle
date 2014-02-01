/**
 * Beats Field Hronos Date
 */
(function ($) {

  /******************************************************************************************************************/

  Beats.Field.Hronos.Time = Beats.Field.Hronos.extend({
    pluginName: 'beats_field_time',
    defaults: {
      incomplete: 'The time is incomplete',
      view: 'beats.can.field.hronos.time.ejs'
    }
  }, {

    _iso2structure: function (iso) {
      return this.constructor.isoTime2structure(iso)
    },

    _structure2iso: function (structure) {
      return this.constructor.structure2isoTime(structure)
    },

    _isInvalid: function (value, structure) {
      return false
    },

    _defaulter: function (value) {
      var self = this
      if (Beats.empty(value) && !self.isClearable()) {
        var date = Date.now()
        date = new Date(self.constructor.roundMinutes(date.getTime(), 5))
        value = date.toISOTime()
      }
      return value
    }

  })

  /******************************************************************************************************************/

  return Beats.Field.Hronos.Time

})(jQuery)
