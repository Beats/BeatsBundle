/**
 * Beats Field Number
 */
(function ($) {

  Beats.Field.Number = Beats.Field.extend({
    defaults: {
      keyCodes: [0, 8, 13, 16, 33, 34, 35, 36, 37, 38, 39, 40, 46]
    }
  }, {

    init: function () {
      var self = this
      self._super.apply(self, arguments)
    },

    'keypress': function ($el, evt) {
      if (evt.charCode && (evt.charCode < 48 || 57 < evt.charCode )) {
        evt.prevtDefault()
      }
    }

  })

  /******************************************************************************************************************/

  return Beats.Field.Number;

})(jQuery)
