<script type="text/javascript">
    $(document).ready(function () {
        $(".listeProjets").tablesorter({headers: {4: {sorter: false}, 5: {sorter: false}}});
        $(".mandats").tablesorter({headers: {}});
<?
if ($this->nb_lignes != '') {
    ?>
            $(".listeProjets").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
            $(".mandats").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
    <?
}
?>
    });
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
        <li><a href="<?= $this->lurl ?>/emprunteurs" title="Emprunteurs">Emprunteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/emprunteurs/gestion" title="Gestion emprunteurs">Gestion emprunteurs</a> -</li>
        <li>Detail emprunteur</li>
    </ul>

    <h1>Detail emprunteur : <?= $this->clients->nom . ' ' . $this->clients->prenom ?></h1>

    <?
    if (isset($_SESSION['error_email_exist']) && $_SESSION['error_email_exist'] != '') {
        ?>
        <p style="color:#c84747;text-align:center;font-size:14px;font-weight:bold;"><?= $_SESSION['error_email_exist'] ?></p>
        <?
        unset($_SESSION['error_email_exist']);
    }
    ?>

    <form method="post" name="edit_emprunteur" id="edit_emprunteur" enctype="multipart/form-data" action="<?= $this->lurl ?>/emprunteurs/edit/<?= $this->clients->id_client ?>" target="_parent">
        <table class="formColor" style="width: 775px;margin:auto;">
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
                <th><label for="societe">Société :</label></th>
                <td><input type="text" name="societe" id="societe" class="input_large" value="<?= $this->companies->name ?>"/></td>

                <th><label for="secteur">Secteur :</label></th>
                <td>
                    <select name="secteur" id="secteur" class="select">
                        <?
                        foreach ($this->lSecteurs as $k => $s) {
                            ?><option <?= ($this->companies->sector == $k + 1 ? 'selected' : '') ?> value="<?= $k + 1 ?>"><?= $s ?></option><?
                        }
                        ?>

                    </select>
                </td>
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
                <th><label for="iban">IBAN :</label></th>
                <td colspan="3">
                    <script>
                        function jumpIBAN(field) {
                            if (field.id == "iban7")
                            {
                                field.value = field.value.substring(0, 3);
                            }
                            if (field.value.length == 4)
                            {
                                field.nextElementSibling.value = '';
                                field.nextElementSibling.focus();
                            }
                        }
                    </script>
                    <input type="text" name="iban1" id="iban1" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big" value="<?= substr($this->companies->iban, 0, 4) ?>" />
                    <input type="text" name="iban2" id="iban2" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big" value="<?= substr($this->companies->iban, 4, 4) ?>" />
                    <input type="text" name="iban3" id="iban3" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big" value="<?= substr($this->companies->iban, 8, 4) ?>" />
                    <input type="text" name="iban4" id="iban4" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big" value="<?= substr($this->companies->iban, 12, 4) ?>" />
                    <input type="text" name="iban5" id="iban5" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big" value="<?= substr($this->companies->iban, 16, 4) ?>" />
                    <input type="text" name="iban6" id="iban6" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big" value="<?= substr($this->companies->iban, 20, 4) ?>" />
                    <input type="text" name="iban7" id="iban7" onkeyup="jumpIBAN(this)" style="width: 53px;" size="4" class="input_big" value="<?= substr($this->companies->iban, 24, 3) ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="bic">BIC :</label></th>
                <td colspan="3"><input type="text" name="bic" id="bic" style="width: 620px;" class="input_big" value="<?= $this->companies->bic ?>" onKeyUp="verif(this.id, 1);"/></td>
            </tr>
            <tr>
                <th><label for="email_facture">Email de facturation :</label></th>
                <td colspan="3"><input type="text" name="email_facture" id="email_facture" class="input_large" value="<?= $this->companies->email_facture ?>"/></td>
            </tr>
            <tr>
                <th></th>
                <td><input style="font-size: 11px; height: 25px;" type="button" id="initialiser_espace_emprunteur" name="initialiser_espace_emprunteur" value="Reinitialiser Espace Emprunteur" class="btn" onclick="send_email_borrower_area('<?= $this->clients->id_client ?>', 'initialize')"/></td>
                <?php if (empty($this->clients->secrete_question) && empty($this->clients->secrete_reponse)): ?>
                    <td colspan="2"><input style="font-size: 11px; height: 25px;" type="button" id="ouvrir_espace_emprunteur" name="ouvrir_espace_emprunteur" value="Envoyer Email Ouverture Espace Emprunteur" class="btn" onclick="send_email_borrower_area('<?= $this->clients->id_client ?>', 'open')"/></td>
                <?php endif ?>
               <td><span style="margin-left:5px;color:green; display:none;" class="reponse_email" >Email Envoyé</span></td>
            </tr>
            <tr>
                <th><label for="cni_passeport">CNI/Passeport :</label></th>
                <td>
                    <?= $this->clients->cni_passeport ?><br>
                    <input type="file" name="cni_passeport" id="cni_passeport" value="<?= $this->clients->cni_passeport ?>"/>
                </td>

                <th><label for="signature">Signature :</label></th>
                <td>
                    <?= $this->clients->signature ?><br>
                    <input type="file" name="signature" id="signature" value="<?= $this->clients->signature ?>"/>
                </td>
            </tr>
            <tr>
                <th colspan="4">
                    <input type="hidden" name="form_edit_emprunteur" id="form_edit_emprunteur" />
                    <input type="submit" value="Valider" title="Valider" name="send_edit_emprunteur" onclick="return RIBediting();" id="send_edit_emprunteur" class="btn" />
                </th>
            </tr>
        </table>
    </form>

    <br /><br />

    <?php if ($this->clients->history != ''): ?>
        <div id="edit_history" >
            <h2>Historique :</h2>
            <table class="histo_status_client tablesorter">
                <tbody>
                    <?= $this->clients->history ?>
                </tbody>
            </table>
        </div>
        <br /><br />
    <?php endif; ?>

    <h2>Liste des projets</h2>
    <?
    if (count($this->lprojects) > 0) {
        ?>
        <table class="tablesorter listeProjets">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Projet</th>
                    <th>statut</th>
                    <th>Montant</th>
                    <th>PDF</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?
                $i = 1;
                foreach ($this->lprojects as $p) {


                    // on recupe le nom du statut
                    $this->projects_status->getLastStatut($p['id_project']);
                    ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $p['id_project'] ?></td>
                        <td><?= $p['title'] ?></td>
                        <td><?= $this->projects_status->label ?></td>
                        <td><?= $this->ficelle->formatNumber($p['amount']) ?> €</td>
                        <td>
                            <?
                            if ($this->projects_pouvoir->get($p['id_project'], 'id_project')) {
                                ?><a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $this->projects_pouvoir->name ?>">POUVOIR</a><?
                            }
                            echo '&nbsp;&nbsp;';
                            if ($this->clients_mandats->get($this->clients->id_client, 'id_project = ' . $p['id_project'] . ' AND status = ' . clients_mandats::STATUS_SIGNED . ' AND id_client')) {
                                ?><a href="<?= $this->lurl ?>/protected/mandat_preteur/<?= $this->clients_mandats->name ?>">MANDAT</a><?
                            }
                            ?>
                        </td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/dossiers/edit/<?= $p['id_project'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Détails" />
                            </a>
                        </td>
                    </tr>
                    <?
                    $i++;
                }
                ?>
            </tbody>
        </table>
        <?
        if ($this->nb_lignes != '') {
            ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay" />
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            <?
        }
    }
    ?>
</div>

<script>

    function RIBediting()
    {

        var iban = $('#iban1').val() + $('#iban2').val() + $('#iban3').val() +$('#iban4').val() + $('#iban5').val() + $('#iban6').val() + $('#iban7').val(),
            bic = $('#bic').val();

        // si vide on ne tient pas compte
        if (iban != "")
        {
            if (validateIban(iban) == false && iban != "")
            {
                $.colorbox({href: '<?= $this->lurl ?>/emprunteurs/error_iban_lightbox/'});
                return false;
            }
            else if (check_bic(bic) == false){
                $.colorbox({href: '<?= $this->lurl ?>/emprunteurs/error_bic_lightbox/'});
                return false;
            }
            else{
                // si on a deja les memes infos deja 'enregistré on valide
                if (iban == "<?= $this->companies->iban ?>" && bic == "<?= $this->companies->bic ?>") {
                    return true;
                }

                List_compagnie_meme_iban = CheckIfIbanExistDeja(iban, bic, <?= $this->clients->id_client ?>);

                if (List_compagnie_meme_iban != "none")
                {
                    $.colorbox({href: '<?= $this->lurl ?>/emprunteurs/RIB_iban_existant/'+List_compagnie_meme_iban});
                    return false;
                }

                if (<?= count($this->loadData('prelevements')->select('status = 0 AND id_client = ' . $this->bdd->escape_string($this->params[0]))); ?> == 0)
                {
                    $.colorbox({href: '<?= $this->lurl ?>/emprunteurs/RIBlightbox_no_prelev/<?= $this->clients->id_client ?>'});
                    return false;
                }
                else if (<?= count($this->loadData('prelevements')->select('date_echeance_emprunteur > CURRENT_DATE AND id_client = ' . $this->bdd->escape_string($this->params[0]))); ?> == 0)
                {
                    return true;
                }

                $.colorbox({href: '<?= $this->lurl ?>/emprunteurs/RIBlightbox/<?= $this->clients->id_client ?>'});
                    return false;
            };;
        }
        else
        {
            return true;
        }

    }

    function verif(id, champ)
    {
        // Bic
        if (champ == 1)
        {

            if (check_bic($("#" + id).val()) == false)
                    //if($("#"+id).val().length < 8 || $("#"+id).val().length > 11)
                    {
                        $("#" + id).css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');
                    }
            else {
                $("#" + id).css('border', '1px solid #A1A5A7').css('color', '#B10366').css('background-color', '#ECECEC');
            }
        }
        // IBAN
        if (champ == 2)
        {
             var iban = $('#iban1').val() + $('#iban2').val() + $('#iban3').val() +$('#iban4').val() + $('#iban5').val() + $('#iban6').val() + $('#iban7').val();

            if (validateIban($("#" + id).val()) == false)
                    //if($("#"+id).val().length != 27)
                    {
                        $("#" + id).css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');
                    }
            else {
                $("#" + id).css('border', '1px solid #A1A5A7').css('color', '#B10366').css('background-color', '#ECECEC');
            }
        }
    }

    $("#edit_emprunteur").submit(function (event) {
        var form_ok = true;

        if (check_bic($("#bic").val()) == false && $("#bic").val() != "")
        {
            form_ok = false;
            $("#bic").css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');

        }

        var iban = document.getElementById('iban1').value + document.getElementById('iban2').value + document.getElementById('iban3').value + document.getElementById('iban4').value + document.getElementById('iban5').value + document.getElementById('iban6').value + document.getElementById('iban7').value;

        if (validateIban(iban) == false && iban != "")
        {
            form_ok = false;
            $("#iban1").css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');
            $("#iban2").css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');
            $("#iban3").css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');
            $("#iban4").css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');
            $("#iban5").css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');
            $("#iban6").css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');
            $("#iban7").css('border', '1px solid #E3BCBC').css('color', '#C84747').css('background-color', '#FFE8E8');
        }

        if (form_ok == false)
        {
            event.preventDefault();
        }
    });
</script>
