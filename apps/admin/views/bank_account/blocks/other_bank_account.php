<h2>Autre RIBs</h2>
<table class="formColor" style="width: 775px;">
    <?php use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount; ?>
    <?php if (false === empty($this->bankAccountDocuments)) : ?>
        <?php foreach ($this->bankAccountDocuments as $attachment) : ?>
            <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount $bankAccount */ ?>
            <?php $bankAccount = $attachment->getBankAccount(); ?>
            <?php if (null === $bankAccount || BankAccount::STATUS_PENDING === $bankAccount->getStatus() || BankAccount::STATUS_ARCHIVED === $bankAccount->getStatus()) :  ?>
                <tr>
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
                            <a href="/bank_account/validate_rib_lightbox/<?= $bankAccount->getId(); ?>" class="btn_link thickbox cboxElement">Mettre en vigueur</a>
                        <?php else : ?>
                            <a href="/bank_account/extraction_rib_lightbox/<?= $attachment->getId(); ?>" class="btn_link extract_rib_btn">Extraire</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</table>
<br><br>
