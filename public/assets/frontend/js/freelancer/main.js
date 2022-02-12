(function($) {

  $('#specialty').parent().append('<ul class="list-item" id="newspecialty" name="specialty"></ul>');
  $('#specialty option').each(function(){
      $('#newspecialty').append('<li value="' + $(this).val() + '">'+$(this).text()+'</li>');
  });
  $('#specialty').remove();
  $('#newspecialty').attr('id', 'specialty');
  $('#specialty li').first().addClass('init');
  $("#specialty").on("click", ".init", function() {
      $(this).closest("#specialty").children('li:not(.init)').toggle();
  });
  
  var allOptions = $("#specialty").children('li:not(.init)');
  $("#specialty").on("click", "li:not(.init)", function() {
      allOptions.removeClass('selected');
      $(this).addClass('selected');
      $("#specialty").children('.init').html($(this).html());
      allOptions.toggle();
  });

 
  $('#reset').on('click', function(){
      $('#register-form').reset();
  });

  $('#register-form').validate({
    rules : {
        name : {
            required: true,
        },
        username : {
            required: true,
        },
        resume: {
            required: true
        },
        email : {
            required: true,
            email : true
        },
        password : {
            required: true,
        },
        password_confirmation : {
            required: true,
        },
        phone: {
            required: true,
        }
    },
    onfocusout: function(element) {
        $(element).valid();
    },
});

    jQuery.extend(jQuery.validator.messages, {
        required: "",
        remote: "",
        email: "",
        url: "",
        date: "",
        dateISO: "",
        number: "",
        digits: "",
        creditcard: "",
        equalTo: ""
    });
})(jQuery);