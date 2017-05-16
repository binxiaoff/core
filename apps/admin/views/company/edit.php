<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1><?= $this->company->getName() ?></h1>
    <?php $this->fireview('blocks/identity'); ?>
    <?php $this->fireView('../bank_account/blocks/validated_bank_account'); ?>
    <?php $this->fireView('../bank_account/blocks/other_bank_account'); ?>
</div>
