<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <h2>Êtes vous certain de vouloir mettre en vigueur ce RIB ?</h2>
    <?php if ($this->bankAccount) : ?>
        <form method="post" enctype="multipart/form-data" action="/bank_account/validate_rib">
            <table class="formColor" style="width: 775px;margin:auto;">
                <tr>
                    <th>IBAN</th>
                    <td><?= chunk_split($this->bankAccount->getIban(), 4, ' ') ?></td>
                </tr>
                <tr>
                    <th>BIC</th>
                    <td><?= $this->bankAccount->getBic() ?></td>
                </tr>
            </table>
            <input type="hidden" name="id_bank_account" value="<?= $this->bankAccount->getId() ?>">
            <div style="text-align: center">
                <button onclick="parent.$.fn.colorbox.close();" class='btn btn_link btnDisabled' style="margin-left:15px;">Annuler</button>
                <input type="submit" class="btn" value="Valider">
            </div>
        </form>
    <?php endif; ?>
</div>
