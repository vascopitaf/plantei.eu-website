/*
 * from here https://gist.github.com/endel/dfe6bb2fbe679781948c
 */

function getMoonPhase(year, month, day)
{
    var c = e = jd = b = 0;
    if (month < 3) {
        year--;
        month += 12;
    }
    c = 365.25 * year;
    e = 30.6 * month;
    jd = c + e + day - 694039.09; //jd is total days elapsed
    jd /= 29.5305882; //divide by the moon cycle
    b = parseInt(jd); //int(jd) -> b, take integer part of jd
    jd -= b; //subtract integer part to leave fractional part of original jd
    b = Math.round(jd * 8); //scale fraction from 0-8 and round
    if (b >= 8 ) {
        b = 0; //0 and 8 are the same so turn 8 into 0
    }
    // 0 => New Moon
    // 1 => Waxing Crescent Moon
    // 2 => Quarter Moon
    // 3 => Waxing Gibbous Moon
    // 4 => Full Moon
    // 5 => Waning Gibbous Moon
    // 6 => Last Quarter Moon
    // 7 => Waning Crescent Moon
    b = b + 1; // for images names
    return "/images/moons/1f31" + b + ".svg"
}

/*
 * Clear all form data
 * element : form jquery element
 * keeplocation : boolean (if set 'element' must be too)
 */

var clearForm = function (element, keeplocation) {

  if (! element) {
    element = $('form');
  }
  if (! keeplocation ) {
    window.history.pushState({}, document.title, location.protocol
      + '//' + location.host + location.pathname);
  }

  element.find('input').not('[type=button], [type=checkbox], [type=submit], [type=reset], [type=radio], [name=_token]').val('');
  element.find('input[name=_save]').val('1');
  // element.find('textarea').text('');
  $('form').find('textarea').each(function (i) {
    if (tinymce.activeEditor) {
      tinymce.activeEditor.setContent("");
    }
    $(this).val("");
  });
  element.find('select').prop('disabled', false);
  element.find('span[data-toggle=buttons]').each(function (i,e) {
    $(e).find('label.btn').removeClass('active');
    $(e).find('input[type=radio], input[type=checkbox]').prop('checked', false);
  });
  $('.row.validationErrors').empty();

  /* empty image thumbnails */
  element.find('.files .col-md-4').each(function(i, e){
    if ( $(e).find('img').length ) {
      if (! $(e).find('.fileinput-button').length ) {
        $(e).remove();
      }
    }
  });

}

/*
 * Get parameters from  urls
 */
function findGetParameter(parameterName) {
  // https://stackoverflow.com/a/5448595
  var result = null,
    tmp = [];
  var items = location.search.substr(1).split("&");
  for (var index = 0; index < items.length; index++) {
    tmp = items[index].split("=");
    if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
  }
  return result;
};

function formErrors(errors, $form)
{

  let errorsDiv = $('.row.validationErrors').empty();
  $.each(errors, function (key, value) {
    errorsDiv.append('<div class="alert alert-danger col-sm-6 col-sm-offset-3" role="alert">' + value[0] + '</div>');
  });
  $('#modal').animate({ scrollTop: 0});
}
