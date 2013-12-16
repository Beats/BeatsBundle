/**
 * Beats Field
 */
(function ($) {

  var _ = {
  }

  Beats.Field = Beats.Control.extend({

      defaults: {
        id: null,
        name: null,
        label: null,
        value: null,
        error: null,
        reditor: false,
        tplV: {
          id: null,
          name: null,
          label: null,
          value: null,
          error: null
        }
      }

    }, {

      init: function (el, opts) {
        this._super.apply(this, arguments)
        var self = this

        self.options.tplV.id = self.options.id
        self.options.tplV.name = self.options.name
        self.options.tplV.label = self.options.label
        self.options.tplV.value = self.options.value
        self.options.tplV.error = self.options.error
      },

      disable: function () {
        this.element.prop('disabled', true)
      },
      enable: function () {
        this.element.prop('disabled', false)
      }


    }
  )

  return Beats.Field

})(jQuery);
