/**
 * Beats Field File
 */
(function () {

  var __ = {
    clearInputFile: function ($input) {
      if (/MSIE/.test(navigator.userAgent)) {
        $input.wrap('<form>').closest('form').on('reset', function (evt) {
          evt.stopPropagation();
        }).get(0).reset();
        $input.unwrap();
      } else { // normal reset behavior for other sane browsers
        $input.val('');
      }
    }
  };

  Beats.Field.File = Beats.Control.extend({
    pluginName: 'beats_field_file',
    defaults: {
      alert: {
        success: _('beats.engine.can.field.file.success'),
        failure: _('beats.engine.can.field.file.failure')
      },
      url: null,
      acceptFileTypes: undefined,
      maxFileSize: undefined,
      minFileSize: undefined,
      rotateField: null
    }

  }, {

    init: function () {
      var self = this;
      self._super.apply(self, arguments);

      var $control = self.$control()
        , $button = self.$button()
        , $progress = self.$progress()
        , $progressBars = self.$progressBars()
        , $progressText = self.$progressText()
        , $preview = self.$preview()
        , $caption = self.$caption()
        , $inputs = self.$inputs()
        , $alert = self.$alert()
        , $clear = self.$clear()
        ;
      $clear.hide();

      $caption.click(function (evt) {
        evt.stopPropagation();
        self.element.get(0).click()
      });
      $button.click(function (evt) {
        evt.stopPropagation();
        self.element.get(0).click()
      });
      self.$control().on('click', '.beats_field_file-preview', function (evt) {
        evt.stopPropagation();
        self.element.get(0).click();
      });

      if (Beats.empty(self.options.url)) {
        var FUP = $.blueimp.fileupload.prototype;

        self.element.on('change', function (evt) {
          $preview.empty().hide();
          $alert.empty().hide();
          $clear.hide();
          $control.removeClass('has-error has-success');
          delete evt.target.files.error;
          delete evt.target.files[0].error;

          FUP.processActions.validate.call({
            options: {
              getNumberOfFiles: $.noop,
              i18n: function (message, context) {
                return _('beats.engine.can.field.file.failure.' + message);
              }
            }
          }, {
            files: evt.target.files,
            index: 0
          }, {
            maxNumberOfFiles: 1,
            acceptFileTypes: self.options.acceptFileTypes,
            minFileSize: self.options.minFileSize,
            maxFileSize: self.options.maxFileSize
          })
            .done(function (data) {
              var file = data.files[data.index];
              var text = file.name.replace(/\\/g, '/').replace(/.*\//, '');
              $caption.val(text);
              $clear.show();
              switch (true) {
                case FUP.options.loadImageFileTypes.test(file.type):
                  data.preview = self._loadPreview(file, 'img', 'image');
                  break;
                case FUP.options.loadAudioFileTypes.test(file.type):
                  data.preview = self._loadPreview(file, 'audio', 'audio');
                  break;
                case FUP.options.loadVideoFileTypes.test(file.type):
                  data.preview = self._loadPreview(file, 'video', 'video');
                  break;
              }
              return data;
            })
            .fail(function (data) {
              var file = data.files[data.index];
              $caption.val('');
              $control.addClass('has-error');
              $alert.html(file.error).show();
              $clear.hide();
            })
        });

        $clear.click(function () {
          self.clear()
        })

      } else {
        if (!$.isFunction(self.options.onDone)) {
          throw new Error('Beats.Field.File requires the onDone callback function. Will be removed in future versions');
        }

        var __ = {
            updatePreview: function (url) {
              $button.trigger(jQuery.Event('beats.field.file.preview', {
                url: url
              }), [url]);
              if ($.isFunction(self.options.onPreview)) {
                self.options.onPreview(url);
              }
              if (url) {
                var $image = $(document.createElement('img'));

                $image.prop({
                  src: url,
                  controls: true
                });

                $image.addClass('img-thumbnail btn-block');
                $preview.append($image).slideDown();
              }
            },
            updateProgress: function (progress) {
              var percentage = progress + '%';
              $progressBars.css('width', percentage);
              $progressText.text(percentage);
            },
            toggleProgress: function (show) {
              if (show) {
                $progress.show(0);
              } else {
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
            $preview.empty().hide();
            $alert.empty().hide();
            $control.removeClass('has-error has-success');
            __.updateProgress(0);
            __.toggleProgress(true);
          },
          progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            __.updateProgress(progress);
          },
          done: function (e, data) {
            __.updateProgress(100);
            var url = null, dfd = $.Deferred();
            if (data.result.success) {
              dfd.resolveWith(self, [false, data.result, __.updatePreview]);
              $control.addClass('has-success');
              $alert.html(self.options.alert.success).show()
            } else {
              dfd.rejectWith(self, [true, data.result, __.updatePreview]);
              $control.addClass('has-error');
              $alert.html(self.options.alert.failure).show();
              console.error(data.result.message);
            }
            self.options.onDone(dfd);
            __.toggleProgress(false);
          },
          processdone: function (e, data) {
            var file = data.files[data.index];
            var text = file.name.replace(/\\/g, '/').replace(/.*\//, '');
            $caption.val(text);
          },
          processfail: function (e, data) {
            var file = data.files[data.index];
            $control.addClass('has-error');
            $alert.html(file.error).show();
            __.updateProgress(0);
            __.toggleProgress(false);
          }
        })
      }
    },

    _loadPreview: function (file, tag, type) {
      var url = loadImage.createObjectURL(file);
      if (!url) {
        return false;
      }
      return this._buildPreview(url, tag, type)
    },

    _buildPreview: function (url, tag, type) {
      var $preview = $(document.createElement(tag));

      $preview.prop({
        src: url,
        controls: true
      });
      this._displayPreview($preview, type);
      return $preview.get(0);
    },

    _displayPreview: function ($preview, type) {
      $preview.addClass('col-xs-12');
      this.$preview().append($preview).slideDown();
    },

    $inputs: function () {
      return this.element.siblings('input')
    },

    $control: function () {
      return this.element.parents('.form-group')
    },

    $button: function () {
      return this.$control().find('.beats_field_file-button')
    },

    $progress: function () {
      return this.$control().find('.beats_field_file-progress')
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
      return this.$control().find('.beats_field_file-preview')
    },

    $caption: function () {
      return this.element.parents('.input-group').find(':text')
    },

    $clear: function () {
      return this.$control().find('.beats_field_file-clear')
    },

    clear: function () {
      var self = this;
      if (Beats.empty(self.options.url)) {
        self.$preview().slideUp(null, function () {
          $(this).empty().hide()
        });
        self.$caption().val('');
        self.$alert().empty().hide();
        self.$clear().hide();
        self.$control().removeClass('has-error has-success');
        __.clearInputFile(self.element);
      } else {
        throw Beats.Error(self, 'Not Implemented', self.element.prop('id'));
      }
    }

  });

  return Beats.Field.File

})(jQuery);