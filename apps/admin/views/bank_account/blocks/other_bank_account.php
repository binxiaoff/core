<?php use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount; ?>
<h2>Autre RIBs</h2>
<?php if (false === empty($this->bankAccountDocuments)) : ?>
    <table class="tablesorter">
        <thead>
        <tr>
            <th>Document</th>
            <th>IBAN</th>
            <th>BIC</th>
            <th>ACTION</th>
        </tr>
        </thead>
        <tbody>
        <?php $i = 0; ?>
        <?php foreach ($this->bankAccountDocuments as $attachment) : ?>
            <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount $bankAccount */ ?>
            <?php $bankAccount = $attachment->getBankAccount(); ?>
            <?php if (null === $bankAccount || BankAccount::STATUS_PENDING === $bankAccount->getStatus() || BankAccount::STATUS_ARCHIVED === $bankAccount->getStatus()) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td style="padding-bottom:20px">
                        <a href="<?= $this->url ?>/attachment/download/id/<?= $attachment->getId() ?>/file/<?= urlencode($attachment->getPath()) ?>"><?= $attachment->getPath() ?></a>
                    </td>
                    <td>
                        <?php if ($bankAccount) : ?>
                            <?= chunk_split($bankAccount->getIban(), 4, ' '); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($bankAccount) : ?>
                            <?= $bankAccount->getBic(); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($bankAccount) : ?>
                            <a href="/bank_account/validate_rib_lightbox/<?= $bankAccount->getId(); ?>" class="thickbox cboxElement">Mettre en vigueur</a>
                        <?php else : ?>
                            <a href="/bank_account/extraction_rib_lightbox/<?= $attachment->getId(); ?>" class="extract_rib_btn">Extraire</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php ++$i; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
<?php else : ?>
    Pas de RIB.
<?php endif; ?>
<br><br>
