<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>SFF docs</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/style.css" type="text/css" media="all"/>
</head>
<body>
<script>var dataLayer = [<?= json_encode($this->aDataLayer) ?>];</script>
<!-- Google Tag Manager -->
<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-MB66VL"
                  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MB66VL');</script>
<!-- End Google Tag Manager -->
<!-- Doc Wrapper -->
<div class="doc-wrapper">
    <!-- Shell -->
    <div class="shell">
        <div>
            <h3>PRINCIPALES CARACTERISTIQUES DU CONTRAT DE PRET</h3>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">
                            Montant total emprunté :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->oLoans->amount / 100) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                        <br/>
                    </li>
                    <li>
                        <div class="col-long">
                            Taux d'intérêt annuel :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->oLoans->rate) ?>&nbsp;%
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Montant total des intérêts :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->fInterestTotal) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Montant de l’échéance (capital et intérêts, hors commission Unilend) :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->lRemb[0]['montant'] / 100) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Date de création :
                        </div>
                        <div class="col-small">
                            <?= $this->dateRemb ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Date d'échéance :
                        </div>
                        <div class="col-small">
                            <?= date('d/m/Y', strtotime($this->dateLastEcheance)) ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                        <br/>
                    </li>
                    <li>
                        Modalités d’amortissement : <?= nl2br($this->bloc_pdf_contrat['modalites-amortissement']) ?>
                        <br/>
                    </li>
                    <li>
                        <div class="col-long">
                            Coût total du prêt pour l’Emprunteur :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->fCommissionRepayment + $this->fCommissionProject + $this->fInterestTotal) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                        <br/>
                    </li>
                    <li>
                        Conditions de mise à disposition des fonds : <?= nl2br($this->bloc_pdf_contrat['conditions-de-mise-a-disposition-des-fonds']) ?> <br/>
                    </li>
                </ul>
            </div>

            <h3><?= $this->bloc_pdf_contrat['titre-contrat-de-pret'] ?> - #<?= $this->oLoans->id_loan ?></h3>
            <h5>Désignation de l'Emprunteur</h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">Raison sociale</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->name ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Forme juridique</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->forme ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Capital social</div>
                        <div class="col-small"><?= (0 < $this->companiesEmprunteur->capital) ? $this->ficelle->formatNumber($this->companiesEmprunteur->capital) : 0 ?>&nbsp;&euro;</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Adresse du siège social</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->adresse1 ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Code postal</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->zip ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Ville</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->city ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Tribunal de commerce</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->tribunal_com ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">R.C.S.</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->siren ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Activité</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->activite ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Lieu d'exploitation</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->lieu_exploi ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                </ul>
            </div>
            <h5>Désignation du prêteur</h5>
            <div class="list">
                <ul>
                    <?php
                    if ($this->clients->type == 1) : // particulier
                    ?>
                        <li>Nom
                            <div class="col-small"><?= $this->clients->nom ?></div>
                        </li>
                        <li>Prénom
                            <div class="col-small"><?= $this->clients->prenom ?></div>
                        </li>
                        <li>Date de naissance
                            <div class="col-small"><?= date('d/m/Y', strtotime($this->clients->naissance)) ?></div>
                        </li>
                        <li>Adresse
                            <div class="col-small"><?= $this->clients_adresses->adresse1 ?></div>
                        </li>
                        <li>Code postal
                            <div class="col-small"><?= $this->clients_adresses->cp ?></div>
                        </li>
                        <li>Ville
                            <div class="col-small"><?= $this->clients_adresses->ville ?></div>
                        </li>
                    <?php
                    else : //morale
                    ?>
                        <li>Raison sociale
                            <div class="col-small"><?= $this->companiesPreteur->name ?></div>
                        </li>
                        <li>Forme juridique
                            <div class="col-small"><?= $this->companiesPreteur->forme ?></div>
                        </li>
                        <li>Capital social
                            <div class="col-small"><?= $this->ficelle->formatNumber($this->companiesPreteur->capital) ?>&nbsp;&euro;</div>
                        </li>
                        <li>Adresse du siège social
                            <div class="col-small"><?= $this->companiesPreteur->adresse1 ?></div>
                        </li>
                        <li>Code postal
                            <div class="col-small"><?= $this->companiesPreteur->zip ?></div>
                        </li>
                        <li>Ville
                            <div class="col-small"><?= $this->companiesPreteur->city ?></div>
                        </li>
                        <li>Tribunal de commerce
                            <div class="col-small"><?= $this->companiesPreteur->tribunal_com ?></div>
                        </li>
                        <li>R.C.S.
                            <div class="col-small"><?= $this->companiesPreteur->siren ?></div>
                        </li>
                    <?php
                    endif;
                    ?>
                </ul>
                <p>En présence de :</p>
                <?= $this->bloc_pdf_contrat['description-unilend'] ?>
            </div>
            <h3>RECONNAISSANCE D’INFORMATION PRECONTRACTUELLE</h3>
            <div><?= $this->bloc_pdf_contrat['information-precontractuelle'] ?></div>
            <h3>CARACTERISTIQUES DU PROJET</h3>
            <div>
                <?= $this->bloc_pdf_contrat['caracteristiques-du-projet'] ?>
                <p><?= $this->projects->nature_project ?></p>
            </div>
            <h3>CARACTERISTIQUES DU PRET</h3>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">
                            Montant total emprunté :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->oLoans->amount / 100) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                        <br/>
                    </li>
                    <li>
                        <div class="col-long">
                            Taux d'intérêt annuel :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->oLoans->rate) ?>&nbsp;%
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Montant total des intérêts :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->fInterestTotal) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Montant de l’échéance (capital et intérêts, hors commission Unilend) :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->lRemb[0]['montant'] / 100) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Date de création :
                        </div>
                        <div class="col-small">
                            <?= $this->dateRemb ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Date d'échéance :
                        </div>
                        <div class="col-small">
                            <?= date('d/m/Y', strtotime($this->dateLastEcheance)) ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                        <br/>
                    </li>
                    <li>
                        Modalités d’amortissement : <?= nl2br($this->bloc_pdf_contrat['modalites-amortissement']) ?>
                        <br/>
                    </li>
                    <li>
                        <div class="col-long">
                            Coût total du prêt pour l’Emprunteur :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->fCommissionRepayment + $this->fCommissionProject + $this->echeanciers->getSumByLoan($this->oLoans->id_loan, 'interets')) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                        <br/>
                    </li>
                    <li>
                        Conditions de mise à disposition des fonds : <?= nl2br($this->bloc_pdf_contrat['conditions-de-mise-a-disposition-des-fonds']) ?> <br/>
                    </li>
                </ul>
                <p><?= $this->bloc_pdf_contrat['legitimite'] ?></p>
                <p>
                    La signature du contrat de prêt engage l'Emprunteur, en contrepartie des sommes remises ce jour,
                    à rembourser au Prêteur la somme de <?= $this->ficelle->formatNumber($this->oLoans->amount / 100) ?>&nbsp;&euro;
                    assortie des intérêts à <?= $this->ficelle->formatNumber($this->oLoans->rate) ?> % selon l'échéancier annexé aux présentes.
                </p>
            </div>
        </div>
        <div>
            <h3>DERNIER BILAN CERTIFIE SINCERE DE L’EMPRUNTEUR</h3>
            <h5>Actif</h5>
            <div class="list">
                <ul>
                    <li>Immobilisations corporelles :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['immobilisations_corporelles']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Immobilisations incorporelles :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['immobilisations_incorporelles']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Immobilisations financières :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['immobilisations_financieres']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Stocks :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['stocks']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Créances clients :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['creances_clients']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Disponibilités :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['disponibilites']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Valeurs mobilières de placement :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['valeurs_mobilieres_de_placement']) ?>&nbsp;&euro;</div>
                    </li>
                </ul>
            </div>
            <div class="total-row" style="white-space:nowrap; text-align:left;">
                Total actif :
                <div style="display:inline;float: right;"><?= $this->ficelle->formatNumber($this->totalActif) ?>&nbsp;&euro;</div>
            </div>
            <br>
            <h5>Passif</h5>
            <div class="list">
                <ul>
                    <li>Capitaux propres :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['capitaux_propres']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Provisions pour risques et charges :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['provisions_pour_risques_et_charges']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Amortissements sur immobilisations  :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['amortissement_sur_immo']) ?>&nbsp;&euro;</div>
                    </li>

                    <li>Dettes financières :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['dettes_financieres']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Dettes fournisseurs :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['dettes_fournisseurs']) ?>&nbsp;&euro;</div>
                    </li>
                    <li>Autres dettes :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['autres_dettes']) ?>&nbsp;&euro;</div>
                    </li>
                </ul>
            </div>
            <div class="total-row" style="white-space:nowrap; text-align:left;">
                Total passif :
                <div style="display:inline;float: right;"><?= $this->ficelle->formatNumber($this->totalPassif) ?>&nbsp;&euro;</div>
            </div>
            <br><br>
            <?= $this->bloc_pdf_contrat['conditions-ifp'] ?>
            <br>
            <br>
            <br>
            <br>
            <br>
            <p>Fait à Paris, le <?= date('d/m/Y') ?></p>
            <p>Signé par l'Emprunteur Représenté par SFF PME</p>
            <p>Signé par le Prêteur Représenté par SFF PME</p>
        </div>
        <!-- End Page Break -->

        <!-- Page Break -->
        <div class="pageBreakBefore">
            <h3>ECHEANCIER DES REMBOURSEMENTS</h3>

            <div class="dates-table">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <th valign="bottom">DATE</th>
                        <th valign="bottom">CAPITAL</th>
                        <th valign="bottom">INTERETS</th>
                        <th valign="bottom">TOTAL</th>
                        <th valign="bottom">CAPITAL RESTANT DÛ</th>
                    </tr>
                    <?php
                    $capRestant = $this->capital;
                    foreach ($this->lRemb as $r) :
                        $capRestant -= $r['capital'];
                        if ($capRestant < 0) {
                            $capRestant = 0;
                        }
                    ?>
                        <tr>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->dates->formatDate($r['date_echeance'], 'd/m/Y') ?></td>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->ficelle->formatNumber($r['capital'] / 100) ?>&nbsp;&euro;</td>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->ficelle->formatNumber($r['interets'] / 100) ?>&nbsp;&euro;</td>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->ficelle->formatNumber($r['montant'] / 100) ?>&nbsp;&euro;</td>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->ficelle->formatNumber($capRestant / 100) ?>&nbsp;&euro;</td>
                        </tr>
                    <?php
                    endforeach;
                    ?>

                </table>
            </div>
        </div>
        <!-- End Page Break -->
    </div>
    <!-- End Shell -->
</div>
<!-- End Doc Wrapper -->

</body>
</html>