<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1><?= $this->company->getName() ?></h1>
    <?php $this->fireview('blocks/identity'); ?>
    <?php if ($this->userEntity->getIdUserType()->getIdUserType() == \users_types::TYPE_RISK) : ?>
        <a class="btn-primary pull-right" href="<?= $this->lurl ?>/societe/notation/<?= $this->company->getIdCompany()?>">Suivi des notations</a>
    <?php endif; ?>
    <?php $this->fireView('../bank_account/blocks/validated_bank_account'); ?>
    <?php $this->fireView('../bank_account/blocks/other_bank_account'); ?>
</div>
