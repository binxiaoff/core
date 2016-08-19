// Lib Dependencies
var $ = require('jquery')

$('#password_forgotten').on('submit', function (e) {
    console.log('hello');
    e.preventDefault();

    $.ajax({
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: $(this).serialize,
        success: function (data) {
        console.log(data)
        },
        error: function(xhr, errorTxt){
        console.log('error');
        }

    })
})