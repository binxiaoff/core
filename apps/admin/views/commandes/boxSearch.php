<script type="text/javascript">
	$(document).ready(function(){
		$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
		$("#datepik_from").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
		$("#datepik_to").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
	});
</script>
<div id="popup" style="height:300px;">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="commande_recherche" id="commande_recherche" enctype="multipart/form-data" action="<?=$this->lurl?>/commandes/search" target="_parent">
        <h1>Rechercher une commande</h1>            
        <fieldset>
            <table class="formColor">
            	<tr>
                    <th><label for="reference">Réf. commande :</label></td>
                    <td><input type="text" name="reference" id="reference" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="datepik_from">Date début : </label></th>
                    <td><input type="text" name="from" id="datepik_from" class="input_dp" /></td>
              	</tr>
                <tr>
                    <th><label for="datepik_to">Date fin : </label></th>
                    <td><input type="text" name="to" id="datepik_to" class="input_dp" /></td>
                </tr>
            	<tr>
                    <th><label for="nom">Nom client :</label></td>
                    <td><input type="text" name="nom" id="nom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="prenom">Prénom client :</label></td>
                    <td><input type="text" name="prenom" id="prenom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="email">Email client :</label></td>
                    <td><input type="text" name="email" id="email" class="input_large" /></td>
                </tr>
                
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_search_cmd" id="form_search_cmd" />
                        <input type="submit" value="Valider" title="Valider" name="send_search" id="send_search" class="btn" />
                    </th>
                </tr>
            </tr>
        </table>
        </fieldset>
    </form>
</div>