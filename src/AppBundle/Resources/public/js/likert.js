
$(document).ready(function() {
  // Add the OK button on every select
  $('#form>div').each(function(){
    if ($(this).find('select').length) {
      $(this).append('<button type="button">ok</button>');
    }
  });

  // Click the previously created button is like «next»
  $('button').click(function() {
    next();
  });

  // Changing an input that is not number => go next
  $('input').change(function() {
    if ($(this).attr('type') != 'number') {
      next();
    }
  });

  // click on a separator
  $('.likert.none').click(function() {
    next();
  });

  $('.nav .back').click(function() {
    back();
  });

  $('.nav .last').click(function() {
    last();
  });

  $('.nav .next').click(function() {
    next();
  });
  last();
});

function back() {
  var form = $('form');
  var dec = form.scrollLeft() % 302;
  dec = dec > 0 ? dec : 302;
  form.stop().animate({
    scrollLeft: form.scrollLeft() - dec
  }, 500);
}

function last() {
  var count = 0;
  var stop = false;
  var lastWasIntro = false;
  $('#form>div').each(function(){
    var item = ($(this));
    console.log(item);
    if (!stop && isItemValid(item)) {
      count = count + 1;
      lastWasIntro = item.find('.separator');
    } else {
      stop = true;
    }
  });
  if (lastWasIntro) count = count - 1;
  $('form').stop().animate({
    scrollLeft: count * 302
  }, 500);
}

function next() {
  var form = $('form');
  var count = Math.floor(form.scrollLeft() / 302) + 1;
  var item = ($('#form>div:nth-child(' + count + ')'));
  if (isItemValid(item)) {
    $.ajax({
      type        : form.attr('method'), // define the type of HTTP verb we want to use (POST for our form)
      url         : form.attr('action'), // the url where we want to POST
      data        : form.serialize(), // our data object
      dataType    : 'json', // what type of data do we expect back from the server
      encode      : true
    })
      .success(function(data) {
        alert(data);
      });
    var dec = 302 - form.scrollLeft() % 302;
    dec = dec > 0 ? dec : 302;
    form.stop().animate({
      scrollLeft: form.scrollLeft() + dec
    }, 500);
    item.removeClass('needed');
  } else {
    item.addClass('needed');
  }

}

function isItemValid(item) {
  if (item.find('select').length){
    isSelectInvalid = false;
    item.find('select').each(function(){
      isSelectInvalid = isSelectInvalid || ($(this).val() == "");
    })
  }

  return (
    item.find('.separator').length
    || (!item.find('.required').length)
    || item.find('input:checked').length
    || (item.find('input[type=text]').length && item.find('input').val() != "")
    || (item.find('input[type=number]').length && (/^[+]?[0-9]+$/.test(item.find('input').val())))
    || (item.find('select').length && !isSelectInvalid )
  );
}
