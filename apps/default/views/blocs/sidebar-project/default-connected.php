<style>
    table#bids td {
        padding: 8px;
    }

    #bids.autobid {
        -webkit-border-radius: 6px;
        -moz-border-radius: 6px;
        border-radius: 6px;
        color: #ffffff;
        font-weight: bold;
        background: #d9aa34;
        padding: 3px 6px 3px 6px;
        text-decoration: none;
    }

    #bids.no_autobid {
        padding: 3px 6px 3px 6px;
        background: transparent;
        color: transparent;
        font-weight: bold;
    }


</style>
<div class="sidebar right">
    <aside class="widget widget-price">
        <div class="widget-top">
            <i class="icon-pig"></i>
            <?= $this->ficelle->formatNumber($this->projects->amount, 0) ?> €
        </div>
        <div class="widget-body">
            <form action="" method="post">
                <div class="widget-cat progress-cat clearfix">
                    <div class="prices clearfix">
                        <span class="price less">
                            <strong><?= $this->ficelle->formatNumber($this->payer, $this->decimales) ?> €</strong>
                            <?= $this->lng['preteur-projets']['de-pretes'] ?>
                        </span>
                        <i class="icon-arrow-gt"></i>
                        <?php if ($this->soldeBid >= $this->projects->amount) { ?>
                            <p style="font-size:14px;">
                                <?= $this->lng['preteur-projets']['vous-pouvez-encore-preter-en-proposant-une-offre-de-pret-inferieure-a'] ?>
                                <?= $this->ficelle->formatNumber($this->txLenderMax, 1) ?>%
                            </p>
                        <?php } else { ?>
                            <span class="price">
                                <strong><?= $this->ficelle->formatNumber($this->resteApayer, $this->decimales) ?> €</strong>
                                <?= $this->lng['preteur-projets']['restent-a-preter'] ?>
                            </span>
                        <?php } ?>
                    </div>
                    <div class="progressBar" data-percent="<?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>">
                        <div><span></span></div>
                    </div>
                </div>
                <?php if ($this->bidsEncours['nbEncours'] > 0) { ?>
                    <div class="widget-cat">
                        <h4 id="plusOffres">
                            <?= $this->bidsEncours['nbEncours'] ?> <?= $this->lng['preteur-projets']['offres-en-cours-pour'] ?> <?= $this->ficelle->formatNumber($this->bidsEncours['solde'], 0) ?> €
                        </h4>
                        <div id="lOffres">
                            <table class="table orders-table" style="font-size: 0.8em;" id="bids">
                                <thead>
                                    <tr style="text-align: center; color: white;">
                                        <th width="30%" style="padding-left: 30px;">
                                            <span id="triNum">N°</span>
                                        </th>
                                        <th width="25%">
                                            <span id="triTx"><?= $this->lng['preteur-projets']['taux-dinteret'] ?></span>
                                        </th>
                                        <th width="25%">
                                            <span id="triAmount"><?= $this->lng['preteur-projets']['montant'] ?></span>
                                        </th>
                                        <th width="20%">
                                            <span id="triStatuts"><?= $this->lng['preteur-projets']['statuts'] ?></span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="<?= ($this->bidsEncours['nbEncours'] > 3 ) ? 'show-scrollbar' : '' ?>">
                            <?php foreach ($this->lBids as $aBid) : ?>
                                <tr>
                                    <td>
                                        <span class="<?= (empty($aBid['id_autobid'])) ? 'no_autobid' : 'autobid' ?>">A</span>
                                        <?= $aBid['ordre'] ?>
                                    </td>
                                    <td style="white-space: nowrap"><?= $this->ficelle->formatNumber($aBid['rate'], 1) ?> %</td>
                                    <td style="white-space: nowrap"><?= $this->ficelle->formatNumber($aBid['amount'] / 100, 0) ?>€
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
                <?php } ?>
                <?php if ($this->clients->status_pre_emp != 2) { ?>
                    <div class="widget-cat">
                        <h4><?= $this->lng['preteur-projets']['faire-une-offre'] ?></h4>
                        <div class="row">
                            <label><?= $this->lng['preteur-projets']['je-prete-a'] ?></label>
                            <select name="tx_p" id="tx_p" class="custom-select field-hundred">
                                <option value="<?= $this->projects->target_rate ?>"><?= $this->projects->target_rate ?></option>
                                <?php foreach (range(10, 4, -0.1) as $fRate) { ?>
                                    <?php if ($this->soldeBid < $this->projects->amount || $fRate < round($this->txLenderMax, 1)) { ?>
                                        <option value="<?= $fRate ?>"><?= $this->ficelle->formatNumber($fRate, 1) ?>%</option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="row last-row">
                            <label><?= $this->lng['preteur-projets']['la-somme-de'] ?></label>
                            <input name="montant_p" id="montant_p" type="text" placeholder="<?= $this->lng['preteur-projets']['montant-exemple'] ?>" class="field" onkeyup="lisibilite_nombre(this.value,this.id);"/>
                            <span style="margin-left: -15px;position: relative;top: 4px;">€</span>
                        </div>
                        <p class="laMensual" style="font-size:14px;display:none;"><?= $this->lng['preteur-projets']['soit-un-remboursement-mensuel-de'] ?></p>
                        <div class="laMensual" style="font-size:14px;width:245px;display:none;">
                            <div style="text-align:center;"><span id="mensualite">xx</span> €</div>
                        </div>
                        <br/>

                        <?php
                        // on check si on a coché les cgv ou pas
                        // cgu societe
                        if (in_array($this->clients->type, array(2, 4))) {
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
