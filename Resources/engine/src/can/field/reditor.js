/**
 * Beats Field Reditor
 */
(function ($) {

  /********************************************************************************************************************/

  Beats.Field.Reditor = Beats.Field.extend({
    pluginName: 'beats_field_reditor',
    defaults: {
      sender: null,
      parser: null,
      onEnter: undefined
    }
  }, {

    init: function () {
      var self = this
      self._super.apply(self, arguments)

      self.editing(false)
      self.element.data('old', self.value())


      self.element.on({
        'init.beats.field.reditor': function () {
          self._editInit()
        },
        'done.beats.field.reditor': function () {
          self._editDone()
        }
      })

      if (self.options.onEnter === undefined) {
        self.options.onEnter = self.element.is(':text')
      }

      if (self.element.is('input[type=hidden]')) {

        // TODO@ion When do we do self._editInit?
        self.element.on('change', function (evt) {
          self._editDone()
        })

      } else {

        self.element.on('focus', function (evt) {
          self._editInit()
        })

        self.element.on('blur', function (evt) {
          self._editDone()
        })

        if (self.options.onEnter) {
          self.element.on('keypress', function (evt) {
            if (evt.which == 13) {
              self._editDone()
            }
          })
        }

        if (self.element.is(':checkbox,:radio')) {
          self.element.on('click', function (evt) {
            self._editDone()
          })
        }

      }


    },

    editing: function (editing) {
      if (editing === undefined) {
        return this.element.data('editing')
      }
      this.element.data('editing', editing)
      return this
    },

    _editInit: function () {
      var self = this
      if (self.editing()) {
        return
      }
      self.editing(true)
      self.element.data('old', self.value())
      self.element.trigger(jQuery.Event('beats.field.edit.init'), [])
    },
    _editDone: function () {
      var self = this
      if (!self.editing()) {
        return
      }
      self.editing(false)
      self.element.data('new', self.value())
      self.element.trigger(jQuery.Event('beats.field.edit.done'), [])
      self._saveInit()
    },

    _saveInit: function () {
      var self = this
      self.element.prop('readonly', true)
      self._update()
    },
    _saveDone: function (success, result) {
      var self = this
      self.element.prop('readonly', false)
      self.element.data('new', null)
      self.element.data('old', null)
      self.element.blur() // Force user to focus again
    },

    _parse: function (value) {
      var self = this
      return $.isFunction(self.options.parser)
        ? self.options.parser.apply(self, arguments)
        : value
    },
    _validate: function (valueNew, initial) {
      var self = this
        , dfdValidator = self._super.apply(self, arguments)
      if (initial) {
        return dfdValidator
      }
      var dfdSender = $.Deferred()
      dfdValidator
        .done(function (failure, valueNew) {
          var valueOld = self.element.data('old')
          self.element.prop('readonly', true)
          if (valueNew === valueOld) {
            self._saveDone()
            dfdSender.resolveWith(self, [false, valueNew])
          } else {
            var args = [valueNew, valueOld]
            self.element.trigger(jQuery.Event('beats.field.save.prepare'), args)
            var sender = $.isFunction(self.options.sender)
              ? self.options.sender.apply(self, args)
              : self.options.sender

            sender
              .always(function (success, result) {
                self._saveDone(success, result)
              })
              .done(function () {
                var error = false
                  , value = self._parse.apply(self, arguments)
                self.element.val(value)
                self.element.trigger(jQuery.Event('beats.field.save.success', {
                  error: error,
                  value: value,
                  args: args
                }), [value, args])
                dfdSender.resolveWith(self, [error, value])
              })
              .fail(function () {
                var error = self._parse.apply(self, arguments)
                  , value = self.element.data('new')
                self.element.val(value)
                self.element.trigger(jQuery.Event('beats.field.save.failure', {
                  error: error,
                  value: value,
                  args: args
                }), [error, args])
                dfdSender.rejectWith(self, [error, value])
              })
              .always(function (data) {
                self.element.trigger(jQuery.Event('beats.field.save.cleanup', {
                  data: data,
                  args: args
                }), [data, args])
              })
          }
        })
        .fail(function () {
          self._saveDone()
          dfdSender.rejectWith(self, arguments)
        })

      return dfdSender;
    },

    'beats.field.edit.init': function ($el, evt) {
      var self = this
      self.$group().addClass('has-warning').removeClass('has-success has-error')
    },
    'beats.field.edit.done': function ($el, evt) {
      var self = this
      self.$group().removeClass('has-warning')
    },

    'beats.field.save.prepare': function ($el, evt, valueNew, valueOld) {
      var self = this
        , $label = self.$label()
      $label.find('span').remove()
      $label.append($('<i class="icon-spinner icon-spin padding-left-small"></i>'))
    },
    'beats.field.save.success': function ($el, evt, value) {
      var self = this
      self.$group().addClass('has-success').removeClass('has-error')
      self.$alert().empty().hide()
      self.$label().append($('<span class="italic dgrey small padding-left-small">Saved</span>'))
    },
    'beats.field.save.failure': function ($el, evt, error) {
      var self = this
      self.$group().addClass('has-error').removeClass('has-success')
      self.$alert().html(error.message).show()
    },
    'beats.field.save.cleanup': function ($el, evt, data) {
      var self = this
        , $label = self.$label()
      $label.find('i').remove()
    }


  })

  /********************************************************************************************************************/

  return Beats.Field.Reditor

})(jQuery)
