/**
 * Beats Field
 */
(function ($) {

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

        if (!self.element.is(':input')) {
          throw new Beats.Error(self, 'The field must be a HTML form field')
        }

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
        return value
      },

      $group: function () {
        if (this.element.is('input[type="hidden"]')) {
          return this.element.next()
        }
        return this.element.parents('.' + this.options.groupClass)
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

      _validate: function (value) {
        var self = this
        if (Beats.empty(self.options.validator)) {
          return $.Deferred().resolveWith(self, [false, value])
        } else if ($.isFunction(self.options.validator.promise)) {
          return self.options.validator
        } else if ($.isFunction(self.options.validator)) {
          var dfd = $.Deferred()
            , error = self.options.validator.apply(self, arguments)
          return error ? dfd.rejectWith(self, [error, value]) : dfd.resolveWith(self, [false, value])
        } else {
          return $.when(self.options.validator)
        }
      },

      _update: function (initial) {
        var self = this

        self.$group().removeClass('has-error has-success')
        self.$alert().empty()

        return self._validate(self.element.val())
          .done(function () {
            self._setSuccess(self.options.success, initial)
          })
          .fail(function (failure) {
            self._setFailure(self.options.failure || failure, initial)
          })

//        if (failure = self._validate()) {
//          self._setFailure(self.options.failure || failure, initial)
//          return false
//        } else {
//          self._setSuccess(self.options.success, initial)
//          return true
//        }
      },

      _setFailure: function (failure, initial) {
        var self = this
        self.$group().addClass('has-error')
        if ($.type(failure) === 'string') {
          self.$alert().html(failure)
        }
      },

      _setSuccess: function (success, initial) {
        var self = this
        if (initial) {
          return
        }
        self.$group().addClass('has-success')
        if ($.type(success) === 'string') {
          self.$alert().html(success)
        }
      },

      isValid: function () {
        return !this.$group().hasClass('has-error')
      }

    }
  )

  return Beats.Field

})(jQuery);
