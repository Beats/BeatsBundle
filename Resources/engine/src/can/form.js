/**
 * Beats Form
 */
(function ($) {

  Beats.Form = Beats.Control.extend({
    defaults: {
      url: null
    },

    ajaxReset: function ($form) {
      $form.get(0).reset();
      $form.find('.form-group').each(function () {
        var $group = $(this);
        $group.removeClass('has-error has-success has-warning');
        $group.find('.help-block').remove();
      });
    },

    resetGroup: function ($group) {
      $group.removeClass('has-error has-success has-warning');
      $group.find('.help-block').remove();
    },

    alertGroup: function ($group, message) {
      this.resetGroup($group);
      $group.addClass(this.resolutionClass(message));
      $.each(message.text, function (idx, text) {
        $group.append($('<span class="help-block">' + text + '</span>'));
      });
    },

    alertForm: function ($form, messages) {
      $.each(messages, function (idx, message) {
        var $input = this.findInput($form, message)
          , $group = this.findGroup($input)
          ;
        this.alertGroup($group, message);
      }.bind(this));
    },

    findInput: function ($form, message) {
      return $form.find('[name=' + message.name.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + ']')
    },

    findGroup: function ($input) {
      var $group = $input.parents('.form-group');
      if ($group.length) {
        return $group
      }
      return $input.next('.form-group')
    },

    resolutionClass: function (message) {
      switch (message.type) {
        //case 'optional':
        //  return 'has-warning';
        case 'failure':
          return 'has-error';
        case 'success':
          return 'has-success';
      }
    },

    ajaxSubmit: function ($form, options, dfd) {
      var self = this;
      if (!$form.is('form')) {
        throw Beats.Error(this, 'The given element is not a valid form')
      }
      options = $.isPlainObject(options) || {
        url: options,
        type: 'POST'
      };

      if (!dfd) {
        dfd = $.Deferred();
      }

      $.ajax($.extend(options, {
        data: new FormData($form.get(0)),
        processData: false,
        contentType: false
      })).then(
        function (response, status, xhr) {
          if (response.success) {
            dfd.resolve(response);
            $form.trigger(jQuery.Event('beats.form.success'), response);
          } else {
            self.alertForm($form, response.data);
            $form.trigger(jQuery.Event('beats.form.failure'), response);
            dfd.reject(response);
          }
        }, function (response, status, xhr) {
          dfd.reject(response);
          $form.trigger(jQuery.Event('beats.form.exception'), response);
        }
      );

      return dfd.promise();
    }

  }, {});

  return Beats.Form

})(jQuery);