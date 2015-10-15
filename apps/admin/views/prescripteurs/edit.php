<script type="text/javascript">
<?
if (isset($_SESSION['freeow'])) {
    ?>
        $(document).ready(function () {
            var title, message, opts, container;
            title = "<?= $_SESSION['freeow']['title'] ?>";
            message = "<?= $_SESSION['freeow']['message'] ?>";
            opts = {};
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
    <?
    unset($_SESSION['freeow']);
}
?>
</script>
<style>
    #infos_client{display:none;border:1 px solid #2F86B2; padding:15px;}
</style>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/prescripteurs" title="Prescripteurs">Prescripteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/prescripteurs/gestion" title="Gestion prescripteurs">Gestion prescripteurs</a> -</li>
        <li>Detail prescripteur</li>
    </ul>

    <h1>Detail prescripteur : <?= $this->clients->nom . ' ' . $this->clients->prenom ?></h1>

    <?
    if (isset($_SESSION['error_email_exist']) && $_SESSION['error_email_exist'] != '') {
        ?>
        <p style="color:#c84747;text-align:center;font-size:14px;font-weight:bold;"><?= $_SESSION['error_email_exist'] ?></p>
        <?
        unset($_SESSION['error_email_exist']);
    }
    ?>

    <form method="post" name="edit_prescripteur" id="edit_prescripteur" enctype="multipart/form-data" action="<?= $this->lurl ?>/prescripteurs/edit/<?= $this->clients->id_client ?>" target="_parent">           
        <table class="formColor" style="width: 775px;margin:auto;">
            <tr>
                <th>Civilité :</th>
                <td>
                    <input <?= $this->clients->civilite == 'Mme' ? 'checked' : '' ?> type="radio" name="civilite" id="civilite_mme" value="Mme"/>
                    <label for="civilite_mme">Madame</label>

                    <input <?= $this->clients->civilite == 'M.' ? 'checked' : '' ?> type="radio" name="civilite" id="civilite_m" value="M."/>
                    <label for="civilite_m">Monsieur</label>
                </td>
                <th></th>
                <td></td>
            </tr>
            <tr>
                <th><label for="nom">Nom :</label></th>
                <td><input type="text" name="nom" id="nom" class="input_large" value="<?= $this->clients->nom ?>"/></td>

                <th><label for="prenom">Prénom :</label></th>
                <td><input type="text" name="prenom" id="prenom" class="input_large" value="<?= $this->clients->prenom ?>"/></td>
            </tr>
            <tr>
                <th><label for="email">Email :</label></th>
                <td><input type="text" name="email" id="email" class="input_large" value="<?= $this->clients->email ?>"/></td>
                <th><label for="telephone">Téléphone :</label></th>
                <td><input type="text" name="telephone" id="telephone" class="input_large" value="<?= $this->clients->telephone ?>"/></td>
            </tr>
            <tr>
                <th><label for="adresse">Adresse :</label></th>
                <td colspan="3"><input type="text" name="adresse" id="adresse" style="width: 620px;" class="input_big" value="<?= $this->clients_adresses->adresse1 ?>"/></td>
            </tr>
            <tr>
                <th><label for="cp">Code postal :</label></th>
                <td><input type="text" name="cp" id="cp" class="input_large" value="<?= $this->clients_adresses->cp ?>"/></td>

                <th><label for="ville">Ville :</label></th>
                <td><input type="text" name="ville" id="ville" class="input_large" value="<?= $this->clients_adresses->ville ?>"/></td>
            </tr>
            <tr>
                <th colspan="4">
                    <input type="hidden" name="form_edit_prescripteur" id="form_edit_prescripteur" />
                    <input type="submit" value="Valider" title="Valider" name="send_edit_prescripteur" id="send_edit_prescripteur" class="btn" />
                </th>
            </tr>
        </table>
    </form>
</div>