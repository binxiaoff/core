<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/settings" title="Configuration">Configuration</a> -</li>
        <li><a href="<?= $this->lurl ?>/mails" title="Mails">Mails</a> -</li>
        <li>Modifier un email</li>
    </ul>
    <form method="post" name="mod_mail" id="mod_mail" enctype="multipart/form-data">
        <input type="hidden" name="lng_encours" id="lng_encours" value="<?= $this->language ?>"/>
        <fieldset>
            <h1>Modifier <?= $this->mails_text->type ?></h1>
            <table class="large">
                <tr>
                    <th><label for="exp_name">Nom d'expéditeur :</label></th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="exp_name" id="exp_name" value="<?= $this->mails_text->exp_name ?>" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="exp_email">Adresse d'expéditeur :</label></th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="exp_email" id="exp_email" value="<?= $this->mails_text->exp_email ?>" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="subject">Sujet :</label></th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="subject" id="subject" value="<?= $this->mails_text->subject ?>" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="content">Contenu :</label></th>
                </tr>
                <tr>
                    <td>
                        <textarea name="content" id="content" class="textarea_big"><?= htmlentities($this->mails_text->content, ENT_COMPAT, 'UTF-8') ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="hidden" name="id_textemail" value="<?= $this->mails_text->id_textemail ?>">
                        <input type="hidden" name="type" value="<?= $this->mails_text->type ?>">
                        <input type="hidden" name="form_mod_mail" id="form_mod_mail"/>
                        <input type="submit" value="Valider" title="Valider" name="send_mail" id="send_mail" class="btn"/>
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>