/**
 * Beats Blocker
 */
(function ($) {

  var _ = {
    instance: null
  }

  /******************************************************************************************************************/

  Beats.Controller('Beats.Blocker', {

    defaults: {
      block: null,
      fade: {
        show: 'fast',
        hide: 'slow'
      },
      html: {
        show: null,
        hide: null
      }
    },

    show: function (text, $el) {
      return _.instance.show.apply(_.instance, arguments)
    },
    hide: function (text, $ex) {
      return _.instance.hide.apply(_.instance, arguments)
    },
    toggle: function (show, text, $el) {
      return _.instance.toggle.apply(_.instance, arguments)
    }

  }, {

    init: function (el, opts) {
      this._super.apply(this, arguments)
      var self = this
      if (_.instance) {
        throw new Beats.Error('Already exists')
      }
      _.instance = self

      self.element.data('elements', [])

      if (!Beats.empty(self.options.block)) {
        self.show(self.options.block)
      }
    },

    blocking: function ($el) {
      var self = this
        , elements = self.element.data('elements')
      if (arguments.length) {
        for (var i in elements) {
          if ($el == elements[i]) {
            return i | 0
          }
        }
      }
      return elements.length > 0
    },

    toggle: function (show, text, $element) {
      return this[show ? 'show' : 'hide'].apply(this, [text, $element])
    },

    show: function (text, $element) {
      var self = this
        , $el = $element || self.element
        , dfd = self.element.data('onShow')
        , els = self.element.data('elements')
        , idx = self.blocking($el)
        , $td = self.element.find('td')
      $td.html(text || self.options.show)
      if (!els.length) {
        dfd = $.Deferred()
        self.element.fadeIn(self.options.fade.show, function () {
//          self.element.css('display', 'table-row')
          dfd.resolveWith(self, [true, $element, text])
        })
      }
      if (idx !== false) {
        els.push($el)
      }
      self.element.data('onShow', dfd)
      self.element.data('elements', els)
      return dfd
    },

    hide: function (text, $element) {
      var self = this
        , $el = $element || self.element
        , dfd = self.element.data('onHide')
        , els = self.element.data('elements')
        , idx = self.blocking($el)
        , $td = self.element.find('td')
      $td.html(text || self.options.show)

      if (idx !== false) {
        els.splice(idx, 1)
      }
      if (!els.length) {
        dfd = $.Deferred()
        self.element.fadeOut(self.options.fade.hide, function () {
          $td.empty()
          dfd.resolveWith(self, [false, $element, text])
        })
      }
      self.element.data('onHide', dfd)
      self.element.data('elements', els)
      return dfd

    }

  })

  /******************************************************************************************************************/

  return Beats.Blocker

})(jQuery)
