/**
 * Beats Field Widget
 */
(function ($) {

  var __ = {
  }

  /******************************************************************************************************************/

  Beats.Field.Widget = Beats.Field.extend({

    defaults: {
      appear: {
        speed: {
          show: 'fast',
          hide: 'slow'
        },
        method: {
          show: 'slideDown',
          hide: 'slideUp'
        }
      }
    }

  }, {
    init: function (el, opts) {
      if ($.isPlainObject(opts.appear)) {
        if (!$.isPlainObject(opts.appear.speed)) {
          this.options.appear.speed = {
            show: opts.appear.speed,
            hide: opts.appear.speed
          }
        }
        if (!$.isPlainObject(opts.appear.method)) {
          this.options.appear.method = {
            show: opts.appear.method,
            hide: opts.appear.method
          }
        }
      }
      this._super.apply(this, arguments)
      var self = this
    },

    show: function (callback) {
      return this.toggle(true, callback)
    },
    hide: function (callback) {
      return this.toggle(false, callback)
    },
    toggle: function (show, callback) {
      var self = this
        , visible = self.element.is(':visible')
      if (visible == show) {
        return self;
      }
      if (show) {
        self.element[self.options.appear.method.show](self.options.appear.speed.show, callback)
      } else {
        self.element[self.options.appear.method.hide](self.options.appear.speed.hide, callback)
      }
      return self
    }

  })

  /******************************************************************************************************************/

  return Beats.Field.Widget;

})(jQuery)
