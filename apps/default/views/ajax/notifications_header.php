<?php foreach ($this->lNotifHeader as $r): ?>
    <div class="notif <?= ($r['status'] == 1 ? 'view' : '') ?>">
    <?php
        switch ($r['type']) {
            case \notifications::TYPE_BID_REJECTED:
                $this->bids->get($r['id_bid'], 'id_bid');
                $this->projects_notifs->get($r['id_project'], 'id_project');
                $this->companies_notifs->get($this->projects_notifs->id_company, 'id_company');

                if ($this->bids->amount != $r['amount']) {
                    ?>
                    <b><?= $this->lng['notifications']['offre-partiellement-refusee'] ?></b><br/>
                    <div class="content_notif">
                        <?php
                        $montant = $this->bids->amount - $r['amount'];
                        if (empty($this->bids->id_autobid)) {
                            echo $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a'];
                        } else {
                            echo $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a-autobid'];
                        }
                        ?>
                        <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate, 1) ?> %</b> <?= $this->lng['notifications']['offre-refusee-sur-le-projet'] ?>
                        <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a>
                        <?= $this->lng['notifications']['offre-refusee-a-ete-decoupe'] ?> <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b><?= $this->lng['notifications']['offre-refusee-point'] ?>
                    </div>
                <?php } else { ?>
                    <b><?= $this->lng['notifications']['offre-refusee'] ?></b><br/>
                    <div class="content_notif">
                        <?php
                        if (empty($this->bids->id_autobid)) {
                            echo $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a'];
                        } else {
                            echo $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a-autobid'];
                        }
                        ?>
                        <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate, 1) ?> %</b> <?= $this->lng['notifications']['offre-refusee-pour-un-montant-de'] ?>
                        <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['offre-refusee-sur-le-projet'] ?>
                        <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-refusee-nest-plus-recevable'] ?>
                    </div>
                <?php
                }
                break;
            case \notifications::TYPE_REPAYMENT:
                $this->projects_notifs->get($r['id_project'], 'id_project');
                ?>
                <b><?= $this->lng['notifications']['remboursement'] ?></b><br/>
                <div class="content_notif">
                    <?= $this->lng['notifications']['remboursement-vous-venez-de-recevoir-un-remboursement-de'] ?>
                    <b style="white-space:nowrap;color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['remboursement-pour-le-projet'] ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a><?= $this->lng['notifications']['remboursement-point'] ?>
                </div><?php
                break;
            case \notifications::TYPE_BID_PLACED:
                $this->bids->get($r['id_bid'], 'id_bid');
                $this->projects_notifs->get($r['id_project'], 'id_project');
                $this->companies_notifs->get($this->projects_notifs->id_company, 'id_company');
                ?>
                <b><?= $this->lng['notifications']['offre-placee'] ?></b><br/>
                <div class="content_notif">
                    <?php
                $oAutobid = $this->loadData('autobid');
                    if (empty($this->bids->id_autobid)) {
                        echo $this->lng['notifications']['offre-placee-votre-offre-de-pret-de'];
                    } else {
                        echo $this->lng['notifications']['offre-placee-votre-offre-de-pret-de-autobid'];
                    }
                    ?>
                    <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($this->bids->amount / 100) ?> €</b> <?= $this->lng['notifications']['offre-placee-a'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate, 1) ?> %</b> <?= $this->lng['notifications']['offre-placee-sur-le-projet'] ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-placee-point'] ?>
                    <?php
                    if ($oAutobid->get($this->bids->id_autobid)) {
                       echo str_replace('[#AUTOBID_RATE_MIN#]', $oAutobid->rate_min, $this->lng['notifications']['content-notifications-bid-placed']);
                    }
                    ?>
                </div><?php
                break;
            case \notifications::TYPE_LOAN_ACCEPTED:
                $oAcceptedBids = $this->loadData('accepted_bids');
                $fAmount = $oAcceptedBids->getAcceptedAmount($r['id_bid']);
                $this->bids->get($r['id_bid'], 'id_bid');
                $this->projects_notifs->get($r['id_project'], 'id_project');

                ?>
                <b><?= $this->lng['notifications']['offre-acceptee'] ?></b><br/>
                <div class="content_notif">
                    <?= $this->lng['notifications']['offre-acceptee-votre-offre-de-pret-de'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate, 1) ?> %</b> <?= $this->lng['notifications']['offre-acceptee-pour-un-montant-de'] ?>
                    <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($fAmount) ?> €</b> <?= $this->lng['notifications']['offre-acceptee-sur-le-projet'] ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['offre-acceptee-a-ete-acceptee'] ?>
                </div><?php
                break;
            case \notifications::TYPE_BANK_TRANSFER_CREDIT:
                ?>
                <b><?= $this->lng['notifications']['conf-alim-virement'] ?></b><br/>
                <div class="content_notif">
                    <?= $this->lng['notifications']['conf-alim-virement-votre-alim-par-virement-dun-montant-de'] ?>
                    <b style="white-space:nowrap;color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['conf-alim-virement-a-ete-ajoute-a-votre-solde'] ?>
                </div><?php
                break;
            case \notifications::TYPE_CREDIT_CARD_CREDIT:
                ?>
                <b><?= $this->lng['notifications']['conf-alim-cb'] ?></b><br/>
                <div class="content_notif">
                    <?= $this->lng['notifications']['conf-alim-cb-votre-alim-par-cb-dun-montant-de'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['conf-alim-cb-a-ete-ajoute-a-votre-solde'] ?>
                </div><?php
                break;
            case \notifications::TYPE_DEBIT:
                ?>
                <b><?= $this->lng['notifications']['conf-retrait'] ?></b><br/>
                <div class="content_notif">
                    <?= $this->lng['notifications']['conf-retrait-votre-retrait-dun-montant-de'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b><?= $this->lng['notifications']['conf-retrait-a-ete-pris-en-compte'] ?>
                </div><?php
                break;
            case \notifications::TYPE_NEW_PROJECT:
                $this->projects_notifs->get($r['id_project'], 'id_project');
                ?>
                <b><?= $this->lng['notifications']['annonce-nouveau-projet'] ?></b><br/>
                <div class="content_notif"><?= $this->lng['notifications']['annonce-nouveau-projet-nouveau-projet'] ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['annonce-nouveau-projet-mis-en-ligne-le'] ?> <?= date('d/m/Y', strtotime($this->projects_notifs->date_publication_full)) ?> <?= $this->lng['notifications']['annonce-nouveau-projet-a'] ?> <?= date('H\Hi', strtotime($this->projects_notifs->date_publication_full)) ?><?= $this->lng['notifications']['annonce-nouveau-projet-montant-demande'] ?>
                    <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($this->projects_notifs->amount, 0) ?> €</b> <?= $this->lng['notifications']['annonce-nouveau-projet-sur-une-periode-de'] ?> <?= $this->projects_notifs->period ?> <?= $this->lng['notifications']['annonce-nouveau-projet-mois'] ?>
                </div><?php
                break;
            case \notifications::TYPE_PROJECT_PROBLEM:
            case \notifications::TYPE_PROJECT_PROBLEM_REMINDER:
            case \notifications::TYPE_PROJECT_RECOVERY:
            case \notifications::TYPE_PROJECT_PRECAUTIONARY_PROCESS:
            case \notifications::TYPE_PROJECT_RECEIVERSHIP:
            case \notifications::TYPE_PROJECT_COMPULSORY_LIQUIDATION:
            case \notifications::TYPE_PROJECT_FAILURE:
                $this->projects_notifs->get($r['id_project'], 'id_project');
                $this->companies->get($this->projects_notifs->id_company);
                ?>
                <strong><?= $this->lng['notifications']['titre-' . $r['type']] ?></strong><br/>
                <div class="content_notif">
                    <?= str_replace('[ENTREPRISE]', '<a href="' . $this->lurl . '/projects/detail/' . $this->projects_notifs->slug . '">' . addslashes($this->companies->name) . '</a>', $this->lng['notifications']['contenu-' . $r['type']]) ?>
                </div><?php
                break;
            case \notifications::TYPE_AUTOBID_BALANCE_INSUFFICIENT:
                ?>
                <strong><?= $this->lng['notifications']['titre-autobid-balance'] ?></strong><br/>
                <div class="content_notif">
                    <?= $this->lng['notifications']['content-autobid-balance-insufficient'] ?>
                </div><?php
                break;
            case \notifications::TYPE_AUTOBID_BALANCE_LOW:
                ?>
                <strong><?= $this->lng['notifications']['titre-autobid-balance'] ?></strong><br/>
                <div class="content_notif">
                <?= $this->lng['notifications']['content-autobid-balance-low'] ?>
                </div><?php
                break;
            case \notifications::TYPE_AUTOBID_FIRST_ACTIVATION: ?>
                <strong><?= $this->lng['notifications']['titre-autobid-activation'] ?></strong><br/>
                <div class="content_notif">
                <?= str_replace(array('[#ACTIVATION_TIME#]','[#LURL#]'), array($this->get('AutoBidSettingsManager')->getActivationTime($this->clients), $this->lurl), $this->lng['notifications']['content-autobid-activation']) ?>
                </div><?php
                break;
        }
        ?>
        <span class="date_notif"><?= date('d/m/Y', strtotime($r['added'])) ?></span>
    </div>
<?php endforeach; ?>
