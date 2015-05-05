/**
 * Beats Field Image
 */
(function () {

  Beats.Field.Image = Beats.Control.extend({
    pluginName: 'beats_field_image',
    defaults: {
      alert: {
        success: _('beats.engine.can.field.image.success'),
        failure: _('beats.engine.can.field.image.failure')
      },
      url: null,
      acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
      maxFileSize: 4000000, // 4 MB
      rotateField: null
    }

  }, {

    init: function () {
      var self = this;
      self._super.apply(self, arguments);

      if (!$.isFunction(self.options.onDone)) {
        throw new Error('Beats.Field.Image requires the onDone callback function. Will be removed in future versions');
      }

      var $control = self.$control()
        , $button = self.$button()
        , $progress = self.$progress()
        , $progressBars = self.$progressBars()
        , $progressText = self.$progressText()
        , $preview = self.$preview()
        , $inputs = self.$inputs()
        , $alert = self.$alert()
        , _ = {
          updatePreview: function (url) {
            $button.trigger(jQuery.Event('beats.field.avatar.preview', {
              url: url
            }), [url]);
            if ($.isFunction(self.options.onPreview)) {
              self.options.onPreview(url);
            }
            if (url) {
              $preview.attr('src', url);
            }
          },
          updateProgress: function (progress) {
            var percentage = progress + '%';
            $progressBars.css('width', percentage);
            $progressText.text(percentage);
          },
          toggleProgress: function (show) {
            if (show) {
              $button.hide(0);
              $progress.show(0);
            } else {
              $button.show(0);
              $progress.hide(0);
            }
          }
        }
        ;


      $alert.hide();

      self.element.prop('disabled', !$.support.fileInput);
      $button.prop('disabled', !$.support.fileInput);

      self.element.fileupload({
        url: self.options.url,
        dataType: 'json',
        maxNumberOfFiles: 1,
        acceptFileTypes: self.options.acceptFileTypes,
        maxFileSize: self.options.maxFileSize, // 4 MB
        rotateField: self.options.rotateField,

        formData: function ($form) {
          return $inputs.serializeArray()
        },
        start: function (e, data) {
          $alert.empty().hide();
          $control.removeClass('has-error has-success');
          _.updateProgress(0);
          _.toggleProgress(true);
        },
        progressall: function (e, data) {
          var progress = parseInt(data.loaded / data.total * 100, 10);
          _.updateProgress(progress);
        },
        done: function (e, data) {
          _.updateProgress(100);
          var url = null, dfd = $.Deferred();
          if (data.result.success) {
            dfd.resolveWith(self, [false, data.result, _.updatePreview]);
            $control.addClass('has-success');
            $alert.html(self.options.alert.success).show()
          } else {
            dfd.rejectWith(self, [true, data.result, _.updatePreview]);
            $control.addClass('has-error');
            $alert.html(self.options.alert.failure).show();
            console.error(data.result.message);
          }
          self.options.onDone(dfd);
          _.toggleProgress(false);
        },
        processdone: function (e, data) {
          var file = data.files[data.index]
        },
        processfail: function (e, data) {
          var file = data.files[data.index];
          $control.addClass('has-error');
          $alert.html(file.error).show();
          _.updateProgress(0);
          _.toggleProgress(false);
        }
      })

    },

    $inputs: function () {
      return this.element.siblings('input')
    },

    $control: function () {
      return this.element.parents('.form-group')
    },

    $button: function () {
      return this.$control().find('.btn')
    },

    $progress: function () {
      return this.$control().find('.progress')
    },

    $progressText: function () {
      return this.$progressBars().find('span');
    },

    $progressBars: function () {
      return this.$progress().find('.progress-bar');
    },

    $alert: function () {
      return this.$control().find('.help-block')
    },

    $preview: function () {
      return this.$control().find('img')
    }

  });

  return Beats.Field.Image

})(jQuery);