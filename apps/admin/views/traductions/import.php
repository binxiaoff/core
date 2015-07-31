<div id="popup" style="background-color:#FFF;">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="import_csv" id="import_csv" enctype="multipart/form-data" action="<?=$this->lurl?>/traductions" target="_parent">
        <h1>Importer le fichier de traduction</h1>            
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="csv">Fichier CSV :</label></th>
                    <td><input type="file" name="csv" id="csv" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_import_traduction" id="form_import_traduction" />
                        <input type="submit" value="Valider" name="send_traduction" id="send_traduction" class="btn" />
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>