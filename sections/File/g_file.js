/**
 * Раздел "Файлы". Таблица.
 */
irisControllers.classes.g_File = IrisGridController.extend({

  cancel: function(event) {
    if (event.preventDefault) {
      event.preventDefault();
    }
    return false;
  },

  onDrop: function(event) {
    this.onDropFile(event);
  },

  onDropFile: function(event) {
    if (typeof FormData == "undefined") {
      alert('Вероятно, Вы используете слишком старый браузер. ' +
          'В нем не поддерживается эта операция.')
      return;
    }
    var self = this;
    var files = event.originalEvent.target.files || 
        event.originalEvent.dataTransfer.files;
    if (files && files.length > 0) {
      var loaded = 0;
      var getMsg = function(number, count) {
        return T.t("Загрузка файла") + "<br>" + 
          number + " " + T.t("из") + " " + count + "&hellip;";
      }

      Dialog.info(getMsg(loaded, files.length), {
        width: 250,
        showProgress: true,
        className: "iris_win"
      });

      _.each(files, function(file) {
        // Файл для передачи на сервер
        var fd = new FormData;
        fd.append("file", file);

        // Если вкладка
        var parameters = {};
        var parent_id = self.$el.attr('detail_parent_record_id');
        if (parent_id) {
          parameters.parent_type = 'detail';
          parameters.source_name = self.$el.attr('source_name');
          //parameters.detail = self.$el.attr('detail_name');
          parameters.detail_column_value = parent_id;
        }

        self.request({
          'class': 's_File',
          method: 'onFileUpload',
          files: fd,
          parameters: parameters,
          onSuccess: function(transport) {
            loaded++;
            try {
              self.onFileUploadSuccess(
                  JSON.parse(transport.responseText).data, loaded >= files.length);
              Dialog.setInfoMessage(getMsg(loaded, files.length));
              if (loaded >= files.length) {
                Dialog.closeInfo();
              }
            }
            catch (e) {
              self.onFileUploadError();
            }
          },
          onFail: function() {
            self.onFileUploadError();
          }
        });
      });
    }
    event.preventDefault();
  },

  onFileUploadSuccess: function(data, completed) {
    if (completed) {
      this.onDropFinished();
      redraw_grid(this.el.id, data.file.id);
    }
  },

  onFileUploadError: function() {
    //alert('Ошибка при загрузке файла!');
    Dialog.closeInfo();
    Dialog.alert(T.t('Ошибка при загрузке файла'), {
      width: 250,
      className: "iris_win"
    });
    this.onDropFinished();
  },

  onDropFinished: function() {
    jQuery('.iris-drag-container').remove();
  },

  onOpen: function() {
    var self = this;
    this.$el.find('td').on('dragenter', function(event) {
      var pos = self.$el.parent().position();
      var style = 'left: ' + pos.left + 'px; ' +
          'top: ' + pos.top + 'px; ' +
          'width: ' + self.$el.parent().width() + 'px; ' +
          'height: ' + self.$el.parent().height() + 'px;';
      self.$el.parent()
          .append('<div class="iris-drag-container" style="' + style + '"></div>');

      jQuery('.iris-drag-container').on('dragleave', function(event) {
        self.onDropFinished();
      });

      jQuery('.iris-drag-container').on('dragenter', function(event) {
        self.cancel(event);
      });

      jQuery('.iris-drag-container').on('dragover', function(event) {
        self.cancel(event);
      });

      jQuery('.iris-drag-container').on('drop', function(event) {
        self.onDrop(event);
      });

    });
  }

});
