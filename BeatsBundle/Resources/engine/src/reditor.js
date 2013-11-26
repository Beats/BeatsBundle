/**
 * Beats Reditor
 */
(function ($) {

  var _ = {
    trigger: function (self, done) {
      if (done != self.element.data('editing')) {
        return
      }
      self.element.data('editing', !done)
      self.element.trigger(jQuery.Event(done ? '_beats.reditor.edit.done' : '_beats.reditor.edit.init', {
      }), [])
    },
    value: function (self) {
      if (self.element.is(':checkbox')) {
        return self.element.prop('checked') ? 1 : 0
      } else {
        return self.element.val()
      }
    }
  }

  /********************************************************************************************************************/

  Beats.Controller('Beats.Reditor', {

    defaults: {
      args: null,
      name: null,
      make: function (name, newValue, oldValue) {
        return {
          name: name,
          data: {
            'new': newValue,
            'old': oldValue
          }
        }
      },
      on: {
        enter: true
      }
    }

  }, {

    init: function (el, options) {
      this._super.apply(this, arguments)
      var self = this

      self.element.data('editing', false)

      self.element.trigger('beats.reditor.edit.done')

      self.element.bind('init', function (evt) {
        _.trigger(self, false)
      })
      self.element.bind('done', function (evt) {
        _.trigger(self, true)
      })

      if (self.element.is(':input')) {

        self.options.name = self.element.attr('name')

        if (self.element.is('input[type=hidden]')) {

          self.element.bind('change', function (evt) {
            _.trigger(self, true)
          })

        } else {

          self.element.bind('focus', function (evt) {
            _.trigger(self, false)
          })

          if (self.element.is(':checkbox,:radio')) {

            self.element.bind('click', function (evt) {
              _.trigger(self, true)
            })

          } else if (self.element.is(':text')) {

            if (self.options.on.enter) {
              self.element.bind('keypress', function (evt) {
                if (evt.which == 13) {
                  _.trigger(self, true)
                }
              })
            }

          }

          self.element.bind('blur', function (evt) {
            _.trigger(self, true)
          })
        }

      }

    },

    '_beats.reditor.edit.init': function ($el, evt) {
      var self = this
      self.element.data('old', _.value(self))
      self.element.trigger(jQuery.Event('beats.reditor.edit.init', {
      }), [])
    },

    '_beats.reditor.edit.done': function ($el, evt) {
      var self = this
      self.element.data('new', _.value(self))
      self.element.trigger(jQuery.Event('_beats.reditor.save.init', {
        'new': self.element.data('new'),
        'old': self.element.data('old')
      }), [self.element.data('new'), self.element.data('old')])
    },

    '_beats.reditor.save.init': function ($el, evt, valueNew, valueOld) {
      var self = this

      if (valueNew == valueOld) {

        self.element.trigger(jQuery.Event('_beats.reditor.save.done', {
        }), [false])

      } else {

        self.options.args.data = self.options.make(
          self.options.name, valueNew, valueOld
        )

        self.element.trigger(jQuery.Event('beats.reditor.save.prepare', {
          data: self.options.args.data
        }), [self.options.args.data])

        Beats.Route.resolve($.ajax(self.options.args), self)
          .done(function (value) {
            self.element.trigger(jQuery.Event('beats.reditor.save.success', {
              value: value,
              data: self.options.args.data
            }), [value, self.options.args.data])
          }).fail(function (error) {
            self.element.trigger(jQuery.Event('beats.reditor.save.failure', {
              error: error,
              data: self.options.args.data
            }), [error, self.options.args.data])
          }).always(function (success, result) {
            self.element.trigger(jQuery.Event('beats.reditor.save.cleanup', {
              data: self.options.args.data
            }), [self.options.args.data])
            self.element.trigger(jQuery.Event('_beats.reditor.save.done', {
            }), [true])
          })
      }
    },
    '_beats.reditor.save.done': function ($el, evt, ajax) {
      var self = this
      self.element.data('new', null)
      self.element.data('old', null)
      self.element.trigger(jQuery.Event('beats.reditor.edit.done', {
      }), [ajax])
    },

    'beats.reditor.edit.init': function ($el, evt) {
      var self = this
      self.element.parents('.control-group').addClass('warning').removeClass('success').removeClass('error')
    },
    'beats.reditor.edit.done': function ($el, evt, ajax) {
      var self = this
      self.element.parents('.control-group').removeClass('warning')
      self.element.blur()
    },

    'beats.reditor.save.success': function ($el, evt, value) {
      var self = this
      self.element.val(value).change()
      self.element.parents('.control-group').addClass('success').removeClass('error')
      self.element.parents('.controls').find('.help-inline').empty().hide()
      self.element.parents('.control-group').find('label').append($('<span class="italic dgrey small padding-left-small">Saved</span>'))
    },
    'beats.reditor.save.failure': function ($el, evt, error) {
      var self = this
      self.element.val(self.element.data('new')).change()
      self.element.parents('.control-group').addClass('error').removeClass('success')
      var $help = self.element.parents('.controls').find('.help-inline')
      if ($help.length) {
        $help.html(error.message);
      } else {
        self.element.parents('.controls').append($('<span class="help-inline">' + error.message + '</span>'))
      }

    },

    'beats.reditor.save.prepare': function ($el, evt, data) {
      var self = this
        , $label = self.element.parents('.control-group').find('label')
      self.element.prop('readonly', true)
      $label.find('span').remove()
      $label.append($('<i class="icon-spinner icon-spin padding-left-small"></i>'))
    },
    'beats.reditor.save.cleanup': function ($el, evt, data) {
      var self = this
        , $label = self.element.parents('.control-group').find('label')
      self.element.prop('readonly', false)
      $label.find('i').remove()
    }


  })

  /********************************************************************************************************************/

  return Beats.Reditor

})(jQuery)
