<script type="text/javascript">
    $(document).ready(function () {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});
<?
if ($this->nb_lignes != '')
{
    ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
    <?
}
?>
    });
<?
if (isset($_SESSION['freeow']))
{
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
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/dossiers" title="Dossiers">Dossiers</a> -</li>
        <li><a href="<?= $this->lurl ?>/dossiers/remboursements" title="Remboursements">Remboursements</a> -</li>
        <li>Detail remboursements</li>
    </ul>

    <h1>Remboursement <?= $this->companies->name ?> - <?= $this->projects->title_bo ?></h1>

    <div class="btnDroite">

        <a style="margin-right:10px;" target="_blank" href="<?= $this->lurl ?>/dossiers/echeancier_emprunteur/<?= $this->projects->id_project ?>" class="btn_link">Echeancier Emprunteur</a>

        <a target="_blank" href="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>" class="btn_link">Voir le dossier</a>
    </div>

    <style>

        .form th{width:125px;}
        .form2 th{width:auto;}
    </style>     
    <table class="form" style="margin: auto;">
        <tr>
            <td colspan="7"><h2>Informations projet</h2></td>
        </tr>
        <tr>
            <td colspan="2"><b><?= $this->companies->name ?> - <?= $this->projects->title_bo ?></b></td>
            <td><?= number_format($this->projects->amount, 2, ',', ' ') ?> € - <?= $this->projects->period ?> mois</td>
            <th>Risques :</th>
            <td><?= $this->companies->risk ?></td>
            <th>Analyste : </th>
            <td><?= $this->users->firstname ?> <?= $this->users->name ?></td>
        </tr>
        <tr>
            <th>Contact :</th>
            <td><?= $this->clients->nom ?> <?= $this->clients->prenom ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>

            <th>Nombre de prêteur :</th>
            <td><?= $this->nbPeteurs ?></td>

            <td>Fundé depuis le <?= $this->dates->formatDate($this->projects->date_fin, 'd/m/Y') ?></td>
            <td></td>
            <td></td>
            <th>Statut :</th>
            <td><?= $this->projects_status->label ?></td>
        </tr>
        <tr>

            <th>Commission Unilend :</th>
            <td><?= number_format($this->commissionUnilend / 100, 2, ',', ' ') ?> €</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>

    </table>

    <br><br>
    <?
    if ($this->nbRembEffet > 0)
    {
        ?>
        <table class="form form2" style="margin: auto;">
            <tr>
                <td colspan="4"><h2>Remboursement</h2></td>
            </tr>
            <tr>
                <th>Remboursements effectué : </th>
                <td><?= $this->nbRembEffet ?></td>
                <th>Montant remboursé :</th>
                <td><?= number_format($this->totalEffet / 100, 2, ',', ' ') ?> €</td> 
            </tr>
            <tr>
                <td></td>
                <td></td>
                <th></th>
                <td><i><?= number_format($this->interetEffet / 100, 2, ',', ' ') ?> € d'intérêts - <?= number_format($this->capitalEffet / 100, 2, ',', ' ') ?> € de capital - <?= number_format($this->commissionEffet / 100, 2, ',', ' ') ?> € de commissions - <?= number_format($this->tvaEffet / 100, 2, ',', ' ') ?> € de TVA</i></td> 
            </tr>
            <tr style="height:30px;">
                <td colspan="4"></td>
            </tr>
            <tr>
                <th>Remboursements à venir :</th>
                <td><?= $this->nbRembaVenir ?></td>
                <th>Montant à percevoir :</th>
                <td><?= number_format($this->totalaVenir / 100, 2, ',', ' ') ?> €</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td><i><?= number_format($this->interetaVenir / 100, 2, ',', ' ') ?> € d'intérêts - <?= number_format($this->capitalaVenir / 100, 2, ',', ' ') ?> € de capital - <?= number_format($this->commissionaVenir / 100, 2, ',', ' ') ?> € de commissions - <?= number_format($this->tvaaVenir / 100, 2, ',', ' ') ?> € de TVA</i></td> 
            </tr>
            
            <?php
            if (!$this->remb_anticipe_effectue)
            {
                ?>
                <tr>
                    <th>Prochain remboursement :</th>
                    <td><?= $this->dates->formatDate($this->nextRemb, 'd/m/Y') ?></td>
                    <td></td>
                    <td></td>
                </tr>
                <?php
            }
            ?>
        </table>

    
        <?php
        //on affiche cela uniquement si pas de remb anticipé de fait        
        if (!$this->remb_anticipe_effectue)
        {
            ?>
            <br /><br />

            <div style="border: 1px solid #b10366; height: 60px; padding: 5px; width: 280px;">

                <form action="" method="post">
                    <b>Remboursement automatique : </b>
                    <input type="radio" name="remb_auto" value="0" <?= ($this->projects->remb_auto == 0 ? 'checked' : '') ?>>Oui
                    <input type="radio" name="remb_auto" value="1" <?= ($this->projects->remb_auto == 1 ? 'checked' : '') ?>>Non
                    <br />
                    <input type="hidden" name="send_remb_auto" />
                    <input style="margin-top:5px;" type="submit" value="Valider" name="valider_remb_auto" class="btn" />
                </form>
            </div>

            <br /><br />

            <?
            // remb auto desactivé 
            if ($this->projects->remb_auto == 1)
            {
                ?><a style="display:block; margin: auto;width: 180px;" href="<?= $this->lurl ?>/dossiers/detail_remb/<?= $this->projects->id_project ?>/remb" class="btn_link">Valider & Rembourser</a><?
                echo '<br/>';
                ?><a style="display:block; margin: auto;width: 180px;" href="<?= $this->lurl ?>/dossiers/detail_remb/<?= $this->projects->id_project ?>/remb/regul" class="btn_link">régularisation de remboursement en retard</a><?
            }
        }
    }
    ?>
    <br>
    <div class="btnDroite"><a style="border-color: #A1A5A7;background-color:#A1A5A7; color:white;" href="<?= $this->lurl ?>/dossiers/detail_remb_preteur/<?= $this->projects->id_project ?>" class="btn_link">Voir le détail prêteur</a></div>

    <?
    if ($this->projects_status->status == 100)
    {
        echo 'PROBLEME <-------';
    }
    ?>



    <br><br>
    <h2>Remboursement anticipé / Information</h2>
    <table class="form" style="width: 538px; border: 1px solid #B10366;">
        <tr>
            <th>Statut :</th>
            <td>                            
                <label for="statut"><?= $this->phrase_resultat ?></label>
            </td>
        </tr>
        <?php
        if ($this->virement_recu)
        {
            ?>
            <tr>
                <th>Virement reçu le :</th>
                <td>                            
                    <label for="statut"><?= $this->dates->formatDateMysqltoFr_HourOut($this->receptions->added) ?></label>
                </td>
            </tr>
            <tr>
                <th>Identification virement :</th>
                <td>                            
                    <label for="statut"><?= $this->receptions->id_reception ?></label>
                </td>
            </tr>

            <tr>
                <th>Montant virement :</th>
                <td>                            
                    <label for="statut"><?= ($this->receptions->montant / 100) ?> €</label>
                </td>
            </tr>

            <tr>
                <th>Motif du virement :</th>
                <td>                            
                    <label for="statut"><?= $this->receptions->motif ?></label>
                </td>
            </tr>
            <?php
        }
        else
        {
            ?>
            <tr>
                <th>Virement à émettre avant le :</th>
                <td>                            
                    <label for="statut"><?= $this->date_next_echeance_4jouvres_avant ?></label>
                </td>
            </tr>
            <?php
        }
        ?>
        <tr>    
            <th>Montant CRD (*) :</th>
            <td>                            
                <label for="statut"><?= $this->montant_restant_du_preteur ?>€</label>
            </td>
        </tr>


        <?php
        // on check si toutes les autres echeances on été remb avant de faire le remb anticipe
        //$L_echeance = $this->echeanciers_emprunteur->select(" id_project = " . $this->projects->id_project . " AND status_emprunteur  = 0 AND ordre < " . $this->ordre_echeance_ra);
        
        // on ne se base plus sur l'echeancier emprunteur mais preteur comme pour le calcul du CRD
        $L_echeance = $this->echeanciers->select(" id_project = " . $this->projects->id_project . " AND status = 0 AND ordre < " . $this->ordre_echeance_ra, 'ordre ASC');
                
        if(count($L_echeance) > 0 && false)
        {
            ?>
            <tr>    
                <th>Actions :</th>
                <td>                            
                    <label for="statut">Le remboursement anticipé n'est pour le moment pas possible car les échéances précédentes ne sont pas encore réglée</label>
                </td>
            </tr>
            <?php
        }
        else
        {        
            if (!$this->remb_anticipe_effectue)
            {
                if ($this->virement_recu)
                {
                    if ($this->virement_recu_ok)
                    {
                        if ($this->ra_possible_all_payed)
                        {
                            ?>
                            <tr>
                                <th>Actions :</th>
                                <td> 
                                    <form action="" method="post" name="action_remb_anticipe">
                                        <input type="hidden" name="id_reception" value="<?= $this->receptions->id_reception ?>">
                                        <input type="hidden" name="montant_crd_preteur" value="<?= $this->montant_restant_du_preteur ?>">
                                        <input type="hidden" name="spy_remb_anticipe" value="ok">
                                        <input type="submit" value="Déclencher le remboursement anticipé" class="btn"> 
                                    </form>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                }
                else
                {
                    ?>
                    <tr> 
                        <th>Motif à indiquer sur le virement :</th>
                        <td>                            
                            <label for="statut">RA-<?= $this->projects->id_project ?></label>
                        </td>
                    </tr>
                    <?php
                }
            }
        }
        ?>

    </table>


    <?php
    if(!$this->virement_recu && !$this->remb_anticipe_effectue)
    {
        ?>
        * : Le montant correspond aux CRD des échéances restantes après celle du <?= $this->date_next_echeance ?> qui sera prélevé normalement
        <?php
    }
    ?>



    <br><br><br><br>



</div>
