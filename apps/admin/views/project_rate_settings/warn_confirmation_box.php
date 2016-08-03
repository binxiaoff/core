<div class="mythickbox">
    <h1>Voulez-vous envoyer les mails de notifications aux prêteurs concernés ?</h1>
    <div style="text-align: center">
        <button class="btn" id="valid-send-mail-notification">OK</button>
        <button class="btn" onclick="$.colorbox.close()" style="border-color: #A1A5A7;background-color:#A1A5A7; color:white;">Cancel</button>
    </div>
</div>

<script>
    $('#valid-send-mail-notification').click(function () {
        $('.mythickbox').html(
            '<h1>L\'envoi des notifications est en cours...</h1>'+
            '<div style="text-align: center">' +
            '<img src="https://www.local.unilend.fr/scripts/admin/external/jquery/plugin/colorbox/images/loading.gif">' +
            '</div>');
        $.ajax({
            url: 'project_rate_settings/warn_lender_autolend_settings',
            method: 'GET',
            async: false
        })
            .done(function(){
                $('.mythickbox').html('<h1>Notifications envoyé</h1>')
            });
    });
</script>