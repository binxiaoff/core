<div id="popup">
    <h1>Création du transfert</h1>
    <p>Fonds restants : <?= $this->currencyFormatter->formatCurrency($this->restFunds, 'EUR'); ?></p>
    <form method="post" enctype="multipart/form-data" action="/dossiers/add_wire_transfer_out_lightbox/<?= $this->params[0] ?>/<?= $this->params[1] ?>">
        <table class="formColor">
            <tr>
                <th>Date transfert</th>
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
            <?php if (empty($this->project)) : ?>
            <tr>
                <th>
                    <label for="project">Projet</label>
                </th>
                <td>
                    <select id="project" class="input_large" name="project" required>
                        <option value="">Sélectionnez un projet concerné</option>
                        <?php
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount $bankAccount */
                        foreach ($this->projects as $project) : ?>
                            <option value="<?= $project->getIdProject() ?>"><?= $project->getTitle() ?> (<?= $project->getIdProject() ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php else : ?>
                <input type="hidden" name="project" value="<?= $this->project->getIdProject() ?>">
            <?php endif; ?>

            <tr>
                <th>
                    <label for="beneficiary">Bénéficiaire</label>
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
            <a href="javascript:parent.$.fn.colorbox.close()" class="btn btn_link btnDisabled">Annuler</a>
            <input type="submit" class="btn" value="Valider">
        </div>
    </form>
</div>
<script>
  $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

  $('#date').datepicker({
    minDate: 1,
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
