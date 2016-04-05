<style type="text/css">
    .autobid {
        -webkit-border-radius: 9px;
        -moz-border-radius: 9px;
        border-radius: 9px;
        color: #ffffff;
        font-weight: bold;
        background: #d9aa34;
        padding: 5px 10px 5px 10px;
        text-decoration: none;
    }
    .no_autobid {
        padding: 5px 10px 5px 10px;
        background: transparent;
        color: transparent;
        font-weight: bold;
    }

    .circle_pending {
        width: 15px;
        height: 15px;
        -webkit-border-radius: 15px;
        -moz-border-radius: 15px;
        border-radius: 15px;
        background: #289922;
        text-decoration: none;
        display: inline-block;
    }

    .circle_rejected {
        width: 15px;
        height: 15px;
        -webkit-border-radius: 15px;
        -moz-border-radius: 15px;
        border-radius: 15px;
        background: #eb3023;
        text-decoration: none;
        display: inline-block;
    }
</style>

<?php if (count($this->aBidsOnProject) > 0) : ?>
    <table class="table orders-table">
        <tr>
            <th width="125"><span id="triNum_mobile">N°<i class="icon-arrows"></i></span></th>
            <th width="180">
                <span id="triTx_mobile"><?= $this->lng['preteur-projets']['taux-dinteret'] ?> <i class="icon-arrows"></i></span>
                <small><?= $this->lng['preteur-projets']['taux-moyen'] ?> : <?= $this->ficelle->formatNumber($this->avgRate, 1) ?> %</small>
            </th>
            <th width="214">
                <span id="triAmount_mobile"><?= $this->lng['preteur-projets']['montant'] ?> <i class="icon-arrows"></i></span>
                <small><?= $this->lng['preteur-projets']['montant-moyen'] ?> : <?= $this->ficelle->formatNumber($this->avgAmount / 100) ?> €</small>
            </th>
            <th width="101"><span id="triStatuts_mobile"><?= $this->lng['preteur-projets']['statuts'] ?> <i class="icon-arrows"></i></span></th>
        </tr>
        <?php foreach ($this->aBidsOnProject as $iKey => $aBid) : ?>
            <?php if ($this->CountEnchere >= 12 && !isset($_POST['tri'])) : ?>
                <?php if ($aBid['ordre'] <= 5 || $aBid['ordre'] > $this->CountEnchere - 5) : ?>
                    <tr <?= (($this->lenders_accounts->id_lender_account == $aBid['id_lender_account']) ? ' class="enchereVousColor"' : '') ?>>
                        <td>
                            <?php if ($this->lenders_accounts->id_lender_account == $aBid['id_lender_account']): ?>
                                <span class="enchereVous"><?= $this->lng['preteur-projets']['vous'] ?></span>
                                <span>
                                <span class="<?= (empty($aBid['id_autobid']) || false === $this->bIsAllowedToSeeAutobid) ? 'no_autobid' : 'autobid' ?>">A</span>
                                <?= $aBid['ordre'] ?>
                                </span>
                            <?php else : ?>
                                <span>
                                <span class="<?= (empty($aBid['id_autobid']) || false === $this->bIsAllowedToSeeAutobid) ? 'no_autobid' : 'autobid' ?>">A</span>
                                <?= $aBid['ordre'] ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= $this->ficelle->formatNumber($aBid['rate'], 1) ?> %</td>
                        <td><?= $this->ficelle->formatNumber($aBid['amount'] / 100, 0) ?> €</td>
                        <td>
                            <span class="<?= ($aBid['status'] == \bids::STATUS_BID_PENDING ? 'circle_pending' : ($aBid['status'] == \bids::STATUS_BID_REJECTED ? 'circle_rejected' : '')) ?>"></span>
                            <span class="<?= ($aBid['status'] == \bids::STATUS_BID_PENDING ? 'green-span' : ($aBid['status'] == \bids::STATUS_BID_REJECTED ? 'red-span' : '')) ?>">
                                <?= $this->status[$aBid['status']] ?>
                            </span>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if ($aBid['ordre'] == 6) : ?>
                    <tr><td colspan="4" class="nth-table-row displayAll_mobile" style="cursor:pointer;">...</td></tr>
                <?php endif; ?>
            <?php else : ?>
                <tr <?= (($this->lenders_accounts->id_lender_account == $aBid['id_lender_account']) ? ' class="enchereVousColor"' : '' )?>>
                    <td>
                        <?php if ($this->lenders_accounts->id_lender_account == $aBid['id_lender_account']): ?>
                            <span class="enchereVous"><?= $this->lng['preteur-projets']['vous'] ?></span>
                            <span>
                            <span class="<?= (empty($aBid['id_autobid']) || false === $this->bIsAllowedToSeeAutobid) ? 'no_autobid' : 'autobid' ?>">A</span>
                            <?= $aBid['ordre'] ?>
                            </span>
                        <?php else : ?>
                            <span>
                            <span class="<?= (empty($aBid['id_autobid']) || false === $this->bIsAllowedToSeeAutobid) ? 'no_autobid' : 'autobid' ?>">A</span>
                            <?= $aBid['ordre'] ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= $this->ficelle->formatNumber($aBid['rate'], 1) ?> %</td>
                    <td><?= $this->ficelle->formatNumber($aBid['amount'] / 100, 0) ?>€
                    </td>
                    <td>
                        <span class="<?= ($aBid['status'] == \bids::STATUS_BID_PENDING ? 'circle_pending' : ($aBid['status'] == \bids::STATUS_BID_REJECTED ? 'circle_rejected' : '')) ?>"></span>
                        <span class="<?= ($aBid['status'] == \bids::STATUS_BID_PENDING ? 'green-span' : ($aBid['status'] == \bids::STATUS_BID_REJECTED ? 'red-span' : '')) ?>">
                            <?= $this->status[$aBid['status']] ?>
                        </span>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
    <?php if ($this->CountEnchere >= 12 && !isset($_POST['tri'])) : ?>
        <div class="single-project-actions">
            <a class="btn btn-large displayAll_mobile" ><?= $this->lng['preteur-projets']['voir-tout-le-carnet-dordres'] ?></a>
        </div>
    <?php else : ?>
    <div class="displayAll_mobile"></div>
    <?php endif; ?>
    <script>
        $("#direction_mobile").html('<?= $this->direction ?>');

        $("#triNum_mobile").click(function () {
            $("#tri_mobile").html('ordre');
            $(".displayAll_mobile").click();
        });

        $("#triTx_mobile").click(function () {
            $("#tri_mobile").html('rate');
            $(".displayAll_mobile").click();
        });

        $("#triAmount_mobile").click(function () {
            $("#tri_mobile").html('amount');
            $(".displayAll_mobile").click();
        });

        $("#triStatuts_mobile").click(function () {
            $("#tri_mobile").html('status');
            $(".displayAll_mobile").click();
        });

        $(".displayAll_mobile").click(function () {
            var tri = $("#tri_mobile").html();
            var direction = $("#direction_mobile").html();
            $.post(add_url + '/ajax/displayAll_mobile', {id: <?= $this->projects->id_project ?>, tri: tri, direction: direction}).done(function (data) {
                $('#bids_mobile').html(data)
            });
        });
    </script>
<?php else : ?>
        <p><?= $this->lng['preteur-projets']['aucun-enchere'] ?></p>
<?php endif; ?>