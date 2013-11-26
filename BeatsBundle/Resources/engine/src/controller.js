/**
 * Beats Controller
 */
(function ($) {

  var _ = {
    tpl: 'ejs',
    tplID: function (self) {
      return self.Class.fullName.toLowerCase() + '.' + this.tpl
    },
    tplExists: function (elID) {
      var el = document.getElementById(elID)
      return el && el.tagName.toLowerCase() == 'script'
    },
    mergeData: function (self) {
      var data = self.element.data()
      $.each(self.options, function (key) {
        if (data[key] !== undefined) {
          self.options[key] = data[key]
        }
      })
    },
    extractJQ: function (self) {
      $.each(self.options.$ || {}, function (idx, selector) {
        self.$[idx] = self.element.find(selector)
      })
    }
  }

  $.Controller('Beats.Controller', {

    defaults: {
      view: null,
      tplV: {}
    },

    factory: function (opts, tplV) {
      tplV = $.extend(true, {}, this.defaults.tplV, tplV || opts.tplV);
      return new this($($.View(this.defaults.view, tplV)), opts)
    }

  }, {
    $: {},

    init: function (el, opts) {
//      var tplID = _.tplID(this);
//      if (_.tplExists(tplID)) {
//        this.options.view = tplID
//      }
      _.mergeData(this);
      _.extractJQ(this)

    },

    block: function (show, text) {
      return Beats.Blocker.toggle(show, text, this.element)
    },

    _reloadJQElements: function () {
      _.extractJQ(this)
    },
    _reloadDOMData: function () {
      _.mergeData(this);
    }

  })

  return Beats.Controller

})(jQuery);
