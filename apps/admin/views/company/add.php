<script type="text/javascript">
  $(function() {
    $(".listeProjets").tablesorter({headers: {4: {sorter: false}, 5: {sorter: false}}});
    $(".listeMandats").tablesorter();
    $(".mandats").tablesorter({headers: {}});

      <?php if ($this->nb_lignes != '') : ?>
    $(".listeProjets").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
    $(".mandats").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
      <?php endif; ?>
  });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Création de société</h1>
    <form method="post" name="edit_company" id="edit_company" enctype="multipart/form-data" action="/company/add" target="_parent">
        <table class="formColor" style="width: 775px;margin:auto;">
            <tr>
                <th><label for="email_facture">SIREN*</label></th>
                <td colspan="3"><input type="text" name="siren" id="siren" class="input_large" value="<?= $this->siren ?>" required></td>
            </tr>
            <tr>
                <th><label for="corporate_name">Raison sociale*</label></th>
                <td><input type="text" name="corporate_name" id="corporate_name" class="input_large" required></td>
                <th><label for="sector">Secteur</label></th>
                <td>
                    <select name="sector" id="sector" class="select">
                        <option value=""></option>
                        <?php
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\CompanySector $sector */
                        foreach ($this->sectors as $sector) : ?>
                            <option value="<?= $sector->getIdCompanySector() ?>">
                                <?= $this->translator->trans('company-sector_sector-' . $sector->getIdCompanySector()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="nom">Nom*</label></th>
                <td><input type="text" name="nom" id="nom" class="input_large" required></td>
                <th><label for="prenom">Prénom*</label></th>
                <td><input type="text" name="prenom" id="prenom" class="input_large" required></td>
            </tr>
            <tr>
                <th><label for="email">Email*</label></th>
                <td><input type="text" name="email" id="email" class="input_large" required></td>
                <th><label for="telephone">Téléphone*</label></th>
                <td><input type="text" name="telephone" id="telephone" class="input_large" required></td>
            </tr>
            <tr>
                <th><label for="adresse">Adresse*</label></th>
                <td colspan="3"><input type="text" name="adresse" id="adresse" style="width: 610px;" class="input_big" required></td>
            </tr>
            <tr>
                <th><label for="cp">Code postal*</label></th>
                <td><input type="text" name="cp" id="cp" class="input_large" required></td>
                <th><label for="ville">Ville*</label></th>
                <td><input type="text" name="ville" id="ville" class="input_large" required></td>
            </tr>
            <tr>
                <th><label for="email_facture">Email de facturation</label></th>
                <td colspan="3"><input type="text" name="email_facture" id="email_facture" class="input_large"></td>
            </tr>
            <tr>
                <th><label for="iban1">IBAN</label></th>
                <td colspan="3">
                    <input type="text" name="iban1" id="iban1" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban2" id="iban2" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban3" id="iban3" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban4" id="iban4" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban5" id="iban5" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban6" id="iban6" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban7" id="iban7" onkeyup="jumpIBAN(this)" style="width: 53px;" size="3" class="input_big">
                </td>
            </tr>
            <tr>
                <th><label for="bic">BIC</label></th>
                <td><input type="text" name="bic" id="bic" class="input_large"></td>
                <th><label for="rib">RIB</label></th>
                <td><input type="file" name="rib" id="rib" class="input_large"></td>
            </tr>
            <tr>
                <th><label for="kbis">KBIS</label></th>
                <td colspan="3"><input type="file" name="kbis" id="kbis" class="input_large"></td>
            </tr>
            <tr>
                <th colspan="4">
                    <input type="submit" value="Valider" title="Valider" class="btn" />
                </th>
            </tr>
        </table>
    </form>
    <br /><br />
    * Champ obligatoire
</div>
