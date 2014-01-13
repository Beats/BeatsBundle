/**
 * Beats Flasher
 */
(function ($) {

  var _ = {
    types: {
      success: 'success',
      failure: 'danger',
      warning: 'warning',
      counsel: 'info'
    },
    instance: null,

    message: function (message, heading, type, delay, fade, method) {
      if (Beats.empty(message)) {
        return
      }
      var $message = Beats.Flasher.Message.factory({
        fade: fade || this.instance.options.fade,
        delay: delay || this.instance.options.delay,
        method: method || this.instance.options.method,
        tplV: {
          type: type,
          heading: heading,
          message: message
        }
      })
      Beats.Flasher.attach($message);
      return $message;
    },

    redirect: function (redirect, message, type, heading) {
      return window.location.href = Router.url('beats.basic.html.flash', {
        data: {
          redirect: redirect,
          message: message,
          type: type,
          heading: heading
        }
      })
    },

    page: function (message, type, heading, title, href) {
      return window.location.href = Router.url('beats.basic.html.flash', {
        data: {
          message: message,
          type: type,
          heading: heading,
          title: title,
          href: href
        }
      })
    }
  }

  /******************************************************************************************************************/

  Beats.Flasher = Beats.Control({
    pluginName: 'beats_flasher',
    defaults: {
      single: false,
      immediately: true,
      delay: 10000,
      fade: {
        show: 'fast',
        hide: 'slow'
      },
      method: {
        show: 'slideDown',
        hide: 'slideUp'
      }
    },

    success: function (message, heading, delay) {
      _.message(message, heading, _.types.success, delay)
    },
    failure: function (message, heading, delay) {
      _.message(message, heading, _.types.failure, delay)
    },
    warning: function (message, heading, delay) {
      _.message(message, heading, _.types.warning, delay)
    },
    counsel: function (message, heading, delay) {
      _.message(message, heading, _.types.counsel, delay)
    },

    exception: function (exception, heading, delay) {
      var message = exception ? exception.message : null
      _.message(message || 'An error occurred. Please try again!', heading, _.types.failure, delay)
    },

    attach: function ($message) {
      return _.instance.attach($message)
    },
    clear: function (immediately) {
      return _.instance.clear(immediately)
    },

    redirectSuccess: function (redirect, message, heading) {
      return _.redirect(redirect, message, _.types.success, heading)
    },
    redirectFailure: function (redirect, message, heading) {
      return _.redirect(redirect, message, _.types.failure, heading)
    },
    redirectWarning: function (redirect, message, heading) {
      return _.redirect(redirect, message, _.types.warning, heading)
    },
    redirectCounsel: function (redirect, message, heading) {
      return _.redirect(redirect, message, _.types.counsel, heading)
    },

    pageSuccess: function (message, type, heading, title, href) {
      return _.page(message, _.types.success, heading, title, href)
    },
    pageFailure: function (message, type, heading, title, href) {
      return _.page(message, _.types.failure, heading, title, href)
    },
    pageWarning: function (message, type, heading, title, href) {
      return _.page(message, _.types.warning, heading, title, href)
    },
    pageCounsel: function (message, type, heading, title, href) {
      return _.page(message, _.types.counsel, heading, title, href)
    }

  }, {
    init: function () {
      this._super.apply(this, arguments)
      if (_.instance) {
        throw new Beats.Error('Already exists')
      }
      _.instance = this
      var self = this
        , messages = []

      self.element.children().each(function (idx, span) {
        messages.push($(span).data())
      })
      self.element.empty()

      $.each(messages, function (idx, data) {
        if (!Beats.empty(data.type)) {
          _.message(data.message, data.heading, data.type)
        }
      })
    },

    clear: function (immediately) {
      var self = this
      self.element.children().each(function () {
        $(this).beats_flasher_message('kill', immediately)
      })
    },
    attach: function ($message) {
      var self = this
      if (this.options.single) {
        this.clear(self.options.immediately)
      }
      self.element.append($message.element)
      $message.show()
    }

  })

  /******************************************************************************************************************/

  Beats.Flasher.Message = Beats.Control({
    pluginName: 'beats_flasher_message',
    defaults: {
      delay: Beats.Flasher.defaults.delay,
      fade: Beats.Flasher.defaults.fade,
      method: Beats.Flasher.defaults.method,
      easing: 'swing',
      view: 'beats.can.flasher.message.ejs',
      tplV: {
        type: _.types.warning,
        message: null,
        heading: null
      }
    }
    /*
     , factory: function (opts, tplV) {
     return can.view(this.defaults.view, $.extend(true, {}, this.defaults.tplV, tplV))
     }
     */
  }, {
    _timerID: null,

    init: function () {
      var self = this
      self._super.apply(self, arguments)
      if (!$.isPlainObject(self.options.fade)) {
        self.options.fade = {
          show: self.options.fade,
          hide: self.options.fade
        }
      }
      if (!$.isPlainObject(self.options.method)) {
        self.options.method = {
          show: self.options.method,
          hide: self.options.method
        }
      }
      self.element.css('display', 'none')
    },

    show: function () {
      var self = this
      self.element[self.options.method.show]({
        easing: self.options.easing,
        duration: self.options.fade.show,
        complete: function () {
          if (self.options.delay) {
            self._timerID = setTimeout(function () {
              self.kill()
            }, self.options.delay)
          }
        }
      })
      return self;
    },

    hide: function () {
      var self = this
      self.element[self.options.method.hide]({
        easing: self.options.easing,
        duration: self.options.fade.hide,
        complete: function () {
          self.kill(true)
        }
      })
      return self;
    },

    kill: function (immediately) {
      var self = this
      if (self.options.fade && !immediately) {
        self.hide()
      } else {
        if (self._timerID) {
          clearTimeout(self._timerID)
          self._timerID = null
        }
        self.element.remove()
      }
    },

    'button.close click': function (el, evt) {
      this.hide()
    }

  })

  /******************************************************************************************************************/

  return Beats.Flasher

})(jQuery)
