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

let emptyUse = $('.row.uses').children('.row.item');

$('form').on('submit', function (){
  element = $('input[name="common_name"]');
  if (! element.val()) {
    $('#identification').collapse('show');
    element.closest('.form-group').addClass('has-error');
    element.next('.help-block').text('This field is the only field required');
    element.focus();
    return false;
  }
  tinymce.triggerSave();
  var formdata = $(this).serializeArray();
  $.post('/api/enciclopedia', formdata, function(data) {

    if (! data.errors ) {
      window.location = location.protocol + "//" + location.host + location.pathname +
        "?id=" + data.id;
    }

    //console.log(data);
    previewseed(parameters, data);

    $("form").hide();
    $("#seed-preview").show();
  });
  return false;
});

$( function () {
  $('button.new-encicl').css({'margin-right': '32px','margin-top': '16px'}).on('click', function (e) {
    $('#modal form').show();
    clearForm();
    $('#encicl-preview').hide();
    $('#modal').modal('show');
  });

  $('#uses .row.buttons button').on('click', function (e) {
    let len = $('#uses .row.uses').children().length;
    console.log("pressed new length: " + len);
    let row = emptyUse.clone(true);
    row.find('select').attr('name', 'uses[' + (len - 1) + '][category]');
    row.find('textarea').attr('name', 'uses[' + (len - 1) + '][description]')
      .addClass('editor');
    row.find('input[type=text]').attr('name', 'uses[' + (len - 1) + '][title]');
    row.find('input[type=hidden]').attr('name', 'uses[' + (len - 1) + '][id]');
    row.show();
    /*let categoryDiv = document.createElement('div');
    categoryDiv.setAttribute('class', 'form-group');
    let categorySelect = document.createElement('select');
    categorySelect.setAttribute('name', 'uses[' + len + '][category]');
    use_categories.forEach(function (category, index) {
      categorySelect.options.add(new Option(category, index));
    });
    categoryDiv.appendChild(categorySelect);

    let useTextDiv = document.createElement('div');
    useTextDiv.setAttribute('class', 'form-group');
    let useTextSpan = document.createElement('span');
    useTextSpan.setAttribute('class', 'cell');
    let useTextTextarea = document.createElement('textarea');
    useTextTextarea.setAttribute('class', 'form-control');
    useTextTextarea.setAttribute('name', 'uses[' + len + '][description]');
    useTextSpan.appendChild(useTextTextarea);
    useTextDiv.appendChild(useTextSpan);

    let newUseDiv = document.createElement('div');
    newUseDiv.setAttribute('class', 'row');
    newUseDiv.appendChild(categoryDiv);
    newUseDiv.appendChild(useTextDiv);*/

    $('#uses .row.uses').append(row);

  });


  $('.row.enciclopedia.content .col-sm-4 > div ').on('click', function () {
    var seed_id = $(this).data('seed_id');
    window.open('/enciclopedia?id=' + seed_id, '_self')

    // $.get("/encicplopedia/seedbank/seedm/" + seed_id, function (data) {
    //   if (data.length == 0){
    //     return false;
    //   }
    //   clearform();
    //   populateform(parameters, data);
    //   initRegisterSeed();
    //   previewseed(parameters, data);
    //   $("#modal").find('.modal-title').text(data.common_name);
    //   $('#seed-preview').show();
    //   $('form').hide();
    //
    //   $('#modal').modal('show');
    // });
  }).mouseover(function () {
    $(this).addClass('active');
  }).mouseout(function () {
    $(this).removeClass('active');
  });


});
