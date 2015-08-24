<script type="text/javascript">


    $(document).ready(function () {

        <!--partie copié de la vue "edit"-->
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".encheres").tablesorter({headers: {6: {sorter: false}}});
        $(".mandats").tablesorter({headers: {}});
        $(".bidsEncours").tablesorter({headers: {6: {sorter: false}}});
        $(".transac").tablesorter({headers: {}});
        $(".favoris").tablesorter({headers: {3: {sorter: false}}});
        <?
        if($this->nb_lignes != '')
        {
        ?>
        $(".encheres").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        $(".mandats").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?
        }
        ?>
        $("#annee").change(function () {
            $('#changeDate').attr('href', "<?=$this->lurl?>/preteurs/edit/<?=$this->params[0]?>/" + $(this).val());
        });

    });
    //partie qui vient de la vue "vos_operations"
    $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

    $("#debut").datepicker({
        changeMonth: true,
        changeYear: true,
        yearRange: '<?=(date('Y')-40)?>:<?=(date('Y'))?>',
        maxDate: '<?=$this->date_fin_display?>',
        onClose: function (selectedDate) {
            $("#fin").datepicker("option", "minDate", selectedDate);
        }
    });
    $("#fin").datepicker({
        changeMonth: true,
        changeYear: true,
        yearRange: '<?=(date('Y')-40)?>:<?=(date('Y'))?>',
        minDate: '<?=$this->date_debut_display?>',
        onClose: function (selectedDate) {
            $("#debut").datepicker("option", "maxDate", selectedDate);
        }
    });

    })
    ;

    <!--partie copié de la vue "edit"-->
    <?
if(isset($_SESSION['freeow']))
{
    ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?
    unset($_SESSION['freeow']);
}
?>
</script>
<!--vient de "vos_operations"-->
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Detail prêteur</a> -</li>
        <li>Portefeuille & Performances</li>
    </ul>

    <?
    // a controler
    if ($this->clients_status->status == 10) {
        ?>
        <div class="attention">
            Attention : compte non validé - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?
    } // completude
    elseif (in_array($this->clients_status->status, array(20, 30, 40))) {
        ?>
        <div class="attention" style="background-color:#F9B137">
            Attention : compte en complétude - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?
    } // modification
    elseif (in_array($this->clients_status->status, array(50))) {
        ?>
        <div class="attention" style="background-color:#F2F258">
            Attention : compte en modification - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?
    }
    ?>

    <!--    section "detail prêteur"    -->
    <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>

    <h2>Préférences Notifications</h2>
    <div class="btnDroite">
        <a
            href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>"
            class="btn_link">Consulter Prêteur</a>
        <a
            href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>"
            class="btn_link">Toutes les infos</a>
        <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Portefeuille & Performances</a>
    </div>
    <div class="form-body">
        <div class="form-row">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <th><span><br>Offres et Projets</span></th>
                    <th><br>Immédiatement</th>
                    <th>
                        <p>Synthèse<br>quotidienne</p>
                    </th>
                    <th>
                        <p>Synthèse<br>hebdomadaire</p>
                    </th>
                    <th>
                        <p>Synthèse<br>Mensuelle</p>
                    </th>
                    <th>
                        <p>Uniquement<br>notification</p>
                    </th>
                </tr>
                <?
                foreach($this->lTypeNotifs as $k => $n){

                    $id_notif = $n['id_client_gestion_type_notif'];
                    if(in_array($id_notif,array(1,2,3,4))){
                        ?>
                        <tr>
                            <td><p><?=$this->lTypeNotifs[$id_notif-1]['nom']?></p></td>
                            <td>
                                <input type="checkbox" id="immediatement_<?=$id_notif?>" name="immediatement_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['immediatement']==1?'checked':'')?> disabled/>
                                <label for="immediatement_<?=$id_notif?>"></label>
                            </td>
                            <td>
                                <input type="checkbox" id="quotidienne_<?=$id_notif?>" name="quotidienne_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['quotidienne']==1?'checked':'')?> disabled/>
                                <label for="quotidienne_<?=$id_notif?>"></label>
                            </td>
                            <td>
                                <?
                                if(!in_array($id_notif,array(2,3))){
                                    ?>
                                    <input type="checkbox" id="hebdomadaire_<?=$id_notif?>" name="hebdomadaire_<?=$id_notif?>" <?=(in_array($id_notif,array(2))?'class="check-delete" disabled checked':($this->NotifC[$id_notif]['hebdomadaire']==1?'checked':''))?> disabled/>
                                    <label for="hebdomadaire_<?=$id_notif?>"></label>
                                    <?
                                }
                                ?>
                            </td>
                            <td>
                                <?
                                if(!in_array($id_notif,array(1,2,3))){
                                    ?>
                                    <input type="checkbox" id="mensuelle_<?=$id_notif?>" name="mensuelle_<?=$id_notif?>" <?=(in_array($id_notif,array(1,2))?'class="check-delete" disabled checked':($this->NotifC[$id_notif]['mensuelle']==1?'checked':''))?> disabled/>
                                    <label for="mensuelle_<?=$id_notif?>"></label>

                                    <?
                                }
                                ?>
                            </td>
                            <td>
                                <input type="radio" id="uniquement_notif_<?=$id_notif?>" name="uniquement_notif_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['uniquement_notif']==1?'checked':'')?> disabled/>
                                <label for="uniquement_notif_<?=$id_notif?>"></label>
                            </td>
                        </tr>
                        <?
                    }
                }
                ?>
                <tr>
                    <th><span>Remboursements</span></th>
                </tr>
                <?
                foreach($this->lTypeNotifs as $k => $n){
                    $id_notif = $n['id_client_gestion_type_notif'];
                    if(in_array($id_notif,array(5))){
                        ?>
                        <tr>
                            <td><p><?=$this->lTypeNotifs[$id_notif-1]['nom']?></p></td>
                            <td>
                                <input type="radio" id="immediatement_<?=$id_notif?>" name="immediatement_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['immediatement']==1?'checked':'')?> disabled/>
                                <label for="immediatement_<?=$id_notif?>"></label>
                            </td>

                            <td>
                                <input  type="checkbox" id="quotidienne_<?=$id_notif?>" name="quotidienne_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['quotidienne']==1?'checked':'')?> disabled/>
                                <label for="quotidienne_<?=$id_notif?>"></label>
                            </td>
                            <td>
                                <input type="checkbox" id="hebdomadaire_<?=$id_notif?>" name="hebdomadaire_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['hebdomadaire']==1?'checked':'')?> disabled/>
                                <label for="hebdomadaire_<?=$id_notif?>"></label>
                            </td>
                            <td>
                                <input type="checkbox" id="mensuelle_<?=$id_notif?>" name="mensuelle_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['mensuelle']==1?'checked':'')?> disabled/>
                                <label for="mensuelle_<?=$id_notif?>"></label>
                            </td>
                            <td>
                                <div class="form-controls">
                                    <input type="radio" id="uniquement_notif_<?=$id_notif?>" name="uniquement_notif_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['uniquement_notif']==1?'checked':'')?> disabled/>

                                    <label for="uniquement_notif_<?=$id_notif?>"></label>
                            </td>
                        </tr>
                        <?
                    }
                }
                ?>
                <tr>
                    <th><span>Mouvements sur le compte</span></th>
                </tr>
                <?
                foreach($this->lTypeNotifs as $k => $n){
                    $id_notif = $n['id_client_gestion_type_notif'];
                    if(in_array($id_notif,array(6,7,8))){
                        ?>
                        <tr>
                            <td>
                                <p><?=$this->lTypeNotifs[$id_notif-1]['nom']?></p></td>
                            <td>
                                <input type="checkbox" id="immediatement_<?=$id_notif?>" name="immediatement_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['immediatement']==1?'checked':'')?> disabled/>
                                <label for="immediatement_<?=$id_notif?>"></label>
                            </td>
                            <td colspan="3"></td>
                            <td>
                                <input type="radio" id="uniquement_notif_<?=$id_notif?>" name="uniquement_notif_<?=$id_notif?>" <?=($this->NotifC[$id_notif]['uniquement_notif']==1?'checked':'')?> disabled />
                                <label for="uniquement_notif_<?=$id_notif?>"></label>
                            </td>
                        </tr>
                        <?
                    }
                }
                ?>
            </table>
        </div><!-- /.form-row -->
    </div><!-- /.form-body -->


    <H2>Historique des Emails</H2>
    Et ici viedra la vue des l'historique des mails
    avec une lightbox preview à droite


    <script type="text/javascript">
        $("input,select").change(function () {

            $(".c2-sb-wrap").removeClass('populated');


            $(".load_table_vos_operations").fadeIn();


            var val = {
                debut: $("#debut").val(),
                fin: $("#fin").val(),
                nbMois: $("#nbMois").val(),
                annee: $("#annee").val(),
                tri_type_transac: $("#tri_type_transac").val(),
                tri_projects: $("#tri_projects").val(),
                id_last_action: $(this).attr('id')
            };
            //alert('debut : '+debut+' fin : '+fin+' mois : '+mois+' annee : '+annee+' tri_type_transac : '+tri_type_transac+' tri_projects : '+tri_projects);
            $.post(add_url + "/ajax/vos_operations", val).done(function (data) {
                //alert( "Data Loaded: " + data );

                var obj = jQuery.parseJSON(data);

                $("#debut").val(obj.debut);
                $("#fin").val(obj.fin);


                $("#filtres_secondaires").html(obj.html_filtre);
                $(".custom-select").c2Selectbox();

                $(".content_table_vos_operations").html(obj.html);
                $(".load_table_vos_operations").fadeOut();

            });
        });


    </script>