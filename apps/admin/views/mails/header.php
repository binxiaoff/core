<div id="contenu">
    <h1>Modifier <?php echo $this->mailPart->getName(); ?></h1>
    <form method="post" action="<?php echo $this->url; ?>/mails/header/<?php echo $this->mailPart->getId(); ?>">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" class="form-control input-sm" rows="20" spellcheck="false" style="font-family: Courier New, monospace; width: 100%;"><?php echo htmlentities($this->mailPart->getContent(), ENT_COMPAT, 'UTF-8'); ?></textarea>
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
