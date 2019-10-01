<div id="contenu">
    <h1>Ajouter un email</h1>
    <form method="post" action="<?php echo $this->url; ?>/mails/add">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="type">Type</label>
                <span class="help-block">Utiliser uniquement des minuscules et des "-"</span>
                <input type="text" id="type" name="type" class="form-control">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="sender-name">Nom expéditeur</label>
                <input type="text" id="sender-name" name="sender_name" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="sender-email">Email expéditeur</label>
                <input type="text" id="sender-email" name="sender_email" class="form-control">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="subject">Sujet</label>
                <input type="text" id="subject" name="subject" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="title">Titre</label>
                <input type="text" id="title" name="title" class="form-control">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-4">
                <label for="header-select">Header</label>
                <select id="header-select" name="header" class="form-control">
                    <option value=""></option>
                    <?php foreach ($this->headers as $header) { ?>
                        <option value="<?php echo $header->getId(); ?>"><?php echo $header->getName(); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="footer-select">Footer</label>
                <select id="footer-select" name="footer" class="form-control">
                    <option value=""></option>
                    <?php foreach ($this->footers as $footer) { ?>
                        <option value="<?php echo $footer->getId(); ?>"><?php echo $footer->getName(); ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" class="form-control input-sm" rows="20" spellcheck="false" style="font-family: Courier New, monospace; width: 100%;"></textarea>
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
<?php $this->fireView('preview');
