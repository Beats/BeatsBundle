/**
 * Beats Field Autocomplete
 */
(function ($) {

  /******************************************************************************************************************/

  Beats.Field.Autocomplete = Beats.Field.extend({
    pluginName: 'beats_field_autocomplete',

    defaults: {
      size: 4,

      lookuper: null,
      displayer: function (value) {
        return value;
      },
      optioner: function (value) {
        return value;
      },
      valuer: function (store, value) {
        return value;
      },

      view: 'beats.can.field.autocomplete.ejs',
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

      if (!$.isFunction(self.options.optioner)) {
        self.options.optioner = self.options.displayer;
      }
      self._super.apply(self, arguments)
    },

    _beforeRender: function () {
      var self = this;
      self._super.apply(self, arguments);

      self.options.tplV.name = self.options.name;
      self.options.tplV.size = self.options.size;
      self.options.tplV.display = self.options.displayer(
        self.options.valuer(false, self.options.preset)
      );
    },

    _afterRender: function () {
      var self = this
        , $display = self.$display()
        , $options = self.$options()
        ;

      self._super.apply(self, arguments);

      var suppressKeyPressRepeat, suppressNonChars;

      $display
        .on('blur', function (evt) {
          if (self.isOptionsShown()) {
            self.revert();
          }
        })

        .on('keydown', function (evt) {
          suppressKeyPressRepeat = ~$.inArray(evt.keyCode, [13, 27, 38, 40]);

          if (self.isOptionsShown() && !evt.shiftKey) {
            var handled = false;
            switch (evt.keyCode) {
              case 13: // Enter
                self._select($options.find('li.active'));
                handled = true;
                break;

              case 27: // Esc
                self.revert();
                handled = true;
                break;

              case 38: // Up arrow
                self._shiftActive(-1);
                handled = true;
                break;

              case 40: // Down arrow
                self._shiftActive(+1);
                handled = true;
                break;
            }
            if (handled) {
              evt.preventDefault();
              evt.stopPropagation();
            }
          }

        })
        .on('keypress', function (evt) {
          if (suppressKeyPressRepeat) {
            return;
          }
          suppressNonChars = !evt.which
        })
        .on('keyup', function (evt) {
          if (suppressKeyPressRepeat || suppressNonChars) {
            return;
          }
          evt.stopPropagation();
          evt.preventDefault();
          self._lookup($(this).val());
        })
      ;

      $options
        .on('mouseenter', 'li', function (evt) {
          evt.stopPropagation();
          self._activate($(evt.currentTarget));
        })
        .on('mousedown', function (evt) {
          evt.preventDefault();
        })
        .on('click', 'li', function (evt) {
          evt.stopPropagation();
          self._select($(evt.currentTarget));
        })
      ;

    },

    _activate: function ($newOption, $oldOption) {
      if (!$oldOption) {
        $oldOption = this.$options().find('li.active');

      }
      $oldOption.removeClass('active');
      $newOption.addClass('active');
    },

    _shiftActive: function (direction) {
      var $oldActive = this.$options().find('.active')
        , $newActive
        ;
      if (!direction) {
        return;
      } else if (0 < direction) {
        $newActive = $oldActive.next();
        if (!$newActive.length) {
          $newActive = this.$options().find('li:first');
        }
      } else if (direction < 0) {
        $newActive = $oldActive.prev();
        if (!$newActive.length) {
          $newActive = this.$options().find('li:last');
        }
      }
      this._activate($newActive, $oldActive);
    },

    _lookup: function (term) {
      var self = this
        , $options = self.$options();
      if (Beats.empty(term)) {
        $options.hide();
        self.element.val('');
      } else {
        self.options.lookuper(term)
          .done(function (error, items) {
            if (items.length) {
              self._buildOptions(items);
              $options.show();
            } else {
              $options.hide();
            }
          })
        ;
      }
    },

    _buildOptions: function (items) {
      var self = this
        , $options = self.$options()
        , $ul = $options.find('ul')
        ;
      $ul.empty();
      $.each(items, function (idx, item) {
        var $li = self._buildOption(item);
        $ul.append($li);
      });
      $options.show();
    },

    _buildOption: function (item) {
      var $li = $('<li class="list-group-item"/>');
      $li.data('value', item);
      $li.text(this.options.optioner(item));
      return $li;
    },

    isOptionsShown: function () {
      return this.$options().is(':visible')
    },

    $options: function () {
      return this.$group().find('div')
    },

    $display: function () {
      return this.$group().find('input')
    },

    reset: function () {
      var self = this;
      self.element.data('selected', self.options.preset);
      self._update();
      return self;
    },

    revert: function () {
      var self = this;
      self.element.data('selected', self.options.valuer(false, self.element.val()));
      self._update();
      return self;
    },

    _defaulter: function (value) {
      return this.options.valuer(false, value)
    },

    _select: function ($li) {
      if ($li) {
        this.element.data('previous', this.options.valuer(false, this.element.val()));
        this.element.val(this.options.valuer(true, $li.data('value')));
        this._update()
      }
    },

    _update: function (initial) {
      var self = this
        , oldValue
        ;

      if (initial) {
        oldValue = self.options.preset;
      } else {
        oldValue = self.element.data('previous');
      }

      return self._super.apply(self, arguments)
        .always(function (failure, newValue) {
          if (oldValue !== newValue) {
            self.$display().val(self.options.displayer(newValue));
            self.element.val(self.options.valuer(true, newValue));
            if (!initial) {
              self.element.trigger(jQuery.Event('change'), [newValue, !failure])
            }
          }
          self.$options().hide();
        });
    },

    _validate: function (value, initial) {
      var self = this;
      return self._super.apply(self, [self.options.valuer(false, value), initial]);
    }

  });

  /******************************************************************************************************************/

  return Beats.Field.Autocomplete

})(jQuery);
