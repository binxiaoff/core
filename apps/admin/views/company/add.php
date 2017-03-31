<script type="text/javascript">
  $(function () {
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
    <form method="post" name="edit_company" id="edit_company" enctype="multipart/form-data" action="company/add" target="_parent">
        <table class="formColor" style="width: 775px;margin:auto;">
            <tr>
                <th><label for="email_facture">SIREN*</label></th>
                <td><input type="text" name="siren" id="siren" class="input_large" value="<?= $this->siren ?>" required></td>
                <td colspan="2">
                    <button name="fetch" id="fetch" class="btn">Remplir les champs par WS externe</button>
                </td>
            </tr>
            <tr>
                <th><label for="corporate_name">Raison sociale*</label></th>
                <td colspan="2"><input type="text" name="corporate_name" id="corporate_name" class="input_large" required></td>
            </tr>
            <tr>
                <th>Civilite :</th>
                <td colspan="3">
                    <input type="radio" name="title" id="title1" value="Mme"><label for="civilite1">Madame</label>
                    <input type="radio" name="title" id="title2" checked value="M."><label for="civilite2">Monsieur</label>
                </td>
            </tr>
            <tr>
                <th><label for="name">Nom*</label></th>
                <td><input type="text" name="name" id="name" class="input_large" required></td>
                <th><label for="firstname">Prénom*</label></th>
                <td><input type="text" name="firstname" id="firstname" class="input_large" required></td>
            </tr>
            <tr>
                <th><label for="email">Email*</label></th>
                <td><input type="text" name="email" id="email" class="input_large" required></td>
                <th><label for="phone">Téléphone*</label></th>
                <td><input type="text" name="phone" id="phone" class="input_large" required></td>
            </tr>
            <tr>
                <th><label for="address">Adresse*</label></th>
                <td colspan="3"><input type="text" name="address" id="address" style="width: 610px;" class="input_big" required></td>
            </tr>
            <tr>
                <th><label for="postCode">Code postal*</label></th>
                <td><input type="text" name="postCode" id="postCode" class="input_large" required></td>
                <th><label for="city">Ville*</label></th>
                <td><input type="text" name="city" id="city" class="input_large" required></td>
            </tr>
            <tr>
                <th><label for="invoice_email">Email de facturation</label></th>
                <td colspan="3"><input type="text" name="invoice_email" id="invoice_email" class="input_large"></td>
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
                    <input type="submit" value="Valider" title="Valider" class="btn"/>
                </th>
            </tr>
        </table>
    </form>
    <br/><br/>
    * Champ obligatoire
</div>
<script>
  $('#fetch').click(function (event) {
    event.preventDefault();
    var siren = $('input#siren').val();
    if (0 === siren.length) {
      alert('Merci de saisir le SIREN');
    } else {
      $.ajax({
        method: 'GET',
        url: '/company/fetch_details_ajax/' + siren,
        dataType: 'json'
      }).done(function (companyIdentity) {
        $('#corporate_name').val(companyIdentity.corporateName);
        $('#name').val(companyIdentity.ownerName);
        $('#firstname').val(companyIdentity.ownerFirstName);
        $('#phone').val(companyIdentity.phoneNumber);
        $('#address').val(companyIdentity.address);
        $('#postCode').val(companyIdentity.postCode);
        $('#city').val(companyIdentity.city);
        if ('Mme' === companyIdentity.title) {
          $("#title1").prop("checked", true)
        }
      })
    }

  })
</script>
