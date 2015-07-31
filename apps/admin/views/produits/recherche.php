<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="search_produit" id="search_produit" enctype="multipart/form-data" action="<?=$this->lurl?>/produits" target="_parent">
        <h1>Rechercher un produit</h1>            
        <fieldset>
            <table class="formColor">
            <tr>
                <th><label for="s_name">Nom :</label></th>
                <td><input type="text" name="s_name" id="s_name" class="input_large" /></td>
            </tr>
            <tr>
                <th><label for="s_reference">Référence :</label></td>
                <td><input type="text" name="s_reference" id="s_reference" class="input_large" /></th>
            </tr>
            <tr>
                <th><label for="s_id_brand">Marque :</label></th>
                <td colspan="2">
                    <select name="id_brand" id="id_brand" class="select">
                        <option value="">Choisir une marque</option>
                        <?
                        foreach($this->lBrands as $b)
                        {
                            echo '<option value="'.$b['id_brand'].'">'.$b['name'].'</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
            	<td>&nbsp;</td>
                <th>
                    <input type="hidden" name="form_search_produit" id="form_search_produit" />
                    <input type="submit" value="Valider" title="Valider" name="send_form_search_produit" id="send_form_search_produit" class="btn" />
                </th>
            </tr>
        </table>
        </fieldset>
    </form>
</div>