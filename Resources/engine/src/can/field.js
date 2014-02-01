/**
 * Beats Field
 */
(function ($) {

  var _ = {
  }

  Beats.Field = Beats.Control.extend({

      defaults: {
        label: null,
        error: null,

        groupClass: null,
        labelClass: null,

        reditor: false,

        view: null,
        tplV: {
          label: null,
          error: null,

          groupClass: null,
          labelClass: null
        }
      }

    }, {

      init: function () {
        var self = this
        self._super.apply(self, arguments)

        if (Beats.empty(self.options.view)) {
          self._afterRender()
        } else {
          self._beforeRender()
          self.element.after(self.options.view, self.options.tplV, function () {
            self._afterRender()
          })
        }
      },

      _beforeRender: function () {
        var self = this
        self.options.tplV.label = self.options.label
        self.options.tplV.error = self.options.error
        self.options.tplV.groupClass = self.options.groupClass
        self.options.tplV.labelClass = self.options.labelClass
      },

      _afterRender: function () {
      },

      toggleDisable: function (disabled) {
        this.element.prop('disabled', true)
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
