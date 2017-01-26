<div id="contenu">
    <form method="post" name="add_mail" id="add_mail" enctype="multipart/form-data">
        <input type="hidden" name="lng_encours" id="lng_encours" value="<?= $this->language ?>"/>
        <fieldset>
            <h1>Ajouter un email</h1>
            <table class="large">
                <tr>
                    <th><label for="name">Type:</label><br/>
                        <span><em>Utiliser que des minuscules et des "-" et non des "_"</em></span></th>
                </tr>
                <tr>
                    <td><input type="text" name="type" id="type" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="sender_name>">Nom d'expéditeur :</label></th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="sender_name" id="sender_name" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="sender_email">Adresse d'expéditeur :</label></th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="sender_email" id="sender_email" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="subject">Sujet :</label></th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="subject" id="subject" class="input_big"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="content">Contenu :</label></th>
                </tr>
                <tr>
                    <td>
                        <textarea name="content" id="content" class="textarea_big"></textarea>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="hidden" name="form_add_mail" id="form_add_mail"/>
                        <input type="submit" value="Valider" title="Valider" name="send_mail" id="send_mail" class="btn"/>
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
