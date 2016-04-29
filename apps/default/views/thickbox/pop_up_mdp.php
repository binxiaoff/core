<div class="popup" style="width: 300px;height:300px;">
	<a href="#" class="popup-close">close</a>

	<div class="popup-head">
		<h2><?=$this->lng['pop-up-mdp']['mot-de-passe-oublie']?></h2>
	</div>

	<div class="popup-cnt" style="padding:10px;">
    	<p class="reponse_mdp_ok" style="font-size:12px;color:green;display:none;"><?=$this->lng['pop-up-mdp']['reponse-succes']?></p>
        <p class="reponse_mdp_nok" style="font-size:12px;color:#C84747;display:none;"><?=$this->lng['pop-up-mdp']['reponse-echec']?></p>
		<form action="" method="post" class="form_mdp_lost">
        <table border="1" style="margin:auto;">
			<tr style="height: 100px;">
            	<td style="vertical-align: middle; width: 60px;"><label><?=$this->lng['pop-up-mdp']['email']?> :</label></td>
            	<td style="vertical-align:middle;"><input name="email_mdp" id="email_mdp" type="text" title="" value="" class="field field-small"/></td>
			</tr>
            <tr>
            	<td colspan="2" style="text-align:center;">
        			<button type="submit" name="preter" class="btn btn-medium mdp_lost"><?=$this->lng['pop-up-mdp']['valider']?></button>
                </td>
            </tr>
        </table>
        </form>
	</div>
	<!-- /popup-cnt -->

</div>
<script>


$('.form_mdp_lost').submit(function(e) {
    e.preventDefault();
    sendRequest();
});


var sendRequest = function() {
    var email = $("#email_mdp").val();
    var val = {
        email: email
    }
    $.post(add_url + '/ajax/mdp_lost', val).done(function(data) {
        if (data == 'nok') {
            $(".reponse_mdp_nok").slideDown('slow');
            setTimeout(function() {
                $(".reponse_mdp_nok").slideUp('slow');
            }, 3000);
        } else {
            $(".reponse_mdp_ok").slideDown('slow');
            $(".form_mdp_lost").hide();
            setTimeout(function() {
                $(".popup-close").click();
            }, 4000);
        }
    });
}
</script>
