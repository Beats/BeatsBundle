(function () {

  /******************************************************************************************************************
   * Javascript String extensions
   ******************************************************************************************************************/

  String.prototype.trim = function () {
    return this.replace(/^\s+|\s+$/g, '');
  };

  String.prototype.ltrim = function () {
    return this.replace(/^\s+/, '');
  };

  String.prototype.rtrim = function () {
    return this.replace(/\s+$/, '');
  };

  String.prototype.fulltrim = function () {
    return this.replace(/(?:(?:^|\n)\s+|\s+(?:$|\n))/g, '').replace(/\s+/g, ' ');
  };

  String.trim = function (str, charlist) {
    /*
     // http://kevin.vanzonneveld.net
     // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
     // +   improved by: mdsjack (http://www.mdsjack.bo.it)
     // +   improved by: Alexander Ermolaev (http://snippets.dzone.com/user/AlexanderErmolaev)
     // +      input by: Erkekjetter
     // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
     // +      input by: DxGx
     // +   improved by: Steven Levithan (http://blog.stevenlevithan.com)
     // +    tweaked by: Jack
     // +   bugfixed by: Onno Marsman
     // *     example 1: trim('    Kevin van Zonneveld    ');
     // *     returns 1: 'Kevin van Zonneveld'
     // *     example 2: trim('Hello World', 'Hdle');
     // *     returns 2: 'o Wor'
     // *     example 3: trim(16, 1);
     // *     returns 3: 6
     */
    var whitespace, l = 0,
      i = 0;
    str += '';

    if (!charlist) {
      // default list
      whitespace = " \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000";
    } else {
      // preg_quote custom list
      charlist += '';
      whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^:])/g, '$1');
    }

    l = str.length;
    for (i = 0; i < l; i++) {
      if (whitespace.indexOf(str.charAt(i)) === -1) {
        str = str.substring(i);
        break;
      }
    }

    l = str.length;
    for (i = l - 1; i >= 0; i--) {
      if (whitespace.indexOf(str.charAt(i)) === -1) {
        str = str.substring(0, i + 1);
        break;
      }
    }

    return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
  };

  String.ucfirst = function (str, force) {
    str = force ? str.toLowerCase() : str;
    return str.replace(/(\b)([a-zA-Z])/, function (first) {
      return   first.toUpperCase()
    })
  };

  String.ucwords = function (str, force) {
    str = force ? str.toLowerCase() : str;
    return str.replace(/(\b)([a-zA-Z])/g, function (first) {
      return   first.toUpperCase()
    })
  };

  if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position) {
      position = position || 0;
      return this.lastIndexOf(searchString, position) === position;
    };
  }

  /******************************************************************************************************************
   * Javascript Date extensions
   ******************************************************************************************************************/

  function _zero_pad(value) {
    return value < 10 ? '0' + value : value
  }

  function _date_diff(d1, d2, granulation) {
    return Math.floor(((d2 || new Date()).getTime() - (d1 || new Date()).getTime()) / (granulation || 1));
  }

  function _date_granulate(diff, granulation) {
    if (1 < granulation) {
      var result = Math.floor(diff._ % granulation)
      diff._ /= granulation
    } else {
      var result = Math.floor(diff._)
      diff._ -= result
    }
    return result
  }

  Date.diff = {
    milliseconds: function (d1, d2) {
      return _date_diff(d1, d2)
    },
    seconds: function (d1, d2) {
      return _date_diff(d1, d2, 1000)
    },
    minutes: function (d1, d2) {
      return _date_diff(d1, d2, 60 * 1000)
    },
    hours: function (d1, d2) {
      return _date_diff(d1, d2, 60 * 60 * 1000)
    },
    days: function (d1, d2) {
      return _date_diff(d1, d2, 24 * 60 * 60 * 1000)
    },
    weeks: function (d1, d2) {
      return _date_diff(d1, d2, 7 * 24 * 60 * 60 * 1000)
    },
    span: function (d1, d2) {
      var _ = _date_diff(d1, d2)
        , diff = {
          $: _,
          u: 0,
          s: 0,
          i: 0,
          h: 0,
          d: 0,
          w: 0,
          _: _
        }
      diff.u = _date_granulate(diff, 1000)
      diff.s = _date_granulate(diff, 60)
      diff.m = _date_granulate(diff, 60)
      diff.h = _date_granulate(diff, 24)
      diff.d = _date_granulate(diff, 7)
      diff.w = _date_granulate(diff, 1)
      return diff
    }

  }

  Date.prototype.diffMilliseconds = function (date) {
    return Date.diff.milliseconds.call(this, this, date)
  }
  Date.prototype.diffSeconds = function (date) {
    return Date.diff.seconds.call(this, this, date)
  }
  Date.prototype.diffMinutes = function (date) {
    return Date.diff.minutes.call(this, this, date)
  }
  Date.prototype.diffHours = function (date) {
    return Date.diff.hours.call(this, this, date)
  }
  Date.prototype.diffDays = function (date) {
    return Date.diff.days.call(this, this, date)
  }
  Date.prototype.diffWeeks = function (date) {
    return Date.diff.weeks.call(this, this, date)
  }
  Date.prototype.diffSpan = function (date) {
    return Date.diff.span.call(this, this, date)
  }

  Date.Format = {
    // Some common format strings
    masks: {
      defaultValue: "ddd MMM dd yyyy HH:mm:ss",
      shortDate: "m/d/yy",
      mediumDate: "MMM d, yyyy",
      longDate: "MMMM d, yyyy",
      fullDate: "dddd, MMMM d, yyyy",
      shortTime: "h:mm TT",
      mediumTime: "h:mm:ss TT",
      longTime: "h:mm:ss TT Z",
      isoDate: "yyyy-MM-dd",
      isoTime: "HH:mm:ss",
      isoDateTime: "yyyy-MM-dd'T'HH:mm:ss",
      isoUtcDateTime: "UTC:yyyy-MM-dd'T'HH:mm:ss'Z'"
    },
    // Internationalization strings
    i18n: {
      dayNames: [
        "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
        "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
      ],
      monthNames: [
        "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
        "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
      ]
    },
    widgets: {
      date: '',
      time: '',
      full: ''
    },
    defaults: {
      date: "mm/dd/yyyy",
      time: 'h:mm tt',
      full: 'mm/dd/yyyy h:mm tt'
    }

  }

  Date.prototype.toFormat = function (mask, utc) {
    // format display
    // base on Date Format 1.2.3
    // http://blog.stevenlevithan.com/archives/date-time-format
    // (c) 2007-2009 Steven Levithan <stevenlevithan.com> MIT license

    if (typeof mask === 'undefined') {
      mask = Date.Format.defaults.full;
    } else if (typeof mask !== 'string') {
      mask = mask ? Date.Format.defaults.date : Date.Format.defaults.time
    }

    var timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
      timezoneClip = /[^-+\dA-Z]/g,
      pad = function (val, len) {
        val = String(val);
        len = len || 2;
        while (val.length < len) val = "0" + val;
        return val;
      };

    // Regexes and supporting functions are cached through closure
    function DateFormat(date, mask, utc) {
      var dF = Date.Format;

      // You can't provide utc if you skip other args (use the "UTC:" mask prefix)
      if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
        mask = date;
        date = undefined;
      }

      // Passing date through Date applies Date.parse, if necessary
      date = date ? new Date(date) : new Date;
      if (isNaN(date)) throw new SyntaxError("invalid date");

      mask = String(dF.masks[mask] || mask || dF.masks["defaultValue"]);

      // Allow setting the utc argument via the mask
      if (mask.slice(0, 4) == "UTC:") {
        mask = mask.slice(4);
        utc = true;
      }

      var _ = utc ? "getUTC" : "get",
        d = date[_ + "Date"](),
        D = date[_ + "Day"](),
        M = date[_ + "Month"](),
        y = date[_ + "FullYear"](),
        H = date[_ + "Hours"](),
        m = date[_ + "Minutes"](),
        s = date[_ + "Seconds"](),
        L = date[_ + "Milliseconds"](),
        o = utc ? 0 : date.getTimezoneOffset(),
        flags = {
          d: d,
          dd: pad(d),
          ddd: dF.i18n.dayNames[D],
          dddd: dF.i18n.dayNames[D + 7],
          M: M + 1,
          MM: pad(M + 1),
          MMM: dF.i18n.monthNames[M],
          MMMM: dF.i18n.monthNames[M + 12],
          yy: String(y).slice(2),
          yyyy: y,
          h: H % 12 || 12,
          hh: pad(H % 12 || 12),
          H: H,
          HH: pad(H),
          m: m,
          mm: pad(m),
          s: s,
          ss: pad(s),
          l: pad(L, 3),
          L: pad(L > 99 ? Math.round(L / 10) : L),
          t: H < 12 ? "a" : "p",
          tt: H < 12 ? "am" : "pm",
          T: H < 12 ? "A" : "P",
          TT: H < 12 ? "AM" : "PM",
          Z: utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
          o: (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
          S: ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
        };

      return mask.replace(new RegExp(/d{1,4}|M{1,4}|yy(?:yy)?|([HhmsTt])\1?|[LloSZ]|"[^"]*"|\'[^\']*'/g), function ($0) {
        return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
      });
    }

    return DateFormat(this, mask, utc);
  }

  Date.prototype.toISODate = function (utc) {
    return utc
      ? [this.getUTCFullYear(), _zero_pad(this.getUTCMonth() + 1), _zero_pad(this.getUTCDate())].join('-')
      : [this.getFullYear(), _zero_pad(this.getMonth() + 1), _zero_pad(this.getDate())].join('-')
  }

  Date.prototype.toISOTime = function (utc, milliseconds) {
    var time = utc
      ? [_zero_pad(this.getUTCHours()), _zero_pad(this.getUTCMinutes()), _zero_pad(this.getUTCSeconds())].join(':')
      : [_zero_pad(this.getHours()), _zero_pad(this.getMinutes()), _zero_pad(this.getSeconds())].join(':')
    return milliseconds ? [time, utc ? this.getUTCMilliseconds() : this.getMilliseconds()].join('.') : time
  }

  Date.prototype.toISODateTime = function (utc, milliseconds, separator) {
    return [this.toISODate(utc), this.toISOTime(utc, milliseconds)].join(separator ? separator : ' ')
  }

  Date.prototype.toISO = function (type, utc) {
    if (typeof type === 'undefined') {
      return this.toISODateTime(utc)
    } else if (typeof type !== 'string') {
      return type ? this.toISODate(utc) : this.toISOTime(utc)
    } else {
      switch (type) {
        case 'date':
          return this.toISODate(utc)
        case 'time':
          return this.toISOTime(utc)
      }
      return this.toISODateTime(utc)
    }
  }

  Date.prototype.getUTCTime = function () {
    return this.getTime() + (this.getTimezoneOffset() * 60000)
  }

  Date.prototype.toTZ = function (offsetHours) {
    return new Date(this.getUTCTime() + (3600000 * offsetHours))
  }

  Date.ISO2UTC = function (iso, offsetHours) {
    var date = Date.fromISO(iso, true) // fake UTC
    if (!date) {
      return date
    }
    var ltz = date.toTZ(-offsetHours)  // shift to TZ
    iso = ltz.toISODateTime()          // remove local TZ
    date = Date.fromISO(iso, true)     // fake UTC again
    return date.toISODateTime(true)    // export iso
  }

  Date.fromISO = function (iso, utc) {
    var date = null, parts = /(\d{4})-(\d{2})-(\d{2})(.(\d{2}):(\d{2}):(\d{2})(\.(\d+))?)?/g.exec(iso)
    if (parts) {
      date = new Date()
      if (utc) {
        if (parts[0]) {
          date.setUTCFullYear(parts[1] | 0, parts[2] - 1, parts[3] | 0)
        }
        if (parts[4]) {
          date.setUTCHours(parts[5] | 0, parts[6] | 0, parts[7] | 0)
        }
        if (parts[8]) {
          date.setUTCMilliseconds(parts[9] | 0)
        }
      } else {
        if (parts[0]) {
          date.setFullYear(parts[1] | 0, parts[2] - 1, parts[3] | 0)
        }
        if (parts[4]) {
          date.setHours(parts[5] | 0, parts[6] | 0, parts[7] | 0)
        }
        if (parts[8]) {
          date.setMilliseconds(parts[9] | 0)
        }
      }
    }
    return date;
  }

  Date.now = function (utc) {
    var now = new Date();
    if (utc) {
      return new Date(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), now.getUTCHours(), now.getUTCMinutes(), now.getUTCSeconds())
    }
    return now
  }

  Date.today = function (utc) {
    var now = new Date();
    if (utc) {
      return new Date(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), 0, 0, 0, 0)
    }
    return new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0, 0)
  }

})();