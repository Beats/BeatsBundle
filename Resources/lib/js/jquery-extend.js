(function ($) {

  if ($ === undefined) {
    return
  }

  $.fn.extend({
    serializeObject: function () {
      var object = {};
      $.each(this.serializeArray(), function (idx, item) {
        if (object[item.name] === undefined) {
          object[item.name] = item.value;
        } else {
          var array = object[item.name];
          if (!array.push) {
            array = [array];
          }
          array.push(item.value);
        }
      });
      return object
    }
  });

  $.extend($, {
    count: function (value) {
      if (value) {
        switch ($.type(value)) {
          case 'array':
            return value.length;
          case 'object':
            if (Object && $.isFunction(Object.keys)) {
              return Object.keys(value).length
            }
            var count = 0;
            $.each(value, function () {
              count++
            });
            return count
        }
        return 1
      }
      return 0
    },

    join: function (value, separator) {
      if (value) {
        switch ($.type(value)) {
          case 'array':
            return value.join(separator);
          case 'object':
            return $.map(value, function (value) {
              return value
            }).join(separator)
        }
        return value
      }
      return ''
    },

    diff: function (arr1, arr2) {
      return $.merge($(arr1).not(arr2), $(arr2).not(arr1));
    },

    keys: function (object) {
      var key, keys = [];
      for (key in object) {
        keys.push(key);
      }
      return keys;
    }
  });

})(jQuery);
