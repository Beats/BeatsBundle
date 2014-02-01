/**
 * Beats Field
 */
(function ($) {

  var _ = {
  }

  Beats.Field = Beats.Control.extend({

      defaults: {
        default: null,

        label: null,
        alert: null,

        labelClass: 'control-label',
        alertClass: 'help-block',
        groupClass: 'form-group',

        validator: null,
        success: null,
        failure: null,
        defaulter: null,

        view: null,
        tplV: {
          label: null,
          alert: null,

          groupClass: null,
          labelClass: null,
          alertClass: null
        }
      }

    }, {

      init: function () {
        var self = this
        self._super.apply(self, arguments)

        self.options.default = (
          $.isFunction(self.options.defaulter)
            ? self.options.defaulter
            : self._defaulter
          )
          .apply(self, [self.element.val()])

        if (Beats.empty(self.options.view)) {
          self._afterRender.apply(self, [])
        } else {
          self._beforeRender.apply(self, [])
          self.element.after(self.options.view, self.options.tplV, function () {
            self._afterRender.apply(self, [])
          })
        }
      },

      _beforeRender: function () {
        var self = this

        self.options.tplV.label = self.options.label
        self.options.tplV.alert = self.options.alert

        self.options.tplV.labelClass = self.options.labelClass
        self.options.tplV.alertClass = self.options.alertClass
        self.options.tplV.groupClass = self.options.groupClass
      },

      _afterRender: function () {
        this._update(true)
      },

      _defaulter: function (value) {
        var self = this
        return value
      },

      $group: function () {
        return this.element.next()
      },

      $label: function () {
        return this.$group().find('label')
      },

      $alert: function () {
        return this.$group().find('.' + this.options.alertClass)
      },

      reset: function () {
        var self = this
        self.element.val(self.options.default)
        self._update()
        return self
      },

      _validate: function () {
        var self = this
          , value = self.element.val()
        if ($.isFunction(self.options.validator)) {
          return self.options.validator.apply(self, [value])
        }
        return false;
      },

      _update: function (initial) {
        var self = this
          , failure

        self.$group().removeClass('has-error has-success')
        self.$alert().empty()

        if (failure = self._validate()) {
          self._setFailure(self.options.failure || failure, initial)
          return false
        } else {
          self._setSuccess(self.options.success, initial)
          return true
        }
      },

      _setFailure: function (failure, initial) {
        var self = this
        self.$group().addClass('has-error')
        self.$alert().html(failure)
      },

      _setSuccess: function (success, initial) {
        var self = this
        if (initial) {
          return
        }
        self.$group().addClass('has-success')
        self.$alert().html(success)
      },

      isValid: function () {
        return !this.$group().hasClass('has-error')
      }

    }
  )

  return Beats.Field

})(jQuery);
