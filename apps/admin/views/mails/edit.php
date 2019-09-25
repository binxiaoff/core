<div id="contenu">
    <h1>Modifier <?php echo $this->mailTemplate->getType(); ?></h1>
    <form method="post" action="<?php echo $this->url; ?>/mails/edit/<?php echo $this->mailTemplate->getType(); ?><?php echo isset($this->params[1]) ? '/' . $this->params[1] : ''; ?>">
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="sender-name">Nom expéditeur</label>
                    <input type="text" value="<?php echo $this->mailTemplate->getSenderName(); ?>" id="sender-name" name="sender_name" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label for="sender-email">Email expéditeur</label>
                    <input type="text" value="<?php echo $this->mailTemplate->getSenderEmail(); ?>" id="sender-email" name="sender_email" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="subject">Sujet</label>
                    <input type="text" value="<?php echo $this->mailTemplate->getSubject(); ?>" id="subject" name="subject" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label for="title">Titre</label>
                    <input type="text" value="<?php echo $this->mailTitle; ?>" id="title" name="title" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="header-select">Header</label>
                    <select id="header-select" name="header" class="form-control">
                        <option value=""></option>
                        <?php foreach ($this->headers as $header) { ?>
                            <option value="<?php echo $header->getId(); ?>"<?php echo $this->mailTemplate->getHeader() === $header ? ' selected' : ''; ?>><?php echo $header->getType(); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="footer-select">Footer</label>
                    <select id="footer-select" name="footer" class="form-control">
                        <option value=""></option>
                        <?php foreach ($this->footers as $footer) { ?>
                            <option value="<?php echo $footer->getId(); ?>"<?php echo $this->mailTemplate->getFooter() === $footer ? ' selected' : ''; ?>><?php echo $footer->getType(); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        <div class="row">
            <div class="form-group col-md-12">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" class="form-control input-sm" rows="20" spellcheck="false" style="font-family: Courier New, monospace; width: 100%;"><?php echo htmlentities($this->mailTemplate->getContent(), ENT_COMPAT, 'UTF-8'); ?></textarea>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <button type="submit" class="btn-primary pull-right">Valider</button>
            </div>
        </div>
    </form>
</div>
<?php $this->fireView('preview');
