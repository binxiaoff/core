<h3>RIB en vigueur</h3>
<?php if ($this->bankAccount) : ?>
    <table class="tablesorter" style="width: 775px;margin:auto;">
        <tr>
            <td>Document</td>
            <td>
                <?php if ($this->bankAccount->getAttachment()) : ?>
                    <a href="<?= $this->url ?>/attachment/download/id/<?= $this->bankAccount->getAttachment()->getId() ?>/file/<?= urlencode($this->bankAccount->getAttachment()->getPath()) ?>">
                        <?= $this->bankAccount->getAttachment()->getOriginalName() ?? $this->bankAccount->getAttachment()->getPath() ?>
                    </a>
                <?php else : ?>
                    Aucun document fourni
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>IBAN</td>
            <td>
                <?= chunk_split($this->bankAccount->getIban(), 4, ' '); ?>
            </td>
        </tr>
        <tr>
            <td>BIC</td>
            <td><?= $this->bankAccount->getBic() ?></td>
        </tr>
    </table>
<?php else : ?>
    Aucun RIB en vigueur
<?php endif; ?>
<br><br>
