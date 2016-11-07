<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="add_emprunteur" id="add_emprunteur" enctype="multipart/form-data" action="<?=$this->lurl?>/emprunteurs/gestion" target="_parent">
        <h1>Ajouter un emprunteur</h1>
        <fieldset>
            <table class="formColor" style="width: 755px;">
            	<tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" class="input_large" value=""/></td>

                    <th><label for="prenom">Prénom :</label></th>
                    <td><input type="text" name="prenom" id="prenom" class="input_large" value=""/></td>
                </tr>
                <tr>
                    <th><label for="email">Email :</label></th>
                    <td><input type="text" name="email" id="email" class="input_large" value=""/></td>
                    <th><label for="telephone">Téléphone :</label></th>
                    <td><input type="text" name="telephone" id="telephone" class="input_large" value=""/></td>
                </tr>
                <tr>
                    <th><label for="societe">Société :</label></th>
                    <td><input type="text" name="societe" id="societe" class="input_large" value=""/></td>

                    <th><label for="secteur">Secteur :</label></th>
                    <td>
                    <select name="secteur" id="secteur" class="select">
                        <?
                        foreach ($this->lSecteurs as $section) {
                            ?>
                            <option value="<?= $section['id_company_sector'] ?>"><?= $this->translator->trans('company-sector_sector-' . $section['id_company_sector']) ?></option><?
                        }
                        ?>
                    </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="adresse">Adresse :</label></th>
                    <td colspan="3"><input type="text" name="adresse" id="adresse" style="width: 620px;" class="input_big" value=""/></td>
                </tr>
                <tr>
                    <th><label for="cp">Code postal :</label></th>
                    <td><input type="text" name="cp" id="cp" class="input_large" value=""/></td>

                    <th><label for="ville">Ville :</label></th>
                    <td><input type="text" name="ville" id="ville" class="input_large" value=""/></td>
                </tr>
                <tr>
                    <th><label for="cni_passeport">CNI/Passeport :</label></th>
                    <td><input type="file" name="cni_passeport" id="cni_passeport" value=""/></td>

                    <th><label for="signature">Signature :</label></th>
                    <td><input type="file" name="signature" id="signature" value=""/></td>
                </tr>
            	<tr>

                	<th colspan="4">
                        <input type="hidden" name="form_add_emprunteur" id="form_add_emprunteur" />
                        <input type="submit" value="Valider" title="Valider" name="send_add_emprunteur" id="send_add_emprunteur" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>
