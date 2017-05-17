<?php
/** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients client */
/** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies company */
?>
<form method="post" name="edit_company" id="edit_company" enctype="multipart/form-data">
    <table class="formColor" style="width: 775px;margin:auto;">
        <tr>
            <th><label for="siren">SIREN*</label></th>
            <td><input type="text" name="siren" id="siren" class="input_large" value="<?= $this->siren ?>" required></td>
            <td colspan="2">
                <?php if (null === $this->client->getNom()) : ?>
                    <button name="fetch" id="fetch" class="btn">Remplir les champs par WS externe</button>
                <? endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="corporate_name">Raison sociale*</label></th>
            <td colspan="2"><input type="text" name="corporate_name" id="corporate_name" class="input_large" value="<?= $this->company->getName() ?>" required></td>
        </tr>
        <tr>
            <th>Civilite</th>
            <td colspan="3">
                <input type="radio" name="title" id="title1" <?= $this->client->getCivilite() === 'Mme' ? 'checked' : '' ?> value="Mme"><label for="civilite1">Madame</label>
                <input type="radio" name="title" id="title2" <?= $this->client->getCivilite() === 'M.' ? 'checked' : '' ?> value="M."><label for="civilite2">Monsieur</label>
            </td>
        </tr>
        <tr>
            <th><label for="name">Nom*</label></th>
            <td><input type="text" name="name" id="name" class="input_large" value="<?= $this->client->getNom() ?>" required></td>
            <th><label for="firstName">Prénom*</label></th>
            <td><input type="text" name="firstName" id="firstName" class="input_large" value="<?= $this->client->getPrenom() ?>" required></td>
        </tr>
        <tr>
            <th><label for="email">Email*</label></th>
            <td><input type="text" name="email" id="email" class="input_large" value="<?= $this->client->getEmail() ?>" required></td>
            <th><label for="phone">Téléphone*</label></th>
            <td><input type="text" name="phone" id="phone" class="input_large" value="<?= $this->company->getPhone() ?>" required></td>
        </tr>
        <tr>
            <th><label for="address">Adresse*</label></th>
            <td colspan="3"><input type="text" name="address" id="address" style="width: 610px;" class="input_big" value="<?= $this->company->getAdresse1() ?>" required></td>
        </tr>
        <tr>
            <th><label for="postCode">Code postal*</label></th>
            <td><input type="text" name="postCode" id="postCode" class="input_large" value="<?= $this->company->getZip() ?>" required></td>
            <th><label for="city">Ville*</label></th>
            <td><input type="text" name="city" id="city" class="input_large" value="<?= $this->company->getCity() ?>" required></td>
        </tr>
        <tr>
            <th><label for="invoice_email">Email de facturation</label></th>
            <td colspan="3"><input type="text" name="invoice_email" id="invoice_email" class="input_large" value="<?= $this->company->getEmailFacture() ?>"></td>
        </tr>
        <tr>
            <th><label for="rib">RIB<?php if (! $this->company->getName()) : ?>*<?php endif;?></label></th>
            <td colspan="3"><input type="file" name="rib" id="rib" <?php if (! $this->company->getName()) : ?>required<?php endif;?></td>
        </tr>
        <tr>
            <th><label for="kbis">KBIS<?php if (! $this->company->getName()) : ?>*<?php endif;?></label></th>
            <td colspan="3"><input type="file" name="kbis" id="kbis" <?php if (! $this->company->getName()) : ?>required<?php endif;?>></td>
        </tr>
        <tr>
            <th colspan="4">
                <input type="submit" value="Valider" title="Valider" class="btn"/>
            </th>
        </tr>
    </table>
</form>
<br/><br/>
* Champs obligatoires
<br/><br/>
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
        $('#firstName').val(companyIdentity.ownerFirstName);
        $('#phone').val(companyIdentity.phoneNumber);
        $('#address').val(companyIdentity.address);
        $('#postCode').val(companyIdentity.postCode);
        $('#city').val(companyIdentity.city);
        if ('Mme' === companyIdentity.title) {
          $("#title1").prop("checked", true)
        } else {
          $("#title2").prop("checked", true)
        }
      })
    }

  })
</script>
