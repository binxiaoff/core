<div id="contenu">
    <h1>Modifier <?= $this->mailTemplate->getType() ?></h1>
    <form method="post" action="<?= $this->lurl ?>/mails/edit/<?= $this->mailTemplate->getType() ?>">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="sender-name">Nom expéditeur</label>
                <input type="text" value="<?= $this->mailTemplate->getSenderName() ?>" id="sender-name" name="sender_name" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="sender-email">Email expéditeur</label>
                <input type="text" value="<?= $this->mailTemplate->getSenderEmail() ?>" id="sender-email" name="sender_email" class="form-control">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <label for="subject">Sujet</label>
                <input type="text" value="<?= $this->mailTemplate->getSubject() ?>" id="subject" name="subject" class="form-control">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" class="form-control input-sm" rows="20" style="font-family: Courier New, monospace; width: 100%;"><?= htmlentities($this->mailTemplate->getContent(), ENT_COMPAT, 'UTF-8') ?></textarea>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <button type="submit" class="btn-primary pull-right">Valider</button>
                <button type="button" id="preview-button" class="btn-default pull-right" style="margin-right: 5px;">Prévisualiser</button>
            </div>
        </div>
    </form>
</div>
<?php $this->fireView('preview'); ?>
