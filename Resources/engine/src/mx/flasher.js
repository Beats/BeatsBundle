/**
 * Beats Flasher
 */
(function ($) {

  var _ = {
    types: {
      success: 'success',
      failure: 'error',
      warning: 'block',
      counsel: 'info'
    },
    instance: null,
    message: function (message, heading, type, delay, fade, method) {
      return !Beats.empty(message) && Beats.Flasher.Message.factory({
        delay: delay || this.instance.options.delay,
        fade: fade || this.instance.options.fade,
        method: method || this.instance.options.method,
        tplV: {
          type: type,
          heading: heading,
          message: message
        }
      })
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

  Beats.Controller('Beats.Flasher', {

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

    attach: function (message) {
      if (_.instance.options.single) {
        this.clear(_.instance.options.immediately)
      }
      _.instance.element.append(message.element)
    },

    clear: function (immediately) {
      _.instance.element.children().each(function () {
        $(this).beats_flasher_message('kill', immediately)
      })
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
    init: function (el, opts) {
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
    }
  })

  /******************************************************************************************************************/

  Beats.Controller('Beats.Flasher.Message', {

    defaults: {
      delay: Beats.Flasher.defaults.delay,
      fade: Beats.Flasher.defaults.fade,
      method: Beats.Flasher.defaults.method,
      easing: 'swing',
      view: 'beats.flasher.message.ejs',
      tplV: {
        type: _.types.warning,
        message: null,
        heading: null
      }
    }

  }, {
    _timerID: null,

    init: function (el, opts) {
      if (!$.isPlainObject(opts.fade)) {
        this.options.fade = {
          show: opts.fade,
          hide: opts.fade
        }
      }
      if (!$.isPlainObject(opts.method)) {
        this.options.method = {
          show: opts.method,
          hide: opts.method
        }
      }
      this._super.apply(this, arguments)
      var self = this
      self.element.hide().after(function () {
        Beats.Flasher.attach(self)
        self.element[self.options.method.show]({easing: self.options.easing, duration: self.options.fade.show})
      })
      if (self.options.delay) {
        self._timerID = setTimeout(function () {
          self.kill()
        }, self.options.delay)
      }
    },

    kill: function (immediately) {
      var self = this
      if (self.options.fade && !immediately) {
        self.element[self.options.method.hide]({easing: self.options.easing, duration: self.options.fade.hide, complete: function () {
          self.element.remove()
        }})
      } else {
        self.element.remove()
      }
      if (self._timerID) {
        clearTimeout(self._timerID)
        self._timerID = null
      }
    },

    'button.close click': function (el, evt) {
      this.kill()
    }

  })

  /******************************************************************************************************************/

  return Beats.Flasher

})(jQuery)