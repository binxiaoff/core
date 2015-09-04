<script type="text/javascript">
    <?
    if(isset($_SESSION['freeow']))
    {
    ?>
    $(document).ready(function(){
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

    <!--    section "lender details"    -->
    <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>

    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Consulter Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Modifier Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Historique des emails</a>
    </div>

    <div>
        <h2>Portefeuille</h2>

        <h3>TRI du portefeuille : <?=$this->TRI?></h3>

        <h3>Nombre de projets à probleme dans le portefeuille :  <?=$this->problProjects?></h3>
        <h3>Nombre de projets total dans le portefeuille : <?=$this->totalProjects?></h3>

        <h3>Nombre de projets mis en ligne depuis son inscription : <?php echo $this->projectsPublished; ?><h2>

    </div>

    <br>

    <!--HISTORIQUE DES PRETS-->

    <h2>Prêts</h2>

    <div class="table-filter clearfix">
        <p class="left">Historique des projets financés depuis le compte Unilend n°<?= $this->clients->id_client ?></p>
    </div>
    <div><!-- start table loans -->
        <table class="tablesorter">
            <thead>
            <tr>
                <th style="text-align: left">Projet</th>
                <th style="text-align: left">Note</th>
                <th style="text-align: left">Montant prêté</th>
                <th style="text-align: left">Taux d'intérêt</th>
                <th style="text-align: left">Début</th>
                <th style="text-align: left">Prochaine</th>
                <th style="text-align: left">Fin</th>
                <th style="text-align: left">Mensualité</th>
                <th style="text-align: left">Documents <br> à télécharger</th>
            </tr>
            </thead>
            <?
            if($this->lSumLoans != false)
            {
                $i=1;
                foreach($this->lSumLoans as $k => $l)
                {
                    $Le_projects = $this->loadData('projects');
                    $Le_projects->get($l['id_project']);
                    $this->projects_status->getLastStatut($l['id_project']);

                    //si un seul loan sur le projet
                    if($l['nb_loan'] == 1){
                        ?>
                        <tr class="<?=($i%2 == 1?'':'odd')?>">
                            <td><h5><?=$l['name']?></h5></td>
                            <td><?=$l['risk']?></td>
                            <td><?=number_format($l['amount'], 2, ',', ' ')?> €</td>
                            <td><?=number_format($l['rate'], 2, ',', ' ')?> %</td>
                            <td><?=$this->dates->formatDate($l['debut'],'d/m/Y')?></td>
                            <td><?=$this->dates->formatDate($l['next_echeance'],'d/m/Y')?></td>
                            <td><?=$this->dates->formatDate($l['fin'],'d/m/Y')?></td>
                            <td><?=number_format($l['mensuel'], 2, ',', ' ')?> €/mois</td>
                           <td>
                               <?
                               if($this->projects_status->status >=80)
                               {
                               ?>
                               <a href="<?=$this->lurl.'/pdf/contrat/'.$this->clients->hash.'/'.$loan['id_loan']?>">Contrat PDF</a><br>

                               <?php if(in_array($l['id_project'],$this->arrayDeclarationCreance)){?>
                               <a href="<?=$this->lurl.'/pdf/declaration_de_creances/'.$this->clients->hash.'/'.$loan['id_loan']?>">Créances PDF</a><?php

                                }
                                }
                                ?>
                           </td>
                        </tr>
                        <?php
                        $i++;
                    }
                    // Si plus
                    else{
                        ?>
                        <tr class="<?=($i%2 == 1?'':'odd')?>">
                            <td><h5><?=$l['name']?></h5></td>
                            <td><?=$l['risk']?></td>
                            <td><?=number_format($l['amount'], 2, ',', ' ')?> €</td>
                            <td><?=number_format($l['rate'], 2, ',', ' ')?> %</td>
                            <td><?=$this->dates->formatDate($l['debut'],'d/m/Y')?></td>
                            <td><?=$this->dates->formatDate($l['next_echeance'],'d/m/Y')?></td>
                            <td><?=$this->dates->formatDate($l['fin'],'d/m/Y')?></td>
                            <td><?=number_format($l['mensuel'], 2, ',', ' ')?> €/mois</td>
                            <td>
                            </td>
                        </tr>
                                        <?
                                        $a = 0;
                                        $listeLoans = $this->loans->select('id_lender = '.$this->lenders_accounts->id_lender_account.' AND id_project = '.$l['id_project']);
                                        foreach($listeLoans as $loan){

                                            $SumAremb = $this->echeanciers->select('id_loan = '.$loan['id_loan'].' AND status = 0','ordre ASC',0,1);

                                            $fiscal = $SumAremb[0]['prelevements_obligatoires']+$SumAremb[0]['retenues_source']+$SumAremb[0]['csg']+$SumAremb[0]['prelevements_sociaux']+$SumAremb[0]['contributions_additionnelles']+$SumAremb[0]['prelevements_solidarite']+$SumAremb[0]['crds'];

                                            $b = $a+1;
                                            ?>

                                            <tr style="background-color: #e3e4e5; color: black;">
                                                <td style="text-align: right; background-color: #e3e4e5; color: black;">Détail loan</td>
                                                <td style="text-align: right; background-color: #e3e4e5; color: black;"></td>
                                                <td style="background-color: #e3e4e5; color: black;"><?=number_format($loan['amount']/100, 0, ',', ' ')?> €</td>
                                                <td style="background-color: #e3e4e5; color: black;"><?=number_format($loan['rate'], 2, ',', ' ')?>%</td>
                                                <td style="text-align: right; background-color: #e3e4e5; color: black;"></td>
                                                <td style="text-align: right; background-color: #e3e4e5; color: black;"></td>
                                                <td style="text-align: right; background-color: #e3e4e5; color: black;"></td>
                                                <td style="background-color: #e3e4e5; color: black;"><?=number_format(($SumAremb[0]['montant']/100)-$fiscal, 2, ',', ' ')?> €/mois</td>
                                                <td>
                                                    <?
                                                    if($this->projects_status->status >=80)
                                                    {
                                                        ?>
                                                        <a style="background-color: #e3e4e5; color: black;" href="<?=$this->lurl.'/pdf/contrat/'.$this->clients->hash.'/'.$loan['id_loan']?>">Contrat PDF</a><br>

                                                        <?php if(in_array($l['id_project'],$this->arrayDeclarationCreance)){?>
                                                        <a style="background-color: #e3e4e5; color: black;" href="<?=$this->lurl.'/pdf/declaration_de_creances/'.$this->clients->hash.'/'.$loan['id_loan']?>">Créances PDF</a><?php

                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                   <script type="text/javascript">
                                        $(".btn-detailLoans_<?=$k?>").click(function() {
                                            $(".loans_<?=$k?>").slideToggle();

                                            if($(".btn-detailLoans_<?=$k?>").hasClass("on_display"))
                                            {
                                                $(".btn-detailLoans_<?=$k?>").html('+');

                                                $(".btn-detailLoans_<?=$k?>").addClass("off_display");
                                                $(".btn-detailLoans_<?=$k?>").removeClass("on_display");
                                            }
                                            else
                                            {
                                                $(".btn-detailLoans_<?=$k?>").html('-');

                                                $(".btn-detailLoans_<?=$k?>").addClass("on_display");
                                                $(".btn-detailLoans_<?=$k?>").removeClass("off_display");
                                            }

                                        });
                                    </script>
                                            <?php

                                            $a++;
                                        }
                                        ?>
                        </tr>
                        <?
                        $i++;
                    }
                }
            }
            ?>
        </table>
        <!-- /.table loans -->
    </div><!--end div lender-->
    </div>
<?php unset($_SESSION['freeow']); ?>