<script type="text/javascript">
    $(document).ready(function(){

        jQuery.tablesorter.addParser({ id: "fancyNumber", is: function(s) { return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s); }, format: function(s) { return jQuery.tablesorter.formatFloat( s.replace(/,/g,'').replace(' €','').replace(' ','') ); }, type: "numeric" });

        $(".encheres").tablesorter({headers:{6:{sorter: false}}});
        $(".mandats").tablesorter({headers:{}});
        $(".bidsEncours").tablesorter({headers:{6:{sorter: false}}});
        $(".transac").tablesorter({headers:{}});
        $(".favoris").tablesorter({headers:{3:{sorter: false}}});
        <?
        if($this->nb_lignes != '')
        {
        ?>
        $(".encheres").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});
        $(".mandats").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});
        <?
        }
        ?>
        $( "#annee" ).change(function() {
            $('#changeDate').attr('href',"<?=$this->lurl?>/preteurs/edit/<?=$this->params[0]?>/"+$(this).val());
        });

    });
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
        <li><a href="<?=$this->lurl?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?=$this->lurl?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li>Detail prêteur</li>
    </ul>

    <?
    // a controler
    if($this->clients_status->status == 10)
    {
        ?>
        <div class="attention">
            Attention : compte non validé - créé le <?=date('d/m/Y',$this->timeCreate)?>
        </div>
        <?
    }
    // completude
    elseif(in_array($this->clients_status->status,array(20,30,40)))
    {
        ?>
        <div class="attention" style="background-color:#F9B137">
            Attention : compte en complétude - créé le <?=date('d/m/Y',$this->timeCreate)?>
        </div>
        <?
    }
    // modification
    elseif(in_array($this->clients_status->status,array(50)))
    {
        ?>
        <div class="attention" style="background-color:#F2F258">
            Attention : compte en modification - créé le <?=date('d/m/Y',$this->timeCreate)?>
        </div>
        <?
    }
    ?>

<!--    section "detail prêteur"    -->
    <h1>Detail prêteur : <?=$this->clients->prenom.' '.$this->clients->nom?></h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/preteurs/edit_preteur/<?=$this->lenders_accounts->id_lender_account?>" class="btn_link">Modifier Prêteur</a>
    <a href="<?=$this->lurl?>/preteurs/email_history/<?=$this->lenders_accounts->id_lender_account?>" class="btn_link">Historique des emails</a>
    <a href="<?=$this->lurl?>/preteurs/portefeuille/<?=$this->lenders_accounts->id_lender_account?>" class="btn_link">Portefeuille & Performances</a></div><br>

    <table class="form" style="margin: auto;">
        <tr>
            <th>Prénom :</th>
            <td><?=$this->clients->prenom?></td>
            <th>Date de création :</th>
            <td><?=$this->dates->formatDate($this->clients->added,'d/m/Y')?></td>
        </tr>
        <tr>
            <th>Nom :</th>
            <td><?=$this->clients->nom?></td>
            <th>Source :</th>
            <td><?=$this->clients->source?></td>
        </tr>
        <tr>
            <th>Email :</th>
            <td><?=$this->clients->email?></td>
            <th>
                Passer hors ligne
            </th>
            <td width="365">
                <img onclick="if(confirm('Voulez vous <?=($this->clients->status==1?'Passer hors ligne':'Passer en ligne')?> ce preteur ?')){window.location = '<?=$this->lurl?>/preteurs/gestion/status/<?=$this->clients->id_client?>/<?=$this->clients->status?>';}" src="<?=$this->surl?>/images/admin/<?=($this->clients->status==1?'offline':'online')?>.png" alt="<?=($this->clients->status==1?'Passer hors ligne':'Passer en ligne')?>" />
            </td>
        </tr>
        <tr>
            <th>Adresse fiscale :</th>
            <?
            if($this->clients->type==1)
            {
                ?><td colspan="5"><?=$this->clients_adresses->adresse_fiscal?> <?=$this->clients_adresses->cp_fiscal?> <?=$this->clients_adresses->ville_fiscal?></td><?
            }
            else
            {
                ?><td colspan="5"><?=$this->companies->adresse1?> <?=$this->companies->zip?> <?=$this->companies->city?></td><?
            }
            ?>
        </tr>
        <tr>
            <th>Téléphone :</th><td><?=$this->clients->telephone?></td>
        </tr>
    </table>
    <br /><br />
    <div class="gauche" style="padding:0px;width: 530px;border-right:0px;">
        <table class="form" style="width:340px;">
            <tr>
                <th>Sommes disponibles :</th>
                <td><?=number_format($this->solde, 2, ',', ' ')?> €</td>
            </tr>

            <tr>
                <th>Montant prêté :</th>
                <td><?=number_format($this->sumPrets, 2, ',', ' ')?> €</td>
            </tr>

            <tr>
                <th>Fonds retirés :</th>
                <td><?=number_format($this->soldeRetrait, 2, ',', ' ')?> €</td>
            </tr>

            <tr>
                <th>Remboursement prochain mois :</th>
                <td><?=number_format($this->nextRemb, 2, ',', ' ')?> €</td>
            </tr>

            <tr>
                <th>Enchères moyennes :</th>
                <td><?=number_format($this->avgPreteur, 2, ',', ' ')?> €</td>
            </tr>

            <tr>
                <th>Montant des intérêts :</th>
                <td><?=number_format($this->sumRembInte, 2, ',', ' ')?> €</td>
            </tr>

            <tr>
                <th>Défaut :</th>
                <td>Non</td>
            </tr>
            <tr>
                <th>Exonéré :</th>
                <td><?=($this->lenders_accounts->exonere == 1?'Oui':'Non')?></td>
            </tr>
        </table>
    </div>

    <div class="droite" style="padding:0px;width: 530px;">
        <table class="form" style="width:265px;">
            <tr>
                <th>Total des sommes déposées :</th>
                <td><?=number_format($this->SumDepot, 2, ',', ' ')?> €</td>
            </tr>
            <tr>
                <th>Montant encheres en cours :</th>
                <td><?=number_format($this->sumBidsEncours, 2, ',', ' ')?> €</td>
            </tr>
            <tr>
                <th>Nombre d'encheres en cours :</th>
                <td><?=$this->NbBids?></td>
            </tr>
            <tr>
                <th>Nombre de prêts :</th>
                <td><?=$this->nb_pret?></td>
            </tr>
            <tr>
                <th>Montant du 1er versement :</th>
                <td><?=number_format($this->SumInscription, 2, ',', ' ')?> €</td>
            </tr>
            <tr>
                <th>Taux moyen :</th>
                <td><?=number_format($this->txMoyen, 2, ',', ' ')?> %</td>
            </tr>
            <tr>
                <th>Remboursement total :</th>
                <td><?=number_format($this->sumRembMontant, 2, ',', ' ')?> €</td>
            </tr>
        </table>
    </div>

    <div style="clear:both;"></div>




    <br /><br />
    <h2>Pièces jointes :</h2>
    <table class="form" style="width: auto;">
        <tr>
            <th>Type de fichier</th>
            <th>Nom (cliquer pour télécharger)</th>
        </tr>
        <tr>
            <th>CNI/Passeport</th>
            <td>
                <a href="<?=$this->lurl?>/protected/cni_passeport_lender/<?=(isset($this->attachments[attachment_type::CNI_PASSPORTE])) ? $this->attachments[attachment_type::CNI_PASSPORTE]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::CNI_PASSPORTE])) ? $this->attachments[attachment_type::CNI_PASSPORTE]["path"] : ''?></a>
            </td>
        </tr>
        <tr>
            <th>CNI/Passeport verso</th>
            <td>
                <a href="<?=$this->lurl?>/protected/cni_passeport_verso_lender/<?=(isset($this->attachments[attachment_type::CNI_PASSPORTE_VERSO])) ? $this->attachments[attachment_type::CNI_PASSPORTE_VERSO]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::CNI_PASSPORTE_VERSO])) ? $this->attachments[attachment_type::CNI_PASSPORTE_VERSO]["path"] : ''?></a>
            </td>
        </tr>
        <tr>
            <th>Justificatif de domicile</th>
            <td>
                <a href="<?=$this->lurl?>/protected/justificatif_domicile_lender/<?=(isset($this->attachments[attachment_type::JUSTIFICATIF_DOMICILE])) ? $this->attachments[attachment_type::JUSTIFICATIF_DOMICILE]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::JUSTIFICATIF_DOMICILE])) ? $this->attachments[attachment_type::JUSTIFICATIF_DOMICILE]["path"] : ''?></a>
            </td>
        </tr>
        <tr>
            <th>RIB</th>
            <td><a href="<?=$this->lurl?>/protected/rib_lender/<?=(isset($this->attachments[attachment_type::RIB])) ? $this->attachments[attachment_type::RIB]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::RIB])) ? $this->attachments[attachment_type::RIB]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Attestation d'hébergement par un tiers</th>
            <td>
                <a href="<?=$this->lurl?>/protected/attestation_hebergement_tiers_lender/<?=(isset($this->attachments[attachment_type::ATTESTATION_HEBERGEMENT_TIERS])) ? $this->attachments[attachment_type::ATTESTATION_HEBERGEMENT_TIERS]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::ATTESTATION_HEBERGEMENT_TIERS])) ? $this->attachments[attachment_type::ATTESTATION_HEBERGEMENT_TIERS]["path"] : ''?></a>
            </td>
        </tr>
        <tr>
            <th>CNI ou passport du tiers hébergeant</th>
            <td>
                <a href="<?=$this->lurl?>/protected/cni_passport_tiers_hebergeant_lender/<?=(isset($this->attachments[attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT])) ? $this->attachments[attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT])) ? $this->attachments[attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>CNI/Passeport dirigeant</th>
            <td><a href="<?=$this->lurl?>/protected/cni_passeport_dirigent_lender/<?=(isset($this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT])) ? $this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT])) ? $this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Délégation de pouvoir</th>
            <td><a href="<?=$this->lurl?>/protected/delegation_pouvoir_lender/<?=(isset($this->attachments[attachment_type::DELEGATION_POUVOIR])) ? $this->attachments[attachment_type::DELEGATION_POUVOIR]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::DELEGATION_POUVOIR])) ? $this->attachments[attachment_type::DELEGATION_POUVOIR]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Extrait kbis</th>
            <td><a href="<?=$this->lurl?>/protected/extrait_kbis_lender/<?=(isset($this->attachments[attachment_type::KBIS])) ? $this->attachments[attachment_type::KBIS]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::KBIS])) ? $this->attachments[attachment_type::KBIS]["path"] : ''?></a></td>
        </tr>

        <tr>
            <th>Mandat</th>
            <td>
                <?
                if($this->clients_mandats->get($this->clients->id_client,'id_client'))
                {
                ?>
                    <a href="<?=$this->lurl?>/protected/mandat_preteur/<?=(isset($this->clients_mandats)) ? $this->clients_mandats->name : ''?>">
                    <?=(isset($this->clients_mandats)) ? $this->clients_mandats->name : ''?></a>
                <?
                }
                ?>
            </td>
        </tr>
        <tr>
            <th>Document fiscal</th>
            <td><a href="<?=$this->lurl?>/protected/document_fiscal_preteur/<?=(isset($this->attachments[attachment_type::JUSTIFICATIF_FISCAL])) ? $this->attachments[attachment_type::JUSTIFICATIF_FISCAL]["path"] : ''?>">
                    <?=(isset($this->attachments[attachment_type::JUSTIFICATIF_FISCAL])) ? $this->attachments[attachment_type::JUSTIFICATIF_FISCAL]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Autre 1</th>
            <td><a href="<?=$this->lurl?>/protected/autre_lender/<?=(isset($this->attachments[attachment_type::AUTRE1])) ? $this->attachments[attachment_type::AUTRE1]["path"] : ''?>"><?=(isset($this->attachments[attachment_type::AUTRE1])) ? $this->attachments[attachment_type::AUTRE1]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Autre 2</th>
            <td><a href="<?=$this->lurl?>/protected/autre2_lender/<?=(isset($this->attachments[attachment_type::AUTRE2])) ? $this->attachments[attachment_type::AUTRE2]["path"] : ''?>"><?=(isset($this->attachments[attachment_type::AUTRE2])) ? $this->attachments[attachment_type::AUTRE2]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Autre 3</th>
            <td><a href="<?=$this->lurl?>/protected/autre3_lender/<?=(isset($this->attachments[attachment_type::AUTRE3])) ? $this->attachments[attachment_type::AUTRE3]["path"] : ''?>"><?=(isset($this->attachments[attachment_type::AUTRE3])) ? $this->attachments[attachment_type::AUTRE3]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Dispense de prélèvement 2014</th>
            <td><a href="<?=$this->lurl?>/protected/dispense_prelevement_2014_lender/<?=(isset($this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2014])) ? $this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2014]["path"] : ''?>"><?=(isset($this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2014])) ? $this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2014]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Dispense de prélèvement 2015</th>
            <td><a href="<?=$this->lurl?>/protected/dispense_prelevement_2015_lender/<?=(isset($this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2015])) ? $this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2015]["path"] : ''?>"><?=(isset($this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2015])) ? $this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2015]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Dispense de prélèvement 2016</th>
            <td><a href="<?=$this->lurl?>/protected/dispense_prelevement_2016_lender/<?=(isset($this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2016])) ? $this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2016]["path"] : ''?>"><?=(isset($this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2016])) ? $this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2016]["path"] : ''?></a></td>
        </tr>
        <tr>
            <th>Dispense de prélèvement 2017</th>
            <td><a href="<?=$this->lurl?>/protected/dispense_prelevement_2017_lender/<?=(isset($this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2017])) ? $this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2017]["path"] : ''?>"><?=(isset($this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2017])) ? $this->attachments[attachment_type::DISPENSE_PRELEVEMENT_2017]["path"] : ''?></a></td>
        </tr>
    </table>
    <br /><br />
    <h2>Mouvements</h2>
    <div class="btnDroite">
        <select name="anneeMouvTransac" id="anneeMouvTransac" class="select" style="width:95px;">
            <?
            for($i=date('Y');$i>=2008;$i--)
            {
                ?><option value="<?=$i?>"><?=$i?></option><?
            }
            ?>
        </select>
    </div>
    <div class="MouvTransac">
        <?
        if(count($this->lTrans) > 0)
        {
            ?>
            <table class="tablesorter transac">
                <thead>
                <tr>
                    <th>Type d'approvisionnement</th>
                    <th>Date de l'opération</th>
                    <th>Montant de l'opération</th>
                </tr>
                </thead>
                <tbody>
                <?
                $i = 1;
                foreach($this->lTrans as $t)
                {
                    if($t['type_transaction'] == 5)
                    {
                        $this->echeanciers->get($t['id_echeancier'],'id_echeancier');
                        $this->projects->get($this->echeanciers->id_project,'id_project');
                        $this->companies->get($this->projects->id_company,'id_company');
                    }
                    elseif ($t['type_transaction'] == 23)
                    {
                        $this->projects->get($t['id_project'], 'id_project');
                        $this->companies->get($this->projects->id_company, 'id_company');
                    }

                    $type = "";
                    if($t['type_transaction'] == 8 && $t['montant'] > 0)
                    {
                        $type = "Annulation retrait des fonds - compte bancaire clos";
                    }
                    else
                    {
                        $type = $this->lesStatuts[$t['type_transaction']].($t['type_transaction'] == 5?' - '.$this->companies->name:'');
                    }

                    ?>
                    <tr<?=($i%2 == 1?'':' class="odd"')?>>
                        <td><?= $this->lesStatuts[$t['type_transaction']] . ($t['type_transaction'] == 5 || $t['type_transaction'] == 23 ? ' - ' . $this->companies->name : '') ?></td>
                        <td><?=$this->dates->formatDate($t['date_transaction'],'d-m-Y')?></td>
                        <td><?=number_format($t['montant']/100, 2, ',', ' ')?> €</td>
                    </tr>
                    <?
                    $i++;
                }
                ?>
                </tbody>
            </table>
            <?
            if($this->nb_lignes != '')
            {
                ?>
                <table>
                    <tr>
                        <td id="pager">
                            <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                            <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                            <input type="text" class="pagedisplay" />
                            <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                            <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                            <select class="pagesize">
                                <option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?
            }
        }
        ?>
    </div>
    <div class="lesbidsEncours">
        <h2>Suivi des enchères en cours</h2>
        <?
        if(count($this->lBids) > 0)
        {
            ?>
            <table class="tablesorter bidsEncours">
                <thead>
                <tr>
                    <th>id bid</th>
                    <th>Projet</th>
                    <th>Date</th>
                    <th>Montant enchere (€)</th>
                    <th>Taux</th>
                    <th>Nbre de mois</th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?
                $i = 1;
                foreach($this->lBids as $e)
                {

                    $this->projects->get($e['id_project'],'id_project');

                    ?>
                    <tr<?=($i%2 == 1?'':' class="odd"')?>>
                        <td align="center"><?=$e['id_bid']?></td>
                        <td><a href="<?=$this->lurl?>/dossiers/edit/<?=$this->projects->id_project?>"><?=$this->projects->title_bo?></a></td>
                        <td><?=date('d/m/Y',strtotime($e['added']))?></td>
                        <td align="center"><?=number_format($e['amount']/100, 2, '.', ' ')?></td>
                        <td align="center"><?=number_format($e['rate'], 2, '.', ' ')?> %</td>
                        <td align="center"><?=$this->projects->period?></td>

                        <td align="center">
                            <img style="cursor:pointer;" onclick="deleteBid(<?=$e['id_bid']?>);" src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer" />
                        </td>
                    </tr>
                    <?
                    $i++;
                }
                ?>
                </tbody>
            </table>
            <?
            if($this->nb_lignes != '')
            {
                ?>
                <table>
                    <tr>
                        <td id="pager">
                            <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                            <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                            <input type="text" class="pagedisplay" />
                            <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                            <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                            <select class="pagesize">
                                <option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?
            }
        }
        ?>
    </div>

    <br /><br />
    <h2>Suivi des enchères</h2>
    <div class="btnDroite">
        <select name="annee" id="annee" class="select" style="width:95px;">
            <?
            for($i=date('Y');$i>=2008;$i--)
            {
                ?><option <?=(isset($this->params[1]) && $this->params[1] == $i?'selected':'')?> value="<?=$i?>"><?=$i?></option><?
            }
            ?>
        </select>
        <a id="changeDate" href="<?=$this->lurl?>/preteurs/edit/<?=$this->params[0]?>/2013" class="btn_link">OK</a>
    </div>
    <?
    if(count($this->lEncheres) > 0)
    {
        ?>
        <table class="tablesorter encheres">
            <thead>
            <tr>
                <th>Année</th>
                <th>Projet</th>
                <th>Montant Prêt (€)</th>
                <th>Pourcentage</th>
                <th>Nbre de mois</th>
                <th>Remboursement (€)</th>
                <th>Contrat</th>
            </tr>
            </thead>
            <tbody>
            <?
            $i = 1;
            foreach($this->lEncheres as $e)
            {

                $year = $this->dates->formatDate($e['added'],'Y');
                $this->projects->get($e['id_project'],'id_project');
                $sumMontant = $this->echeanciers->getSum($e['id_loan']);

                ?>
                <tr<?=($i%2 == 1?'':' class="odd"')?>>
                    <td align="center"><?=$year?></td>
                    <td><a href="<?=$this->lurl?>/dossiers/edit/<?=$this->projects->id_project?>"><?=$this->projects->title_bo?></a></td>
                    <td align="center"><?=number_format($e['amount']/100, 2, '.', ' ')?></td>
                    <td align="center"><?=number_format($e['rate'], 2, '.', ' ')?> %</td>
                    <td align="center"><?=$this->projects->period?></td>
                    <td align="center"><?=number_format($sumMontant, 2, '.', ' ')?></td>
                    <td align="center"><a href="<?=$this->furl.'/pdf/contrat/'.$this->clients->hash.'/'.$e['id_loan']?>" >PDF</a></td>
                </tr>
                <?
                $i++;
            }
            ?>
            </tbody>
        </table>
        <?
        if($this->nb_lignes != '')
        {
            ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay" />
                        <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
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

    $("#anneeMouvTransac").change(function() {

        var val = {
            id_client: <?=$this->clients->id_client?>,
            year: $(this).val()
        }
        $.post(add_url + '/ajax/loadMouvTransac', val).done(function(data) {
            if(data != 'nok')
            {
                $(".MouvTransac").html(data);

            }
        });
    });


    function deleteBid(id_bid)
    {
        if(confirm('Etes vous sur de vouloir supprimer ce bid ?'))
        {
            var val = {
                id_bid: id_bid,
                id_lender: <?=$this->lenders_accounts->id_lender_account?>
            }
            $.post(add_url + '/ajax/deleteBidPreteur', val).done(function(data) {


                if(data != 'nok')
                {

                    $(".lesbidsEncours").html(data);

                }
            });

        }


    }
</script>
