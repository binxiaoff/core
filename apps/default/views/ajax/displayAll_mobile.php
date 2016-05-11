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
<table class="table orders-table">
    <tr>
        <th width="25%">
            <span id="rate"><?= $this->lng['preteur-projets']['taux-dinteret'] ?></span>
        </th>
        <th width="25%">
            <span id="amount"><?= $this->lng['preteur-projets']['slice-total-amount'] ?></span>
        </th>
        <th width="25%">
            <span id="offers"><?= $this->lng['preteur-projets']['nb-offer'] ?></span>
        </th>
        <th width="25%">
            <span id="current-offers"><?= $this->lng['preteur-projets']['nb-pending-offer'] ?></span>
        </th>
    </tr>
<?php foreach ($this->bidsStatistics as $bidsForRate) :
    if ($bidsForRate['amount_total'] <= 0) {
        continue;
    }
?>
    <tr class="table-body" data-rate="<?=$bidsForRate['rate']?>" data-project="<?=$this->projects->id_project?>">
        <td class="rate-cell">
            <span class="order-rate"><?=number_format((float) $bidsForRate['rate'], 1, ',', ' ')?> %<i class="icon-grey icon-simple-arrow"></i></span>
        </td>
        <td>
            <span class="total-amount"><?=number_format((float) $bidsForRate['amount_total'], 0, ',', ' ')?> €</span>
        </td>
        <td>
            <span class="number-of-offers"><?=$bidsForRate['nb_bids']?></span>
        </td>
        <td>
            <span class="offers-rate"><?=number_format($bidsForRate['amount_active'] * 100 / $bidsForRate['amount_total'], 1, ',', ' ')?> %</span>
        </td>
    </tr>
    <tr class="detail-nav">
        <th>
            <span class="bid-number">
                N°
                <i class="icon-grey icon-arrows"></i>
            </span>
        </th>
        <th>
            <span class="rate">
                <?= $this->lng['preteur-projets']['taux-dinteret'] ?>
            </span>
        </th>
        <th>
            <span class="amount">
                <?= $this->lng['preteur-projets']['montant'] ?>
                <i class="icon-grey icon-arrows"></i>
            </span>
        </th>
        <th>
            <span class="status">
                <?= $this->lng['preteur-projets']['statuts'] ?>
                <i class="icon-grey icon-arrows"></i>
            </span>
        </th>
    </tr>
<?php endforeach; ?>
</table>