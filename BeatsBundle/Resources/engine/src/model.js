/**
 * Beats Model
 */
(function ($) {

  var _ = {
    configREST: function (base, ctrl, id) {
      id = ['{', id || 'id', '}'].join('')
      return {
        findAll: _.buildRequestLine('GET', base, ctrl),
        findOne: _.buildRequestLine('GET', base, ctrl, id),
        create: _.buildRequestLine('POST', base, ctrl),
        update: _.buildRequestLine('PUT', base, ctrl, id),
        destroy: _.buildRequestLine('DELETE', base, ctrl, id)
      }
    },
    buildRequestLine: function () {
      return [
        Array.prototype.shift.apply(arguments),
        Array.prototype.join.call(arguments, '/')
      ].join(' ')
    }
  }

  $.Model('Beats.Model', {
    _base: 'rest',

    setup: function (superClass, name, klass, proto) {
      var self = this
        , base = klass._base || superClass._base
        , ctrl = klass._ctrl || name.toLowerCase() + 's'
        , id = klass.id || superClass.id


      $.extend(self, _.configREST(base, ctrl, id), {
        _ctrl: ctrl
      }, klass)

      $.Model.setup.apply(this, [superClass, klass, proto])
    },
    normalize: function (rows) {
      return rows
    }
  }, {
  })

  return Beats.Model

})(jQuery);
