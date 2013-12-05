/**
 * Beats Control
 */
(function ($) {

  var _ = {
    mergeData: function (self) {
      var data = self.element.data()
      if (jQuery.isPlainObject(data)) {
        $.each(self.options, function (key) {
          if (data[key] !== undefined) {
            self.options[key] = data[key]
          }
        })
      }
    }
  }

  Beats.Control = can.Control({
    defaults: {
      view: null,
      tplV: {}
    },
    factory: function (opts, tplV) {
      tplV = $.extend(true, {}, this.defaults.tplV, tplV || opts.tplV);
      var df = can.view(this.defaults.view, tplV)
      return new this($(df.children), opts)
    }
  }, {
    init: function () {
      _.mergeData(this)
    },

    block: function (show, text) {
      return Beats.Blocker.toggle(show, text, this.element)
    }

  })

  return Beats.Control

})(jQuery);
