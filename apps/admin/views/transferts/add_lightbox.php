<div id="popup">
    <h1>Création du transfert</h1>
    <form method="post" enctype="multipart/form-data" action="/transferts/add_lightbox/<?= $this->project->getIdProject() ?>">
        <table class="formColor">
            <tr>
                <th>Mode transfert</th>
                <td>
                    <input type="radio" name="mode" id="mode_immediate" value="0" checked>
                    <label for="mode_immediate">Dés validation</label>
                    <input type="radio" name="mode" id="mode_delayed" value="1">
                    <label for="mode_delayed">Différé</label>
                </td>
            </tr>
            <tr style="visibility: hidden;" id="date_picker">
                <th>
                    <label for="date">Date transfert</label>
                </th>
                <td>
                    <input type="text" name="date" id="date" class="input_dp">
                </td>
            </tr>
            <tr>
                <th>
                    <label for="pattern">Motif</label>
                </th>
                <td>
                    <input type="text" name="pattern" id="pattern" value="<?= $this->borrowerMotif ?>" class="input_large" required>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="beneficiary">Bénéficiaire:</label>
                </th>
                <td>
                    <select id="bank_account" class="input_large" name="bank_account">
                        <?php
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount $bankAccount */
                        foreach ($this->bankAccounts as $bankAccount) :
                            $beneficiaryCompany = $this->companyRepository->findOneBy(['idClientOwner' => $bankAccount->getIdClient()->getIdClient()]);
                        ?>
                            <option value="<?= $bankAccount->getId() ?>"><?= $beneficiaryCompany->getName() ?> (<?= $bankAccount->getIban() ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="amount">Montant</label>
                </th>
                <td>
                    <input type="text" name="amount" id="amount" class="input_large" required> €
                </td>
            </tr>
        </table>
        <div style="text-align: center">
            <input type="submit" class="btn" value="Valider">
            <a href="javascript:parent.$.fn.colorbox.close()" class="btn btn_link btnDisabled">Annuler</a>
        </div>
    </form>
</div>
<script>
  $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

  $('#date').datepicker({
    showOn: 'both',
    buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
    buttonImageOnly: true,
    changeMonth: true,
    changeYear: true,
    yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'
  });

  $('#mode_delayed').click(function () {
    $('#date_picker').css('visibility', 'visible');
  })
  $('#mode_immediate').click(function () {
    $('#date').val('');
    $('#date_picker').css('visibility', 'hidden');
  })
</script>
