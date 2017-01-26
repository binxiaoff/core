<div id="contenu">
    <form method="post" name="mod_mail" enctype="multipart/form-data">
        <fieldset>
            <h1>Modifier <?= $this->oMailTemplate->type ?></h1>
            <table class="large">
                <tr>
                    <th><label for="sender_name">Nom d'expéditeur</label></th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="sender_name" id="sender_name" value="<?= $this->oMailTemplate->sender_name ?>" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="sender_email">Adresse d'expéditeur</label></th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="sender_email" id="sender_email" value="<?= $this->oMailTemplate->sender_email ?>" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="subject">Sujet</label></th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="subject" id="subject" value="<?= $this->oMailTemplate->subject ?>" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="content">Contenu</label></th>
                </tr>
                <tr>
                    <td>
                        <textarea name="content" id="content" class="textarea_big"><?= htmlentities($this->oMailTemplate->content, ENT_COMPAT, 'UTF-8') ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="hidden" name="form_mod_mail"/>
                        <input type="hidden" name="lng_encours" value="<?= $this->language ?>"/>
                        <input type="submit" value="Valider" class="btn"/>
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
