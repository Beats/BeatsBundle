/**
 * Beats Field Tags
 */
(function ($) {

  /******************************************************************************************************************/

  Beats.Field.Tags = Beats.Field.extend({
    pluginName: 'beats_field_tags',

    defaults: {
      silent: true,
      size: 4,
      maxCount: undefined,
      minCount: undefined,
      view: 'beats.can.field.tags.ejs',
      tplV: {
        name: null,
        display: null,
        value: null,
        size: null
      }
    }

  }, {

    init: function () {
      var self = this;
      self._super.apply(self, arguments)
    },

    _beforeRender: function () {
      var self = this;
      self._super.apply(self, arguments);

      Beats.Error(self, "This component isn't self-rendering");
      self.options.tplV.name = self.options.name;
      self.options.tplV.size = self.options.size;
    },

    _afterRender: function () {
      var self = this
        , $options = self.$options()
        , $searcher = self.$searcher()
        , $selected = self.$selected()
        , $rejected = self.$rejected()
        ;
      $searcher.toggle(!!$rejected.children().length);
      $rejected.toggle(!!$rejected.children().length);
      $selected.toggle(!!$selected.children().length);

      var options = {};
      $options.each(function (idx, el) {
        var $el = $(el);
        options[$el.val()] = $el.text();
      });
      self.options.options = options;

      self._super.apply(self, arguments);

      $selected.on('click', '.beats_field_tags-tag', function (evt) {
        self.reject($(this).data('value'));
      });
      $rejected.on('click', '.beats_field_tags-tag', function (evt) {
        self.select($(this).data('value'));
      });

      self.$searcherText()
        .on('change', function () {
          if (!$(this).val().length) {
            $rejected.children().show();
          }
        })
        .on('keydown', function (evt) {
          if (!evt.shiftKey) {
            var handled = false;
            switch (evt.keyCode) {
              case 13: // Enter
                var $tags = $rejected.children().filter(':visible');
                if ($tags.length == 1) {
                  $tags.click();
                  $(this).val('');
                }
                handled = true;
                break;

              case 27: // Esc
                $(this).val('');
                handled = true;
                break;
            }
          }
          if (handled) {
            evt.preventDefault();
            evt.stopPropagation();
          }
        })
        .on('keyup', function () {
          var term = $(this).val().toLowerCase()
            , $tags = $rejected.children()
            ;
          $tags.each(function (idx, el) {
            var $tag = $(el)
              , text = $tag.text().trim().toLocaleLowerCase()
              ;
            if (text.startsWith(term)) {
              $tag.show();
            } else {
              $tag.hide();
            }
          });
        })
      ;

      self.element.on('change', function (evt) {
        self._update();
        self.element.data('previous', self.element.val() || [])
      });
      self.element.on('update', function (evt) {
        var values = $(this).val() || [];
        var previous = $.keys(self.options.options);
        var diff = $.diff(values, previous);

        $.each(diff, function (idx, value) {
          var $el = _isSelected(value);
          if ($el.length) {
            $el.prependTo($rejected);
          }
        });
        $.each(values, function (idx, value) {
          var $el = _isRejected(value);
          if ($el.length) {
            $el.prependTo($selected);
          }
        });
        $rejected.toggle(!!$rejected.children().length);
        $selected.toggle(!!$selected.children().length);
      });

      function _isSelected(value) {
        return $selected.find('[data-value="' + value + '"]')
      }

      function _isRejected(value) {
        return $rejected.find('[data-value="' + value + '"]')
      }

    },

    select: function (value) {
      var values = this.element.val() || [];
      if (~$.inArray(value, values)) {
        return;
      }
      this.element.data('previous', values);
      this.element.val($.merge([value], values));
      this._update();

    },
    reject: function (value) {
      var values = this.element.val() || [];
      if (!~$.inArray(value, values)) {
        return;
      }
      this.element.data('previous', values);
      this.element.val($.grep(values, function (val) {
        return val !== value;
      }));
      this._update();
    },

    $option: function (value) {
      return this.$options().find('[value="' + value + '"]')
    },

    $options: function () {
      return this.element.find('option')
    },
    $searcher: function () {
      return this.$group().find('.beats_field_tags-searcher')
    },
    $searcherText: function () {
      return this.$searcher().find('input')
    },
    $selected: function () {
      return this.$group().find('.beats_field_tags-selected')
    },
    $rejected: function () {
      return this.$group().find('.beats_field_tags-rejected')
    },

    reset: function () {
      var self = this;
      self.element.data('selected', self.options.preset);
      self._update();
      return self;
    },

    _update: function (initial) {
      var self = this
        , oldValue
        ;

      if (initial) {
        self.element.data('previous', self.options.preset)
      }
      oldValue = self.element.data('previous');

      return self._super.apply(self, arguments)
        .always(function (failure, newValue) {
          if ($.diff(oldValue, newValue).length) {
            self.element.val(newValue);
            if (!initial) {
              self.element.trigger(jQuery.Event('update'));
            }
            if (!isNaN(this.options.maxCount)) {
              self.$rejected().toggle(newValue.length != self.options.maxCount);
              self.$searcher().toggle(newValue.length != self.options.maxCount);
            }
          }
        });
    },

    _limitReached: function (values) {
      return (!isNaN(this.options.minCount) && (values.length < this.options.minCount))
        || (!isNaN(this.options.maxCount) && (this.options.maxCount < values.length))
        ;
    },

    _validate: function (values, initial) {
      var self = this
        ;
      values = values || [];

      if (self._limitReached(values)) {
        var oldValue;
        if (initial) {
          oldValue = self.options.preset;
        } else {
          oldValue = self.element.data('previous');
        }
        self.element.val(oldValue);
        return $.Deferred().resolveWith(self, [false, oldValue]);
      }
      return self._super.apply(self, [values, initial]);
    }

  });

  /******************************************************************************************************************/

  return Beats.Field.Tags

})(jQuery);