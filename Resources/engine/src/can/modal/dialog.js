/**
 * Beats Dialog
 */
(function ($) {

  var __ = {
    id: 0,
    trigger: function (self, type, data) {
      self.element.trigger(jQuery.Event('beats.modal.dialog.' + type, {
        data: data
      }), [self, self.element, type])
    },
    proxy: function (self, type) {
      return function (evt) {
        __.trigger(self, type, self)
      }
    },
    button: function (button, value, dissmis, text, classes) {
      switch ($.type(button)) {
        case  'object':
          button.dismiss = dissmis
          button.value = value
          break
        default:
          button = {
            dismiss: dissmis,
            classes: classes || '',
            text: button || text,
            value: value
          }
          break
      }
      return button
    },

    bootstrap: function (self) {

      self.element.modal({
        backdrop: self.options.backdrop,
        keyboard: self.options.keyboard,
        remote: self.options.remote,
        show: false
      })

      self.element.on({
        'show.bs.modal': __.proxy(self, 'show'),
        'shown.bs.modal': __.proxy(self, 'shown'),
        'hide.bs.modal': __.proxy(self, 'hide'),
        'hidden.bs.modal': function (evt) {
          //noinspection JSBitwiseOperatorUsage,JSBitwiseOperatorUsage,JSBitwiseOperatorUsage,JSBitwiseOperatorUsage
          if (Beats.empty(self.options.value)) {
            self.options.deferred.rejectWith(self, [self.options.value, self.options.data])
          } else {
            self.options.deferred.resolveWith(self, [self.options.value, self.options.data])
          }
          __.trigger(self, 'hidden', self)

          if (self.options.destroy) {
            self.element.remove()
          }
        }
      })

      self.element.trigger(jQuery.Event('beats.modal.dialog.built'), [
        self.element.find('.modal-body'),
        self.element.find('.modal-header'),
        self.element.find('.modal-footer')
      ])

      if (self.options.show) {
        self.show(self.options.deferred)
      }
    }
  }

  /******************************************************************************************************************/

  Beats.Modal.Dialog = Beats.Control({
    pluginName: 'beats_modal_dialog',
    defaults: {
      id: null,
      deferred: null,
      destroy: true,

      show: true,
      backdrop: true,
      keyboard: true,
      remote: false,

      header: true,
      close: true,
      value: 0,
      data: null,
      title: null,
      footer: true,
      body: null,
      buttons: [],

      view: 'beats.can.modal.dialog.ejs',
      tplV: {
        header: true,
        close: null,
        value: 0,
        title: null,
        body: null,
        footer: true,
        buttons: null
      }
    },

    make: function (options) {
      var $dialog = $("<div></div>")
      $('body').append($dialog)
      return new this($dialog, options)
    },

    show: function (options) {
      var dfd = $.Deferred()
      this.make($.extend(options || {}, {
        deferred: dfd,
        destroy: true
      }))
      return dfd
    },

    confirm: function (question, title, ok, no) {
      var options = {
        body: question,
        title: title,
        buttons: [],
        value: 0
      }
      if (!title) {
        if (title === false) {
          options.title = ''
          options.close = false
          options.header = false
        } else {
          options.title = 'Confirm'
        }
      }

      options.buttons.push(__.button(ok, 1, true, 'Yes', 'btn-primary'))
      options.buttons.push(__.button(no, 0, true, 'No'))
      return this.show(options)
    },

    caution: function (message, title, button) {
      var options = {
        body: message,
        title: title,
        buttons: [],
        value: 1
      }
      if (!title) {
        if (title === false) {
          options.title = ''
          options.close = false
          options.header = false
        } else {
          options.title = '&nbsp;'
          options.close = true
          options.header = true
        }
      }
      if (button === false) {
        options.footer = false
        return this.show(options)
      }
      options.buttons.push(__.button(button, 1, true, 'OK', 'btn-primary'))
      return this.show(options)
    }

  }, {

    init: function () {
      this._super.apply(this, arguments)
      var self = this

      if (Beats.empty(self.options.view)) {

        __.bootstrap(self)

      } else {

        if (Beats.empty(self.options.id)) {
          self.options.id = 'beats-dialog-' + (++__.id)
        }

        $.each(['id',
          'header', 'close', 'value', 'title',
          'body',
          'footer', 'buttons'
        ], function (idx, field) {
          self.options.tplV[field] = self.options[field]
        })

        self.element.html(self.options.view, self.options.tplV, function () {
          self.element.addClass('modal fade')
          self.element.attr({
            id: self.options.id,
            tabindex: '-1',
            role: 'dialog',
            'aria-hidden': 'true',
            'aria-labelledby': self.options.id + '-modal-title'
          })

          __.bootstrap(self)

        })
      }

    },

    show: function (dfd) {
      var self = this
      self.options.value = null
      self.options.data = null
      self.options.deferred = dfd || $.Deferred()
      self.options.deferred.$dialog = self.element
      self.element.data('deferred', self.options.deferred)
      self.element.modal('show')
      return self.options.deferred
    },

    hide: function (value, data) {
      if (value !== undefined) {
        this.options.value = value
      }
      if (data !== undefined) {
        this.options.data = data
      }
      this.element.modal('hide')
    },

    disable: function (toggle) {
      this.element.find(':input').prop('disabled', toggle)
    },

    'button click': function ($btn, evt) {
      var self = this
      if ($btn.data('dismiss')) {
        self.options.value = $btn.val()
      }
    }

  })

  /******************************************************************************************************************/

  return Beats.Modal.Dialog

})(jQuery)
