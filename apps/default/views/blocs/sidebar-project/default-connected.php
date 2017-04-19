<div class="sidebar right">
    <aside class="widget widget-price">
        <div class="widget-top">
            <i class="icon-pig"></i>
            <?= $this->ficelle->formatNumber($this->projects->amount, 0) ?>&nbsp;€
        </div>
        <div class="widget-body">
            <form action="" method="post">
                <div class="widget-cat progress-cat clearfix">
                    <div class="prices clearfix">
                        <span class="price less">
                            <strong><?= $this->ficelle->formatNumber($this->payer, 0) ?>&nbsp;€</strong>
                            <?= $this->lng['preteur-projets']['de-pretes'] ?>
                        </span>
                        <i class="icon-arrow-gt"></i>
                        <?php if ($this->soldeBid >= $this->projects->amount) { ?>
                            <p style="font-size:14px;">
                                <?= $this->lng['preteur-projets']['vous-pouvez-encore-preter-en-proposant-une-offre-de-pret-inferieure-a'] ?>
                                <?= $this->ficelle->formatNumber($this->txLenderMax, 1) ?>&nbsp;%
                            </p>
                        <?php } else { ?>
                            <span class="price">
                                <strong><?= $this->ficelle->formatNumber($this->resteApayer, 0) ?>&nbsp;€</strong>
                                <?= $this->lng['preteur-projets']['restent-a-preter'] ?>
                            </span>
                        <?php } ?>
                    </div>
                    <div class="progressBar" data-percent="<?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>">
                        <div><span></span></div>
                    </div>
                </div>
                <?php if ($this->bidsEncours['nbEncours'] > 0) { ?>
                    <div class="widget-cat" id="current-offer">
                        <h4 id="plusOffres">
                            <?= $this->bidsEncours['nbEncours'] ?> <?= $this->lng['preteur-projets']['offres-en-cours-pour'] ?> <?= $this->ficelle->formatNumber($this->bidsEncours['solde'], 0) ?>&nbsp;€
                        </h4>
                        <div id="lOffres" class="show-scrollbar">
                            <div class="orders-table-wrapper">
                                <table class="table orders-table" style="font-size: 0.8em;" id="bids">
                                    <thead>
                                        <tr style="text-align: center; color: white;">
                                            <th width="20%">
                                                <span id="triNum">N°</span>
                                            </th>
                                            <th width="40%">
                                                <span id="triTx"><?= $this->lng['preteur-projets']['taux-dinteret'] ?></span>
                                            </th>
                                            <th width="20%">
                                                <span id="triAmount"><?= $this->lng['preteur-projets']['montant'] ?></span>
                                            </th>
                                            <th width="20%">
                                                <span id="triStatuts"><?= $this->lng['preteur-projets']['statuts'] ?></span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($this->lBids as $aBid) : ?>
                                        <tr>
                                            <td>
                                                <span class="<?= (empty($aBid['id_autobid']) || false == $this->bIsAllowedToSeeAutobid) ? 'no_autobid' : 'autobid' ?>">A</span>
                                                <?= $aBid['ordre'] ?>
                                            </td>
                                            <td style="white-space: nowrap"><?= $this->ficelle->formatNumber($aBid['rate'], 1) ?>&nbsp;%</td>
                                            <td style="white-space: nowrap"><?= $this->ficelle->formatNumber($aBid['amount'] / 100, 0) ?>&nbsp;€
                                            </td>
                                            <td><span>
                                                <span class="<?= ($aBid['status'] == \bids::STATUS_BID_PENDING ? 'circle_pending' : ($aBid['status'] == \bids::STATUS_BID_REJECTED ? 'circle_rejected' : '')) ?>"></span></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($this->bIsLender) { ?>
                    <div class="widget-cat">
                        <h4><?= $this->lng['preteur-projets']['faire-une-offre'] ?></h4>
                        <div class="row">
                            <label><?= $this->lng['preteur-projets']['je-prete-a'] ?></label>
                            <select name="tx_p" id="tx_p" class="custom-select field-hundred">
                                <?php $adjustment = ($this->soldeBid < $this->projects->amount) ? 0 : 0.1 ?>
                                <option value="<?= $this->txLenderMax - $adjustment ?>"><?= $this->ficelle->formatNumber($this->txLenderMax - $adjustment, 1) ?>&nbsp;%</option>
                                <?php foreach (range($this->rateRange['rate_max'], $this->rateRange['rate_min'], -0.1) as $index => $fRate) :?>
                                    <?php if ($this->soldeBid < $this->projects->amount || round($fRate, 1) < round($this->txLenderMax, 1)) : ?>
                                        <option value="<?= $fRate ?>"><?= $this->ficelle->formatNumber($fRate, 1) ?>&nbsp;%</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row last-row">
                            <label><?= $this->lng['preteur-projets']['la-somme-de'] ?></label>
                            <input name="montant_p" id="montant_p" type="text" placeholder="<?= $this->lng['preteur-projets']['montant-exemple'] ?>" class="field" onkeyup="lisibilite_nombre(this.value,this.id);"/>
                            <span style="margin-left: -15px;position: relative;top: 4px;">€</span>
                        </div>
                        <p class="laMensual" style="font-size:14px;display:none;"><?= $this->lng['preteur-projets']['soit-un-remboursement-mensuel-de'] ?></p>
                        <div class="laMensual" style="font-size:14px;width:245px;display:none;">
                            <div style="text-align:center;"><span id="mensualite">xx</span>&nbsp;€</div>
                        </div>
                        <br/>

                        <?php
                        // on check si on a coché les cgv ou pas
                        // cgu societe
                        if (in_array($this->clients->type, array(\Unilend\Bundle\CoreBusinessBundle\Entity\Clients::TYPE_LEGAL_ENTITY, \Unilend\Bundle\CoreBusinessBundle\Entity\Clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
                            $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
                            $this->lienConditionsGenerales_header = $this->settings->value;
                        } // cgu particulier
                        else {
                            $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
                            $this->lienConditionsGenerales_header = $this->settings->value;
                        }

                        $listeAccept_header = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);
                        $this->update_accept_header = false;

                        if (in_array($this->lienConditionsGenerales, $listeAccept_header)) {
                            $this->accept_ok_header = true;
                        } else {
                            $this->accept_ok_header = false;
                            // Si on a deja des cgv d'accepté
                            if ($listeAccept_header != false) {
                                $this->update_accept_header = true;
                            }
                        }
                        ?>

                        <a style="width:76px; display:block;margin:auto;" href="<?= (! $this->accept_ok_header ? $this->lurl . '/thickbox/pop_up_cgv' : $this->lurl . '/thickbox/pop_valid_pret/' . $this->projects->id_project) ?>" class="btn btn-medium popup-link <?= (! $this->accept_ok_header ? 'thickbox' : '') ?>"><?= $this->lng['preteur-projets']['preter'] ?></a>
                    </div>
                <?php } ?>
            </form>
        </div>
    </aside>
</div>
