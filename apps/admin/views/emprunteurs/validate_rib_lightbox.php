<div id="popup">
    <h2>Êtes vous certain de vouloir mettre en vigueur ce RIB ?</h2>
    <?php if ($this->bankAccount) : ?>
        <form method="post" enctype="multipart/form-data" action="/emprunteurs/validate_rib">
            <table class="formColor" style="width: 775px;margin:auto;">
                <tr>
                    <th>IBAN :</th>
                    <td><?= $this->bankAccount->getIban() ?></td>
                </tr>
                <tr>
                    <th>BIC :</th>
                    <td><?= $this->bankAccount->getBic() ?></td>
                </tr>
            </table>
            <input type="hidden" name="id_bank_account" value="<?= $this->bankAccount->getId() ?>">
            <center>
                <input type="submit" class="btn" value="Valider">
                <button onclick="parent.$.fn.colorbox.close();" class='btn' style="margin-left:15px;">Fermer</button>
            </center>
        </form>
    <?php endif; ?>
</div>
