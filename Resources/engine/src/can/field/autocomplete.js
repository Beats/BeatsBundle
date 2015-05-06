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

      $display
        .on('keydown', function (evt) {
          self.suppressKeyPressRepeat = !$.inArray(evt.keyCode, [40,39,38,37,9,13,27]);
          if (!self.isOptionsShown() && evt.keyCode == 40) {
            self._lookup($(this).val());
          } else {
            self._move(evt);
          }
        })
        .on('keypress', function (evt) {
          if (self.suppressKeyPressRepeat) {
            return;
          }
          self._move(evt);
        })
        .on('keyup', function (evt) {
          //console.debug('keyup', $(this).val());
          switch (evt.keyCode) {
            case 40: // down arrow
            case 39: // right arrow
            case 38: // up arrow
            case 37: // left arrow

            case 16: // shift
            case 17: // ctrl
            case 18: // alt
              break;

            case 9: // tab
            case 13: // enter
              if (!self.isOptionsShown()) {
                return;
              }
              //this.select();
              break;

            case 27: // escape
              if (!self.isOptionsShown()) {
                return;
              }
              self.toggleOptions(false);
              break;

            default:
              self._lookup($(this).val());
          }

          evt.stopPropagation();
          evt.preventDefault();
        })
      ;
      $options
        .on('click', 'li', function (evt) {
          evt.stopPropagation();
          var $li = $(evt.target);
          self.element.data('selected', $li);
          self._update()
        });
    },

    _move: function(evt) {
      //if (!this.isOptionsShown()) {
      //  return;
      //}

      switch(evt.keyCode) {
        case 9: // tab
        case 13: // enter
        case 27: // escape
          evt.preventDefault();
          break;

        case 38: // up arrow
          // with the shiftKey (this is actually the left parenthesis)
          if (evt.shiftKey) return;
          evt.preventDefault();
          //this.prev();
          break;

        case 40: // down arrow
          // with the shiftKey (this is actually the right parenthesis)
          if (evt.shiftKey) return;
          evt.preventDefault();
          //this.next();
          break;
      }

      evt.stopPropagation();
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

    isOptionsShown: function() {
      return this.$options().is(':visible')
    },
    toggleOptions: function(show) {
      this.$options().toggle(!!show);
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
      $li.data('value', this.options.valuer(true, item));
      $li.text(this.options.optioner(item));
      return $li;
    },

    $options: function () {
      return this.$group().find('div')
    },

    $display: function () {
      return this.$group().find('input')
    },

    _update: function (initial) {
      var self = this
        , oldValue = self.options.valuer(false, self.element.val())
        ;
      return self._super.apply(self, arguments)
        .always(function (failure, newValue) {
          //self.$clear().toggle(!Beats.empty(newValue));
          if (oldValue !== newValue) {
            self.$display().val(self.options.displayer(newValue));
            self.element.val(self.options.valuer(true, newValue)).trigger(jQuery.Event('change'), [newValue, !failure])
          }
          self.$options().hide();
        })
    },

    _validate: function (oldValue, initial) {
      var self = this
        , newValue
        ;
      if (initial) {
        newValue = self.element.val()
      } else {
        newValue = self.element.data('selected').data('value');
      }
      newValue = self.options.valuer(false, newValue);
      return self._super.apply(self, [newValue]);
    }

  });

  /******************************************************************************************************************/

  return Beats.Field.Autocomplete

})(jQuery);
