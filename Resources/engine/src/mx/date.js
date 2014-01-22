/**
 * Beats Date
 */
(function ($) {


  function _zero_pad(value) {
    return value < 10 ? '0' + value : value
  }

  function _normalize_time(parts) {
    if (parts.H === undefined) {
      parts.h = (parts.h % 12) | 0
      parts.H = parts.h
      if (parts.p == 'pm') {
        parts.H += 12
      }
      parts.H = _zero_pad(parts.H)
    } else {
      parts.h = _zero_pad(parts.H % 12)
      parts.p = (parts.H < 12) ? 'am' : 'pm'
    }
    return parts
  }

  $.Class('Beats.Date', {

    iso2partsDate: function (iso) {
      var date = iso.split('-')
      return {
        y: date[0],
        m: date[1],
        d: date[2]
      }
    },

    iso2partsTime: function (iso) {
      var time = iso.split(':')
      return _normalize_time({
        H: time[0],
        i: time[1],
        s: time[2]
      })
    },

    iso2partsDateTime: function (iso, separator) {
      var part = iso.split(separator || ' ')
        , date = part[0].split('-')
        , time = part[1].split(':')
      return $.extend({}, this.iso2partsDate(iso), this.iso2partsTime(iso))
    },

    parts2isoDate: function (parts) {
      return [parts.y, parts.m, parts.d].join('-')
    },

    parts2isoTime: function (parts) {
      parts = _normalize_time(parts);
      return [parts.H, parts.i, parts.s].join(':')
    },

    parts2isoDateTime: function (parts, separator) {
      return [
        this.Class.parts2isoDate(parts),
        this.Class.parts2isoTime(parts)
      ].join(separator || ' ')
    },


    parts2date: function (parts) {
      return new Date(
        (parts.y | 0),
        (parts.m | 0) - 1,
        (parts.d | 0),
        (parts.H | 0),
        (parts.i | 0),
        (parts.s | 0)
      )
    }

  }, {

  })


  return Beats.Date

})(jQuery);