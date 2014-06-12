/**
 * Beats Translator
 */
(function ($) {

  // LATER: Load /translations/translation.<locale>.js
  var _translations = translations
  delete translations

  Beats.Translator = can.Construct.extend({
    _locale: 'en',
    _message: {},
    _missing: {},

    setup: function (base, fullName, staticProps, protoProps) {
      var self = this
//      self._locale = _locale;
      self._message = _translations;
      can.Construct.setup.apply(self, arguments)
    },
    get: function (name, params) {
      var self = this
      if (name in self._message) {
        if (Beats.empty(params)) {
          return self._message[name]
        }
        var value = self._message[name];
        for (var key in params) {
          var regExp = new RegExp('%' + key + '%', 'g')
          value = value.replace(regExp, params[key])
        }
        return value;
      }
      if (!(name in self._missing)) {
        self._missing[name] = []
      }
      self._missing[name].push(window.location.href)
      return name
    }
  }, {
    init: function () {
      throw Beats.Error(this, "This is a singleton. Do NOT instantiate!")
    }
  });

  return Beats.Translator
})(jQuery);
function _(name, params) {
  return Beats.Translator.get(name, params)
}

