<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/produits" title="Boutique">Boutique</a> -</li>
        <li><a href="<?=$this->lurl?>/temproduits" title="Templates Produits">Templates Produits</a> -</li>
        <li>Edition du template</li>
    </ul>
    <h1>Edition du template <?=$this->templates->name?></h1>
    <form method="post" name="edition_template" id="edition_template" enctype="multipart/form-data">
        <fieldset>
            <table class="large">
                <tr>
                    <td><textarea name="filecontent" id="filecontent" class="textarea_big"><?=$this->edit?></textarea></td>
                </tr>
                <tr>
                    <td>
                        <input type="hidden" name="form_edition_template" id="form_edition_template" />
                        <input type="submit" value="Valider" name="edit_template" id="edit_template" class="btn" />
                    </td>
                </tr>
            </table>
        </fieldset>
 	</form>
</div>