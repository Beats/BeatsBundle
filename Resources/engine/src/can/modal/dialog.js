/**
 * Beats Dialog
 */
(function ($) {

  var _ = {
    id: 0,
    trigger: function (self, type, data) {
      self.element.trigger(jQuery.Event('beats.modal.dialog.' + type, {
        data: data
      }), [self, self.element, type])
    },
    proxy: function (self, type) {
      return function (evt) {
        _.trigger(self, type, self)
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

    show: function (options) {
      var $dialog = $("<div></div>")
        , dfd = $.Deferred()

      $('body').append($dialog)

      $dialog.beats_modal_dialog($.extend(options || {}, {
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

      options.buttons.push(_.button(ok, 1, true, 'Yes', 'btn-primary'))
      options.buttons.push(_.button(no, 0, true, 'No'))
      return Beats.Modal.Dialog.show(options)
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
          options.title = 'Alert'
        }
      }
      if (button === false) {
        options.footer = false
        return Beats.Modal.Dialog.show(options)
      }
      options.buttons.push(_.button(button, 1, true, 'OK', 'btn-primary'))
      return Beats.Modal.Dialog.show(options)
    }

  }, {

    init: function () {
      this._super.apply(this, arguments)
      var self = this

      if (Beats.empty(self.options.id)) {
        self.options.id = 'beats-dialog-' + (++_.id)
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
          'aria-labelledby': self.options.id + '-modal-header'
        })

        self.element.modal({
          backdrop: self.options.backdrop,
          keyboard: self.options.keyboard,
          remote: self.options.remote,
          show: false
        })
        self.element.on({
          'show.bs.modal': _.proxy(self, 'show'),
          'shown.bs.modal': _.proxy(self, 'shown'),
          'hide.bs.modal': _.proxy(self, 'hide'),
          'hidden.bs.modal': function (evt) {
            //noinspection JSBitwiseOperatorUsage,JSBitwiseOperatorUsage,JSBitwiseOperatorUsage,JSBitwiseOperatorUsage
            if (self.options.value | 0) {
              self.options.deferred.resolveWith(self, [self.options.value])
            } else {
              self.options.deferred.rejectWith(self, [self.options.value])
            }
            _.trigger(self, 'hidden', self)
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

      })
    },

    show: function (dfd) {
      var self = this
      self.options.deferred = dfd || $.Deferred()
      self.options.deferred.$dialog = self.element
      self.element.data('deferred', self.options.deferred)
      self.element.modal('show')
      return self.options.deferred
    },

    hide: function () {
      this.element.modal('hide')
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
