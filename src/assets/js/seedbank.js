$(function () {
  $('#seeds tr').on('click', function () {
    var seed_id = $(this).data('seed_id');
    window.open('/seedbank/allseeds?seed_id=' + seed_id, '_self')
  }).mouseover(function () {
    $(this).addClass('active');
  }).mouseout(function () {
    $(this).removeClass('active');
  });

  $('#mySeeds tr').on('click', function () {
    var seed_id = $(this).data('seed_id');
    window.open('/seedbank/myseeds?seed_id=' + seed_id, '_self')
  }).mouseover(function () {
    $(this).addClass('active');
  }).mouseout(function () {
    $(this).removeClass('active');
  });

  $('.listEvents').on('click', 'li', function (e) {
    $.get('/events/get/' + $(this).data('id'), function (data) {
      if (! data ) { return false;}
      $(".modal-content").html(data);
      $('#info_modal').modal('show');
    });
  });

  $('#messages tr').on('click', function () {
      var messageId = $(this).data('message_id') || '';
      window.open('/messages/?message_id=' + messageId, '_self');
    }).mouseover(function () {
      $(this).addClass('active');
    }).mouseout( function () {
      $(this).removeClass('active');
    });

  $('#calendar').fullCalendar({
    // put your options and callbacks here
    defaultView: 'month',
    header: false,
    themeSystem: 'bootstrap3',
    //contentHeight: 400,
    lang: lang,
    timeFormat: 'HH:mm',
    eventClick: function(calEvent, jsEvent, view) {
      window.open('/events?id=' + calEvent.id, '_self')
    },
    eventSources: [
      {
        url: '/api/calendar',
        type: 'POST',
        color: 'yellow',
        textColor: 'black'
      }
    ],
  });
});