<table class="table orders-table">
    <tr>
        <th width="15%"><span id="triNum">N°<i class="icon-arrows"></i></span></th>
        <th width="25%">
            <span id="triTx"><?= $this->lng['preteur-projets']['taux-dinteret'] ?> <i class="icon-arrows"></i></span>
            <small><?= $this->lng['preteur-projets']['taux-moyen'] ?> : <?= $this->ficelle->formatNumber($this->avgRate, 1) ?> %</small>
        </th>
        <th width="35%">
            <span id="triAmount"><?= $this->lng['preteur-projets']['montant'] ?> <i class="icon-arrows"></i></span>
            <small><?= $this->lng['preteur-projets']['montant-moyen'] ?> : <?= $this->ficelle->formatNumber($this->avgAmount / 100) ?> €</small>
        </th>
        <th width="25%"><span id="triStatuts"><?= $this->lng['preteur-projets']['statuts'] ?><i class="icon-arrows"></i></span>
        </th>
    </tr>
    <?php foreach ($this->aBidsOnProject as $iKey => $aBid) : ?>
        <tr <?= (($aBid['id_lender_account'] == $this->lenders_accounts->id_lender_account) ? ' class="enchereVousColor"' : '') ?>>
            <td>
                <?php if ($this->lenders_accounts->id_lender_account == $aBid['id_lender_account']): ?>
                    <span class="enchereVous"><?= $this->lng['preteur-projets']['vous'] ?></span>
                    <span style="position: relative; left: -12px; bottom: 13px">
                    <span class="<?= (empty($aBid['id_autobid'])) ? 'no_autobid' : 'autobid' ?>">A</span>
                    <?= $aBid['ordre'] ?>
                    </span>
            <?php else : ?>
                    <span style="position: relative; left: -12px; bottom: 13px">
                    <span class="<?= (empty($aBid['id_autobid'])) ? 'no_autobid' : 'autobid' ?>">A</span>
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
        <?php endforeach; ?>
</table>
<div id="displayAll"></div>

<script>
    $("#direction").html('<?= $this->direction ?>');

    $("#triNum").click(function () {
        $("#tri").html('ordre');
        $("#displayAll").click();
    });

    $("#triTx").click(function () {
        $("#tri").html('rate');
        $("#displayAll").click();
    });

    $("#triAmount").click(function () {
        $("#tri").html('amount');
        $("#displayAll").click();
    });

    $("#triStatuts").click(function () {
        $("#tri").html('status');
        $("#displayAll").click();
    });

    $("#displayAll").click(function () {
        var tri = $("#tri").html();
        var direction = $("#direction").html();
        $.post(add_url + '/ajax/displayAll', {
            id: <?= $this->projects->id_project ?>,
            tri: tri,
            direction: direction
        }).done(function (data) {
            $('#bids').html(data)
        });
    });
</script>
