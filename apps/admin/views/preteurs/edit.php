<script type="text/javascript">
    $(function() {
        jQuery.tablesorter.addParser({ id: "fancyNumber", is: function(s) { return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s); }, format: function(s) { return jQuery.tablesorter.formatFloat( s.replace(/,/g,'').replace(' €','').replace(' ','') ); }, type: "numeric" });

        $(".encheres").tablesorter({headers: {6: {sorter: false}}});
        $(".mandats").tablesorter({headers: {}});
        $(".bidsEncours").tablesorter({headers: {6: {sorter: false}}});
        $(".transac").tablesorter({headers: {}});
        $(".favoris").tablesorter({headers: {3: {sorter: false}}});
        <?php if ($this->nb_lignes != '') : ?>
            $(".encheres").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
            $(".mandats").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
        $("#annee").change(function () {
            $('#changeDate').attr('href', "<?= $this->lurl ?>/preteurs/edit/<?=$this->params[0]?>/" + $(this).val());
        });

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {},
                container;

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
    $(function () {
        $('#btn-show-lender-vigilance-history').click(function () {
            $('#lender-vigilance-history').toggle();
            $(this).text(function (i, text) {
                return text === 'Voir l\'historique de vigilance' ? 'Cacher l\'historique' : 'Voir l\'historique de vigilance'
            })
        })
    })
    $(function () {
        $('#btn-show-lender-atypical-operation').click(function () {
            $('#lender-atypical-operation').toggle();
            $(this).text(function (i, text) {
                return text === 'Voir les détections' ? 'Cacher les détections' : 'Voir les détections'
            })
        })
    })
</script>
<style>
    .td-greenPoint-status-valid {
        border-radius: 5px; background-color: #00A000; color: white; width: auto; padding: 5px;
    }
    .td-greenPoint-status-warning {
        border-radius: 5px; background-color: #f79232; color: white; width: auto; padding: 5px;
    }
    .td-greenPoint-status-error {
        border-radius: 5px; background-color: #ff0100; color: white; width: auto; padding: 5px;
    }
    table.attachment-list td, th{
        vertical-align: middle;
    }
</style>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <?php if (empty($this->clients->id_client)) : ?>
        <div class="attention">Attention : Compte <?= $this->params[0] ?> innconu</div>
    <?php else : ?>
        <div><?= $this->clientStatusMessage ?></div>
        <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>
        <div class="btnDroite">
            <a href="<?= $this->lurl ?>/preteurs/bids/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Enchères</a>
            <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Modifier Prêteur</a>
            <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Historique des emails</a>
            <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Portefeuille & Performances</a></div>
        <br>
        <table class="form" style="margin: auto;">
            <tr>
                <th>ID Client :</th>
                <td><?= $this->clients->id_client ?></td>
                <th>Date de création :</th>
                <td><?= $this->dates->formatDate($this->clients->added, 'd/m/Y') ?></td>
            </tr>
            <tr>
                <th>Prénom :</th>
                <td><?= $this->clients->prenom ?></td>
                <th>Source :</th>
                <td><?= $this->clients->source ?></td>
            </tr>
            <tr>
                <th>Nom :</th>
                <td><?= $this->clients->nom ?></td>
                <th></th>
                <td></td>
            </tr>
            <tr>
                <th>Email :</th>
                <td><?= $this->clients->email ?></td>
                <th></th>
                <td width="365"></td>
            </tr>
            <tr>
                <th>Adresse fiscale :</th>
                <?php if ($this->clients->type == 1) : ?>
                    <td colspan="5"><?= $this->clients_adresses->adresse_fiscal ?> <?= $this->clients_adresses->cp_fiscal ?> <?= $this->clients_adresses->ville_fiscal ?></td>
                <?php else : ?>
                    <td colspan="5"><?= $this->companies->adresse1 ?> <?= $this->companies->zip ?> <?= $this->companies->city ?></td>
                <?php endif; ?>
            </tr>
            <tr>
                <th>Téléphone / Mobile :</th>
                <td><?= $this->clients->telephone ?> / <?= $this->clients->mobile?></td>
            </tr>
        </table>
        <br/><br/>
        <div class="gauche" style="padding:0px;width: 530px;border-right:0px;">
            <table class="form" style="width:340px;">
                <tr>
                    <th>Sommes disponibles :</th>
                    <td><?= $this->ficelle->formatNumber($this->solde) ?> €</td>
                </tr>
                <tr>
                    <th>Montant prêté :</th>
                    <td><?= $this->ficelle->formatNumber($this->sumPrets) ?> €</td>
                </tr>
                <tr>
                    <th>Fonds retirés :</th>
                    <td><?= $this->ficelle->formatNumber($this->soldeRetrait) ?> €</td>
                </tr>
                <tr>
                    <th>Remboursement prochain mois :</th>
                    <td><?= $this->ficelle->formatNumber($this->nextRemb) ?> €</td>
                </tr>
                <tr>
                    <th>Enchères moyennes :</th>
                    <td><?= $this->ficelle->formatNumber($this->avgPreteur) ?> €</td>
                </tr>
                <tr>
                    <th>Montant des intérêts (brut) :</th>
                    <td><?= $this->ficelle->formatNumber($this->sumRembInte) ?> €</td>
                </tr>
                <tr>
                    <th>Défaut :</th>
                    <td>Non</td>
                </tr>
                <tr>
                    <th>Exonéré :</th>
                    <td>
                        <?php if (empty($this->aExemptionYears)) : ?>
                            Non
                        <?php else : ?>
                            <?= implode('<br>', $this->aExemptionYears) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        <div class="droite" style="padding:0px;width: 530px;">
            <table class="form" style="width:265px;">
                <tr>
                    <th>Total des sommes déposées :</th>
                    <td><?= $this->ficelle->formatNumber($this->SumDepot) ?> €</td>
                </tr>
                <tr>
                    <th>Montant encheres en cours :</th>
                    <td><?= $this->ficelle->formatNumber($this->sumBidsEncours) ?> €</td>
                </tr>
                <tr>
                    <th>Nombre d'encheres en cours :</th>
                    <td><?= $this->NbBids ?></td>
                </tr>
                <tr>
                    <th>Nombre de prêts :</th>
                    <td><?= $this->nb_pret ?></td>
                </tr>
                <tr>
                    <th>Montant du 1er versement :</th>
                    <td><?= $this->ficelle->formatNumber($this->SumInscription) ?> €</td>
                </tr>
                <tr>
                    <th>Taux moyen pondéré :</th>
                    <td><?= $this->ficelle->formatNumber($this->txMoyen) ?> %</td>
                </tr>
                <tr>
                    <th>Remboursement total :</th>
                    <td><?= $this->ficelle->formatNumber($this->sumRembMontant) ?> €</td>
                </tr>
            </table>
        </div>

        <div style="clear:both;"></div>
        <br/><br/>
        <h2>Pièces jointes :</h2>
        <table class="attachment-list" style="width: auto; border-collapse: separate; border-spacing: 2px;">
            <tr>
                <th>Type de fichier</th>
                <th>Nom (cliquer pour télécharger)</th>
                <th>Statut GreenPoint</th>
                <th>&Eacute;tat de validation</th>
            </tr>
            <?php foreach ($this->aAvailableAttachments as $aAttachmentType) : ?>
                <tr style="height: 2em; padding: 2px; ">
                    <th ><?= $aAttachmentType['label'] ?></th>
                    <td>
                        <a href="<?= $this->url ?>/attachment/download/id/<?= $aAttachmentType['id'] ?>/file/<?= urlencode($aAttachmentType['path']) ?>">
                            <?= $aAttachmentType['path'] ?>
                        </a>
                    </td>
                    <td class="td-greenPoint-status-<?= $aAttachmentType['color']?>">
                        <?= $aAttachmentType['greenpoint_label'] ?>
                    </td>
                    <td><?= $aAttachmentType['final_status'] ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th>Mandat</th>
                <td>
                    <?php if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) : ?>
                        <a href="<?= $this->lurl ?>/protected/mandat_preteur/<?= $this->clients_mandats->name ?>"><?= $this->clients_mandats->name ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <br/><br/>
        <?php if (false === empty($this->transferDocuments)) : ?>
            <h2>Document de transfert (en cas de succession)</h2>
            <table class="attachment-list" style="width: auto; border-collapse: separate; border-spacing: 2px;">
                <?php foreach ($this->transferDocuments as $document) : ?>
                    <tr>
                        <td>
                            <a href="<?= $this->url ?>/attachment/download/id/<?= $document['id'] ?>/file/<?= urlencode($document['path']) ?>"><?= $document['path'] ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <h3>Statut de surveillance</h3>
        <div class="attention vigilance-status-<?= $this->vigilanceStatus['status'] ?>" style="margin-left: 0px;color: black;">
            <?= $this->vigilanceStatus['message'] ?>
        </div>
        <?php if (false === empty($this->clientAtypicalOperations)) : ?>
            <button class="btn" id="btn-show-lender-atypical-operation">Voir les détections</button>
        <?php endif; ?>
        <?php if (false === empty($this->vigilanceStatusHistory)) : ?>
            <button class="btn" id="btn-show-lender-vigilance-history">Voir l'historique de vigilance</button>
        <?php endif; ?>
        <a class="thickbox btn" href="<?= $this->lurl ?>/client_atypical_operation/process_detection_box/add/<?= $this->clients->id_client ?>">
            Ajouter
        </a>
        <div id="lender-atypical-operation" style="display: none;">
            <br>
            <h2>Liste des opérations atypiques détéctés</h2>
            <?php if (false === empty($this->clientAtypicalOperations)) : ?>
                <?php
                $this->atypicalOperations = $this->clientAtypicalOperations;
                $this->showActions        = false;
                $this->showUpdated        = true;
                $this->fireView('../client_atypical_operation/detections_table');
                ?>
            <?php endif; ?>
        </div>
        <br>
        <div id="lender-vigilance-history" style="display: none;">
            <br>
            <h2>Historique de vigilance du client</h2>
            <?php if (false === empty($this->clientAtypicalOperations)) : ?>
                <?php $this->fireView('../client_atypical_operation/vigilance_status_history'); ?>
            <?php endif; ?>
        </div>
        <br/><br/>
        <h2>Mouvements</h2>
        <div class="btnDroite">
            <select name="anneeMouvTransac" id="anneeMouvTransac" class="select" style="width:95px;">
                <?php for ($i = date('Y'); $i >= 2008; $i--) : ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="MouvTransac">
            <?php $this->fireView('transactions'); ?>
        </div>
        <div class="lesbidsEncours">
            <h2>Suivi des enchères en cours</h2>
            <?php if (count($this->lBids) > 0) :?>
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
                    <?php
                    $i = 1;
                    foreach ($this->lBids as $e) :
                        $this->projects->get($e['id_project'], 'id_project'); ?>
                        <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                            <td align="center"><?= $e['id_bid'] ?></td>
                            <td>
                                <a href="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>"><?= $this->projects->title_bo ?></a>
                            </td>
                            <td><?= date('d/m/Y', strtotime($e['added'])) ?></td>
                            <td align="center"><?= number_format($e['amount'] / 100, 2, '.', ' ') ?></td>
                            <td align="center"><?= number_format($e['rate'], 2, '.', ' ') ?> %</td>
                            <td align="center"><?= $this->projects->period ?></td>

                            <td align="center">
                                <img style="cursor:pointer;" onclick="deleteBid(<?= $e['id_bid'] ?>);" src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer"/>
                            </td>
                        </tr>
                        <?php
                        $i++;
                    endforeach; ?>
                    </tbody>
                </table>
                <?php if ($this->nb_lignes != '') : ?>
                    <table>
                        <tr>
                            <td id="pager">
                                <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                                <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                                <input type="text" class="pagedisplay"/>
                                <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                                <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                                <select class="pagesize">
                                    <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <br/><br/>
        <h2>Suivi des enchères</h2>
        <div class="btnDroite">
            <select name="annee" id="annee" class="select" style="width:95px;">
                <?php for ($i = date('Y'); $i >= 2008; $i--) : ?>
                    <option <?= (isset($this->params[1]) && $this->params[1] == $i ? 'selected' : '') ?> value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select>
            <a id="changeDate" href="<?= $this->lurl ?>/preteurs/edit/<?= $this->params[0] ?>/2013" class="btn_link">OK</a>
        </div>
        <?php if (count($this->lEncheres) > 0) : ?>
            <table class="tablesorter encheres">
                <thead>
                    <tr>
                        <th>Année</th>
                        <th>Projet</th>
                        <th>Montant prêt (€)</th>
                        <th>Pourcentage</th>
                        <th>Nombre de mois</th>
                        <th>Remboursement (€)</th>
                        <th>Contrat</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                foreach ($this->lEncheres as $e) :
                    $year = $this->dates->formatDate($e['added'], 'Y');
                    $this->projects->get($e['id_project'], 'id_project');
                    $sumMontant = $this->echeanciers->getTotalAmount(array('id_loan' => $e['id_loan']));
                ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td align="center"><?= $year ?></td>
                        <td>
                            <a href="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>"><?= $this->projects->title_bo ?></a>
                        </td>
                        <td align="center"><?= $this->ficelle->formatNumber($e['amount'] / 100, 0) ?></td>
                        <td align="center"><?= $this->ficelle->formatNumber($e['rate'], 1) ?> %</td>
                        <td align="center"><?= $this->projects->period ?></td>
                        <td align="center"><?= $this->ficelle->formatNumber($sumMontant, 2) ?></td>
                        <td align="center">
                            <a href="<?= $this->furl . '/pdf/contrat/' . $this->clients->hash . '/' . $e['id_loan'] ?>">PDF</a>
                        </td>
                    </tr>
                    <?php
                    $i++;
                endforeach; ?>
                </tbody>
            </table>
            <?php if ($this->nb_lignes != '') : ?>
                <table>
                    <tr>
                        <td id="pager">
                            <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                            <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                            <input type="text" class="pagedisplay"/>
                            <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                            <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                            <select class="pagesize">
                                <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script type="text/javascript">
    $("#anneeMouvTransac").change(function() {
        var val = {
            id_client: <?= $this->clients->id_client ?>,
            year: $(this).val()
        };
        $.post(add_url + '/ajax/loadMouvTransac', val).done(function(data) {
            if (data != 'nok') {
                $(".MouvTransac").html(data);
            }
        });
    });

    function deleteBid(id_bid) {
        if (confirm('Etes vous sur de vouloir supprimer ce bid ?')) {
            var val = {
                id_bid: id_bid,
                id_lender: <?= $this->lenders_accounts->id_lender_account ?>
            };
            $.post(add_url + '/ajax/deleteBidPreteur', val).done(function (data) {
                if (data != 'nok') {
                    $(".lesbidsEncours").html(data);
                }
            });
        }
    }
</script>
