/**
 * Beats Can Engine
 */
(function ($) {

  var __ = {
    _tag: null,
    tag: function () {
      if (!this._tag) {
        this._tag = document.createElement("DIV")
      }
      return this._tag;
    },
    cookie: function (name) {
      var cookieValue = null;
      if (document.cookie && document.cookie != '') {
        var cookies = document.cookie.split(';');
        for (var i = 0; i < cookies.length; i++) {
          var cookie = jQuery.trim(cookies[i]);
          // Does this cookie string begin with the name we want?
          if (cookie.substring(0, name.length + 1) == (name + '=')) {
            cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
            break;
          }
        }
      }
      if (jQuery.evalJSON && cookieValue && cookieValue.match(/^\s*\{/)) {
        try {
          cookieValue = jQuery.evalJSON(cookieValue);
        }
        catch (e) {
        }
      }
      return cookieValue;
    }
  };

  window.Beats = {
    _version: '0.1',

    timezone: __.cookie('beats_tz'),

    Error: function (self, message, id) {
      var error = new Error(message, id);
      error.name = self.Class ? self.Class.fullName : self.fullName;
      return error;
    },

    log: function (self, fn) {
      self = self || this;
      console.log([self.Class.fullName, '->', fn].join(' '))
    },

    /**
     * A copy of PHP's empty function
     * @param variable
     * @returns {boolean}
     */
    empty: function (variable) {
      var key, i, len;
      var emptyValues = [undefined, null, false, 0, '', '0'];

      for (i = 0, len = emptyValues.length; i < len; i++) {
        if (variable === emptyValues[i]) {
          return true;
        }
      }

      if (typeof variable === "object") {
        for (key in variable) {
          return false;
        }
        return true;
      }

      return false;
    },

    strip: function (html) {
      if (this.empty(html)) {
        return ""
      }
      var tag = __.tag();
      tag.innerHTML = html;
      return tag.textContent || tag.innerText;
    },

    ellipsis: function (text, length, suffix) {
      if (!text) {
        return ''
      }
      text = text.trim();
      if (!text.length) {
        return ''
      }
      if (text.length <= length) {
        return text
      }
      if (suffix && suffix.length) {
        length -= suffix.length
      } else {
        suffix = ''
      }
      return text.substr(0, length) + suffix;
    },

    encodeEntities: function (s) {
      return $(__.tag()).text(s).html();
    },
    decodeEntities: function (s) {
      return $(__.tag()).html(s).text();
    },

    invoke: function (callback, scope, arguments) {
      if ($.isFunction(callback)) {
        callback.apply(scope, arguments);
        return true
      }
      return false
    },

    _helpers: {
      $: $,
      jQuery: $,
      options: function (opts, selected, classes) {
        var parts = [];
        $.each(opts, function (idx, opt) {
          parts.push(Beats._helpers.option(opt.lbl, opt.val, selected, classes));
        });
        return parts.join('');
      },
      option: function (label, value, selected, classes, disabled) {
        var parts = ['<option value="', value, '"'];
        if (!Beats.empty(classes)) {
          parts.push(' class="', classes, '"')
        }
        if (value == selected) {
          parts.push(' selected="selected"')
        }
        if (disabled) {
          parts.push(' disabled="disabled"');
          parts.push(' style="display:none;"')
        }
        parts.push('>', label, '</option>');
        return parts.join('');
      },
      count: function (object) {
        return jQuery.count(object)
      }
    },

    view: function (helpers) {
      return $.extend({}, Beats._helpers, helpers || {})
    }

  };

// Determine the current user TimeZone
  if (!Beats.timezone) {
    __.cookie('beats_tz', Beats.timezone = jstz.determine().name())
  }

  return Beats

})(jQuery);
