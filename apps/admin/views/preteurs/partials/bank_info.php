<h2>Informations bancaires</h2>
<table class="form" style="margin: auto;">
    <tr>
        <th>BIC :</th>
        <td><?= (null !== $this->currentBankAccount) ? $this->currentBankAccount->getBic() : '' ?></td>
    </tr>
    <tr>
        <th>IBAN :</th>
        <td><?= (null !== $this->currentBankAccount) ? chunk_split($this->currentBankAccount->getIban(), 4, ' ') : '' ?></td>
    </tr>
    <?php if ($this->origine_fonds[0] != false) : ?>
        <tr class="particulier">
            <th colspan="2" style="text-align:left;"><label for="origine_des_fonds">Quelle est l'origine des fonds que vous déposez sur Unilend ?</label></th>
        </tr>
        <tr class="particulier">
            <td colspan="2">
                <select name="origine_des_fonds" id="origine_des_fonds" class="select" disabled>
                    <option value="0">Choisir</option>
                    <?php foreach ($this->origine_fonds as $k => $origine_fonds) : ?>
                        <option <?= ($this->client->getFundsOrigin() == $k + 1 ? 'selected' : '') ?> value="<?= $k + 1 ?>" ><?= $origine_fonds ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    <?php endif; ?>
</table>