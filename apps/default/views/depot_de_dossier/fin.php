<div class="main">
    <div class="shell">
        <p><?= $this->sMessage ?></p>
        <?php if ($this->bDisplayContact) { ?>
            <?php $this->fireView(('../templates/contact_emprunteur')); ?>
        <?php } ?>
    </div>
</div>
