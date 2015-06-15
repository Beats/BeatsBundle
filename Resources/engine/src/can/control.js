/**
 * Beats Control
 */
(function ($) {

  var __ = {
    mergeData: function (self) {
      var data = self.element.data();
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
      var df = can.view(this.defaults.view, tplV);
      var html = df.children;
      if (html === undefined) {
        html = [];
        $.each(df.childNodes, function (idx, node) {
          if (node.nodeType === 1) {
            html.push(node);
          }
        });
      }
      return new this($(html), opts)
    }
  }, {
    init: function () {
      __.mergeData(this)
    },

    block: function (show, text) {
      return Beats.Blocker.toggle(show, text, this.element)
    }

  });

  return Beats.Control

})(jQuery);
