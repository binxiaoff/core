<div id="contenu">
    <form method="post" name="search_preteur" id="search_preteur" enctype="multipart/form-data" action="<?= $this->lurl ?>/preteurs/gestion" target="_parent">
        <h1>Rechercher un prêteur</h1>
        <?php if (isset($_SESSION['error_search'])) : ?>
            <div class="attention">
                <?php foreach ($_SESSION['error_search'] as $error ) : ?>
                    <?= $error ?><br>
                <?php endforeach; ?>
                <?php unset($_SESSION['error_search']); ?>
            </div>
        <?php endif; ?>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="id">ID :</label></th>
                    <td><input type="text" name="id" id="id" class="input_large" /></td>
                </tr>
                <tr>
                    <th>&nbsp;</th>
                    <td>
                        <input type="checkbox" name="nonValide" id="nonValide" />
                        <label for="nonValide">Preteurs offline</label>
                    </td>
                </tr>
                <tr>
                    <th colspan="2" style="text-align:center;"><br />Personne physique</th>
                </tr>
                <tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="prenom">Prenom :</label></th>
                    <td><input type="text" name="prenom" id="prenom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="email">Email :</label></th>
                    <td><input type="text" name="email" id="email" class="input_large" /></td>
                </tr>
                <tr>
                    <th colspan="2" style="text-align:center;"><br />Personne morale</th>
                </tr>
                <tr>
                    <th><label for="raison_sociale">Raison sociale :</label></th>
                    <td><input type="text" name="raison_sociale" id="raison_sociale" class="input_large" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <th>
                        <input type="hidden" name="form_search_preteur" id="form_search_preteur" />
                        <input type="submit" value="Valider" title="Valider" name="send_preteur" id="send_preteur" class="btn" />
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
