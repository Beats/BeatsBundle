/**
 * Beats Modal Popup
 */
(function () {

  /********************************************************************************************************************/

  Beats.Modal.Popup = Beats.Control.extend({
    pluginName: 'beats_modal_popup',
    defaults: {
      $trigger: null,
      offset: null,

      title: null,
      label: null,
      clsGroup: null,
      items: {},

      view: 'beats.can.modal.popup.ejs',
      tplV: {
        title: null,
        label: null,
        items: null
      }
    },

    make: function (options) {
      var $popup = $('<div/>');
      $(document.body).append($popup);
      return new this($popup, options);
    },

    register: function (trigger, options) {
      var self = this;
      $(document).on('click', trigger, function (evt) {
        evt.stopPropagation();
        evt.preventDefault();
        var $trigger = $(this);

        options = options || {};
        options.$trigger = $trigger;
        self.make(options)
      })
    }

  }, {

    init: function () {
      var self = this;
      self._super.apply(self, arguments);

      self._onBeforeRender.call(self);

      $.each([
        'title', 'label', 'items', 'clsGroup'
      ], function (idx, field) {
        self.options.tplV[field] = self.options[field]
      });

      self.backdrop(true);
      self.element.html(self.options.view, self.options.tplV, function () {
        self.element.addClass('beats-modal-popup popover');
        var position = self._onPosition(self.options.$trigger.offset(), self.options.$trigger);
        self.element.addClass(position);
        self._onAfterRender.call(self);
        self.$button().click(function (evt) {
          evt.stopPropagation();
          evt.preventDefault();
          var $button = $(this)
            , name = $button.attr('href').substr(1)
            , item = self.options.items[name]
            ;
          if (item && $.isFunction(item.handler)) {
            item.handler.call(self, self.options.$trigger, $button)
              .always(function (kill) {
                if (kill) {
                  self.kill();
                }
              })
          }
        });
        self.element.show();
      });

    },

    _onBeforeRender: function () {

    },

    _onAfterRender: function () {

    },

    _onPosition: function (offset, $trigger) {
      var self = this
        , pH = self.element.height()
        , pW = self.element.width()
        , tH = $trigger.height()
        , tW = $trigger.width()
        ;
      self.element.css('top', offset.top - (pH - tH) / 2);
      self.element.css('left', offset.left - pW);
      return 'left'
    },

    $button: function (type) {
      return type
        ? this.element.find('a[href="#' + type + '"]')
        : this.element.find('a[href^="#"]')
        ;
    },

    kill: function () {
      this.backdrop(false);
      this.element.remove();
    },

    _buildBackdrop: function () {
      return $('<div class="modal-backdrop fade in"></div>').click(function () {
        this.kill();
      }.bind(this));
    },

    backdrop: function (show) {
      var $body = $(document.body);
      if (show) {
        $body.addClass('modal-open');
        $body.append(this._buildBackdrop())
      } else {
        $body.removeClass('modal-open');
        $body.find('.modal-backdrop').remove();
      }
    }

  });

  /********************************************************************************************************************/

  return Beats.Modal.Popup

})(jQuery);