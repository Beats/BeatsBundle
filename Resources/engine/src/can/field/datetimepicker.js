/**
 * Beats Field DateTimePicker
 */
(function ($) {

  var _ = {
  }

  /******************************************************************************************************************/

  Beats.Field.DateTimePicker = Beats.Field.extend({

    defaults: {
      min: null,
      max: null
    }

  }, {

    init: function (el, opts) {
      this._super.apply(this, arguments)
      var self = this

    }

  })

  /******************************************************************************************************************/

  return Beats.Field.DateTimePicker;

})(jQuery)
