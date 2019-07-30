<div id="contenu">
    <h1>Modifier <?= $this->mailTemplate->getType() ?></h1>
    <form method="post" action="<?= $this->url ?>/mails/edit/<?= $this->mailTemplate->getType() ?><?= isset($this->params[1]) ? '/' . $this->params[1] : '' ?>">
        <?php if (\Unilend\Entity\MailTemplates::PART_TYPE_CONTENT === $this->mailTemplate->getPart()) : ?>
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
                <div class="form-group col-md-6">
                    <label for="subject">Sujet</label>
                    <input type="text" value="<?= $this->mailTemplate->getSubject() ?>" id="subject" name="subject" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label for="title">Titre</label>
                    <input type="text" value="<?= $this->mailTitle ?>" id="title" name="title" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="header-select">Header</label>
                    <select id="header-select" name="header" class="form-control">
                        <option value=""></option>
                        <?php foreach ($this->headers as $header) : ?>
                            <option value="<?= $header->getIdMailTemplate() ?>"<?= $this->mailTemplate->getIdHeader() === $header ? ' selected' : '' ?>><?= $header->getType() ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="footer-select">Footer</label>
                    <select id="footer-select" name="footer" class="form-control">
                        <option value=""></option>
                        <?php foreach ($this->footers as $footer) : ?>
                            <option value="<?= $footer->getIdMailTemplate() ?>"<?= $this->mailTemplate->getIdFooter() === $footer ? ' selected' : '' ?>><?= $footer->getType() ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="recipient-type">Usage</label>
                    <select id="recipient-type" name="recipient_type" class="form-control">
                        <option value="external"<?= $this->mailTemplate->getRecipientType() === \Unilend\Entity\MailTemplates::RECIPIENT_TYPE_EXTERNAL ? ' selected' : '' ?>>Externe</option>
                        <option value="internal"<?= $this->mailTemplate->getRecipientType() === \Unilend\Entity\MailTemplates::RECIPIENT_TYPE_INTERNAL ? ' selected' : '' ?>>Interne</option>
                    </select>
                </div>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="form-group col-md-12">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" class="form-control input-sm" rows="20" spellcheck="false" style="font-family: Courier New, monospace; width: 100%;"><?= htmlentities($this->mailTemplate->getContent(), ENT_COMPAT, 'UTF-8') ?></textarea>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <button type="submit" class="btn-primary pull-right">Valider</button>
                <?php if (\Unilend\Entity\MailTemplates::PART_TYPE_CONTENT === $this->mailTemplate->getPart()) : ?>
                    <button type="button" id="preview-button" class="btn-default pull-right" style="margin-right: 5px;">Prévisualiser</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<?php $this->fireView('preview'); ?>
