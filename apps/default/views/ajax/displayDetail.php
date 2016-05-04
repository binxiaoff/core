<?php foreach ($this->bidsList as $index => $bid) : ?>
<tr class="table-body detail <?= (($this->lenders_accounts->id_lender_account == $aBid['id_lender_account']) ? ' enchereVousColor' : '' )?>">
    <td class="bid-number">
        <?php if ($this->lenders_accounts->id_lender_account == $bid['id_lender_account']): ?>
            <div style="position: relative">
                <span class="enchereVous"><?= $this->lng['preteur-projets']['vous'] ?></span>
            </div>
        <?php endif; ?>
        <span class="order-rate"><?= $bid['ordre'] ?></span>
        <?php if (! empty($bid['id_autobid']) || true == $this->bIsAllowedToSeeAutobid) : ?>
            <span class="autobid">A</span>
        <?php endif; ?>
    </td>
    <td>
        <span class="total-amount"><?= $this->ficelle->formatNumber($bid['rate'], 1) ?>&nbsp;%</span>
    </td>
    <td>
        <span class="number-of-offers"><?= $this->ficelle->formatNumber($bid['amount'] / 100, 0) ?>&nbsp;€</span>
    </td>
    <td>
        <span class="<?= ($bid['status'] == \bids::STATUS_BID_PENDING ? 'circle_pending' : ($bid['status'] == \bids::STATUS_BID_REJECTED ? 'circle_rejected' : '')) ?>"></span>
        <span class="<?= ($bid['status'] == \bids::STATUS_BID_PENDING ? 'green-span' : ($bid['status'] == \bids::STATUS_BID_REJECTED ? 'red-span' : '')) ?>">
            <?= $this->status[$bid['status']] ?>
        </span>
    </td>
<?php endforeach; ?>
