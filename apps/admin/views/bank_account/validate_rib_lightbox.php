<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <h2>Êtes vous certain de vouloir mettre en vigueur ce RIB ?</h2>
    <?php if ($this->bankAccount) : ?>
        <form method="post" enctype="multipart/form-data" action="/emprunteurs/validate_rib">
            <table class="formColor" style="width: 775px;margin:auto;">
                <tr>
                    <th>IBAN</th>
                    <td><?= $this->bankAccount->getIban() ?></td>
                </tr>
                <tr>
                    <th>BIC</th>
                    <td><?= $this->bankAccount->getBic() ?></td>
                </tr>
            </table>
            <input type="hidden" name="id_bank_account" value="<?= $this->bankAccount->getId() ?>">
            <div style="margin-top: 15px; text-align: right">
                <a href="javascript:parent.$.fn.colorbox.close()" class="btn-default">Annuler</a>
                <button type="submit" class="btn-primary">Valider</button>
            </div>
        </form>
    <?php endif; ?>
</div>
