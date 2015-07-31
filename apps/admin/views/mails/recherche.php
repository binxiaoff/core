<script type="text/javascript">
	$(document).ready(function(){
		$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
		$("#datepik_from").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
		$("#datepik_to").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
	});
</script>
<div id="popup" style="height:260px;">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="add_recherche" id="add_recherche" enctype="multipart/form-data" action="<?=$this->lurl?>/mails/logs" target="_parent">
        <h1>Recherche</h1>            
        <fieldset>
            <table class="formColor">
            	<tr>
                    <th><label for="from">From :</label></td>
                    <td><input type="text" name="from" id="from" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="to">To :</label></td>
                    <td><input type="text" name="to" id="to" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="subject">Sujet :</label></td>
                    <td><input type="text" name="subject" id="subject" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="date_from">Entre : </label></th>
                    <td><input type="text" name="date_from" id="datepik_from" class="input_dp" /></td>
              	</tr>
                <tr>
                    <th><label for="date_to"> Et : </label></th>
                    <td><input type="text" name="date_to" id="datepik_to" class="input_dp" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_send_search" id="form_send_search" />
                        <input type="submit" value="Valider" title="Valider" name="send_settings" id="send_settings" class="btn" />
                    </th>
                </tr>
            </tr>
        </table>
        </fieldset>
    </form>
</div>