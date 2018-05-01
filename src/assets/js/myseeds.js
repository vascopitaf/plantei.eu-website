let parameters = [
  {"name": "seed_id", "value": "id"},
  {"name": "_id", "value": "id"},
  {"name": "common_name", "value": "common_name"},
  {"name": "local", "value": "local"},
  {"name": "year", "value": "year"},
  {"name": "public", "value": "public", "type": "checked"},
  {"name": "description", "value": "description", "type": "textarea"},
  {"name": "pictures[]", "value": "pictures[]", "type": "file" },
  {"name": "latin_name", "value": "latin_name"},
  {"name": "species", "value": "species.name"},
  {"name": "species_id", "value": "species.id"},
  {"name": "family", "value": "family.name"},
  {"name": "family_id", "value": "family.id"},
  {"name": "variety", "value": "variety.name"},
  {"name": "variety_id", "value": "variety.id"},
  {"name": "months[]", "value": "months[].month", "type": "checked"},
  {"name": "available", "value": "available", "type": "checked"},
  {"name": "polinization", "value": "polinization", "type": "checked"},
  {"name": "direct", "value": "direct", "type": "checked"}
];

$('form').on('submit', function (e){
  element = $('input[name="common_name"]');
  e.preventDefault();
  if (! element.val()) {
    $('#identification').collapse('show');
    element.closest('.form-group').addClass('has-error');
    element.next('.help-block').text('This field is the only field required');
    element.focus();
    return false;
  }
  tinymce.triggerSave();
  var formdata = $(this).serializeArray();
  $.post('/seedbank/register', formdata, function(data) {
    if (! data.errors ) {
      window.open('/seedbank/myseeds?seed_id=' + data.id, '_self');
      return false;
    }
    previewseed(parameters, data);
    $("form").hide();
    $("#seed-preview").show();
  });
  return false;
});

$( function () {

  $('form input[name=traditionalrisk]').on('change', function (e){
    console.log(e);
    if (e.target.value == "") {
      console.log('disable: ' + e.target.value);
      $(e.target).parents('div').first()
        .find('option[value="1"]').first().prop('selected', true)
        .closest('select').prop('disabled', true);
    } else {
      console.log('enable: ' + e.target.value);
      $(e.target).parents('div').first()
        .find('select').prop('disabled', false);
    }
  });
  $('form input[name=origin]').on('change', function (e){
    console.log('changed origin field');
    if (e.target.value == "1") {
      console.log('disable: ' + e.target.value);
      $(e.target).parents('div').first()
        .find('option[value="0"]').first().prop('selected', true)
        .closest('select').prop('disabled', true);
    } else {
      console.log('enable: ' + e.target.value);
      $(e.target).parents('div').first()
        .find('select').prop('disabled', false);
    }
  });


  $('.seeds tbody tr').on('click', function () {
    var seed_id = $(this).data('seed_id');
    window.open('/seedbank/myseeds?seed_id=' + seed_id, '_self')
  }).mouseover(function () {
    $(this).addClass('active');
  }).mouseout(function () {
    $(this).removeClass('active');
  });

  $('#newseed').on('click', function () {
    clearForm();
    $('#seed-preview').hide();
    $('form').show();
    initRegisterSeed();
    $("#modal").find('.modal-header h1').text(Lang.get('seedbank::messages.add_new_seed'));

    $('#modal').modal('show');
  });

  $('#modal').on('click', '#seed-preview button', function(){
    $('#seed-preview').hide();
    initRegisterSeed();
    $("#modal").find('.modal-header h1').prepend(Lang.get('seedbank::messages.change') + ' - ');
    $('form').show();
  });

  $('#cancel_seed').on('click', function () {
    $('#modal').modal('hide');
  });

  tinymce.init({
    selector: 'textarea',
    inline: false,
    menubar: 'tools',
    width: '100%',
    plugins: [ "placeholder" ],
    setup : function(ed)
    {
      ed.on('init', function()
          {
            this.getDoc().body.style.fontSize = '18px';
            $('.mce-toolbar-grp').hide();
            $('.mce-statusbar').hide();
            $(this.getBody()).on('blur', function() {
              $('.mce-toolbar-grp').hide();
              $('.mce-statusbar').hide();
            });
            $(this.getBody()).on('focus', function() {
              $('.mce-toolbar-grp').show();
              $('.mce-statusbar').show();
            });
          });
    }
  });

  var initRegisterSeed = function () {
    var deletebuttontext = Lang.get('seedbank::messages.delete');
    var addimagebutton = $('#files .col-md-4:last')[0];
    // Change this to the url of your server-side upload handler:
    var url = '/seedbank/add-pictures',
    deleteButton = $('<button/>')
      .addClass('btn btn-danger delete').prop('type', 'button')
      .text(deletebuttontext)
      .on('click', function () {
        var $this = $(this),
        file = $this.data().result.files[0];
        $.getJSON(file.deleteUrl, function (data){
          if (data.files[0][file.md5sum]){
            $this.closest('.col-md-4').remove();
            upload_counter = upload_counter - 1;
          }
        });
        $.each($('#files').children('.col-md-4').not('.processing'), function (index, elem){
          console.log($(elem));
          if (! $(elem).find('img').length) {
            if (! $(elem).find('.fileinput-button').length ) {
              $(elem).remove();
            }
          }
        });
        $this
          .off('click')
          .text('Abort')
          .on('click', function () {
            //$this.remove();
            data.abort();
          });
      });
    $('#fileupload').fileupload({
      url: url,
      dataType: 'json',
      autoUpload: true,
      acceptFileTypes: /(\.|\/)(gif|jpe?g|png|tif?f)$/i,
      maxFileSize: 5999000,
      maxNumberOfFiles: 5,
      getNumberOfFiles: function () {
        return $('#files').find('button.delete').length;
      },
      // Enable image resizing, except for Android and Opera,
      // which actually support image resizing, but fail to
      // send Blob objects via XHR requests:
      disableImageResize: /Android(?!.*Chrome)|Opera/
        .test(window.navigator.userAgent),
      previewMaxWidth: 150,
      previewMaxHeight: 150,
      previewCrop: true,
      //multipart: false
    }).on('fileuploadsubmit', function (e, data){
      var seed_id = $("form input[name='seed_id']").val();
      if (seed_id) {
        data.formData = {"seed_id": seed_id};
      } else {
        data.formData = {};
      }
    }).on('fileuploadadd', function (e, data) {
      let divcol = $('<div/>').addClass('col-md-4')
        .insertBefore(addimagebutton);
      $(divcol).append('<div class="img-content"></div>');
      data.context = $(divcol).children('.img-content');

    }).on('fileuploadprocessalways', function (e, data) {
      var index = data.index,
        file = data.files[index],
        node = $(data.context);
      if (file.preview) {
        node
          .prepend('<br>')
          .prepend(file.preview);
      }
      if (file.error) {
        node
          .append('<br>')
          .append($('<span class="text-danger"/>').text(file.error));
      }
      if (index + 1 === data.files.length) {
        data.context.find('button')
          .text('Upload')
          .prop('disabled', !!data.files.error);
      }
    }).on('fileuploadprogressall', function (e, data) {
      var progress = parseInt(data.loaded / data.total * 100, 10);
      $('#progress .progress-bar').css(
          'width',
          progress + '%'
          );
    }).on('fileuploaddone', function (e, data) {
      $.each(data.result.files, function (index, file) {
        if (file.url) {
          upload_counter += 1;
          $.each($('#files').children('.col-md-4'), function (index, elem) {
            var elem_img = $(elem).find('img');
            if (elem_img.length) {
              if ($(elem_img[0]).data('file-id') == file.id) {
                $(elem_img[0]).closest('.col-md-4').remove();
                upload_counter = upload_counter - 1;
                return false;
              }
            }
          });
          if (upload_counter > $("#fileupload").fileupload('option', 'maxNumberOfFiles')) {
            $(data.context.children()[index]).closest('.col-md-4').remove();
            $.getJSON(file.deleteUrl, function (){
              upload_counter = upload_counter - 1;
            });
            return false;
          }
          var hidden_input = '<input type="hidden" name="pictures_id[]" value="' + file.id + '">';
          var image = $('<img class="img-responsive" data-file-id="'
            + file.id + '" src="' + file.url + '" alt="' + file.label + '" />');
          data.context.empty().prepend(hidden_input).prepend(image).append(deleteButton.clone(true)
              .data(data));
        } else if (file.error) {
          var error = $('<span class="text-danger"/>').text(file.error);
          $(data.context.children()[index])
            .append('<br>')
            .append(error);
        }
      });
    }).on('fileuploadfail', function (e, data) {
      $.each(data.files, function (index) {
        var error = $('<span class="text-danger"/>').text('File upload failed.');
        $(data.context.children()[index])
          .append('<br>')
          .append(error);
      });
    }).prop('disabled', !$.support.fileInput)
    .parent().addClass($.support.fileInput ? undefined : 'disabled');
    var upload_counter = $('#files .col-md-4').length - 1;
  }
});
