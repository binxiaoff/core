<?php foreach ($this->lNotifHeader as $r): ?>
    <div class="notif <?= ($r['status'] == 1 ? 'view' : '') ?>">
    <?php
        if ($r['type'] == \notifications::TYPE_BID_REJECTED) {
            $this->bids->get($r['id_bid'], 'id_bid');
            $this->projects_notifs->get($r['id_project'], 'id_project');
            $this->companies_notifs->get($this->projects_notifs->id_company, 'id_company');

            if ($this->bids->amount != $r['amount']) {
                ?>
                <b><?= $this->lng['notifications']['offre-partiellement-refusee'] ?></b><br/>
                <div class="content_notif">
                    <?php $montant = $this->bids->amount - $r['amount']; ?>
                    <?= $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate, 1) ?> %</b><?= $this->lng['notifications']['offre-refusee-pour-un-montant-de'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->amount / 100) ?> €</b> <?= $this->lng['notifications']['offre-refusee-sur-le-projet'] ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-refusee-a-ete-decoupe'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b><?= $this->lng['notifications']['offre-refusee-point'] ?>
                </div><?php
            } else {
                ?>
                <b><?= $this->lng['notifications']['offre-refusee'] ?></b><br/>
                <div class="content_notif">
                    <?= $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate, 1) ?> %</b> <?= $this->lng['notifications']['offre-refusee-pour-un-montant-de'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['offre-refusee-sur-le-projet'] ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-refusee-nest-plus-recevable'] ?>
                </div><?php
            }
        } elseif ($r['type'] == \notifications::TYPE_REPAYMENT) {
            $this->projects_notifs->get($r['id_project'], 'id_project');
            ?>
            <b><?= $this->lng['notifications']['remboursement'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['remboursement-vous-venez-de-recevoir-un-remboursement-de'] ?>
                <b style="white-space:nowrap;color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['remboursement-pour-le-projet'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a><?= $this->lng['notifications']['remboursement-point'] ?>
            </div><?php
        } elseif ($r['type'] == \notifications::TYPE_BID_PLACED) {
            $this->bids->get($r['id_bid'], 'id_bid');
            $this->projects_notifs->get($r['id_project'], 'id_project');
            $this->companies_notifs->get($this->projects_notifs->id_company, 'id_company');
            ?>
            <b><?= $this->lng['notifications']['offre-placee'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['offre-placee-votre-offre-de-pret-de'] ?>
                <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($this->bids->amount / 100) ?> €</b> <?= $this->lng['notifications']['offre-placee-a'] ?>
                <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate, 1) ?> %</b> <?= $this->lng['notifications']['offre-placee-sur-le-projet'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-placee-point'] ?>
            </div><?php
        } elseif ($r['type'] == \notifications::TYPE_LOAN_ACCEPTED) {
            $this->loans->get($r['id_bid'], 'id_bid');
            $this->projects_notifs->get($r['id_project'], 'id_project');
            ?>
            <b><?= $this->lng['notifications']['offre-acceptee'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['offre-acceptee-votre-offre-de-pret-de'] ?>
                <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->loans->rate, 1) ?> %</b> <?= $this->lng['notifications']['offre-acceptee-pour-un-montant-de'] ?>
                <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($this->loans->amount / 100) ?> €</b> <?= $this->lng['notifications']['offre-acceptee-sur-le-projet'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['offre-acceptee-a-ete-acceptee'] ?>
            </div><?php
        } elseif ($r['type'] == \notifications::TYPE_BANK_TRANSFER_CREDIT) {
            ?>
            <b><?= $this->lng['notifications']['conf-alim-virement'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['conf-alim-virement-votre-alim-par-virement-dun-montant-de'] ?>
                <b style="white-space:nowrap;color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['conf-alim-virement-a-ete-ajoute-a-votre-solde'] ?>
            </div><?php
        } elseif ($r['type'] == \notifications::TYPE_CREDIT_CARD_CREDIT) {
            ?>
            <b><?= $this->lng['notifications']['conf-alim-cb'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['conf-alim-cb-votre-alim-par-cb-dun-montant-de'] ?>
                <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['conf-alim-cb-a-ete-ajoute-a-votre-solde'] ?>
            </div><?php
        } elseif ($r['type'] == \notifications::TYPE_DEBIT) {
            ?>
            <b><?= $this->lng['notifications']['conf-retrait'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['conf-retrait-votre-retrait-dun-montant-de'] ?>
                <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b><?= $this->lng['notifications']['conf-retrait-a-ete-pris-en-compte'] ?>
            </div><?php
        } elseif ($r['type'] == \notifications::TYPE_NEW_PROJECT) {
            $this->projects_notifs->get($r['id_project'], 'id_project');
            ?>
            <b><?= $this->lng['notifications']['annonce-nouveau-projet'] ?></b><br/>
            <div class="content_notif"><?= $this->lng['notifications']['annonce-nouveau-projet-nouveau-projet'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['annonce-nouveau-projet-mis-en-ligne-le'] ?> <?= date('d/m/Y', strtotime($this->projects_notifs->date_publication_full)) ?> <?= $this->lng['notifications']['annonce-nouveau-projet-a'] ?> <?= date('H\Hi', strtotime($this->projects_notifs->date_publication_full)) ?><?= $this->lng['notifications']['annonce-nouveau-projet-montant-demande'] ?>
                <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($this->projects_notifs->amount, 0) ?> €</b> <?= $this->lng['notifications']['annonce-nouveau-projet-sur-une-periode-de'] ?> <?= $this->projects_notifs->period ?> <?= $this->lng['notifications']['annonce-nouveau-projet-mois'] ?>
            </div><?php
        } elseif ($r['type'] == \notifications::TYPE_PROJECT_PROBLEM) {
            $this->projects_notifs->get($r['id_project'], 'id_project');
            ?>
            <b><?= $this->lng['notifications']['annonce-nouveau-probleme'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['probleme-notif-texte1'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['probleme-notif-texte2'] ?>
            </div><?php
        } elseif ($r['type'] == \notifications::TYPE_PROJECT_RECOVERY) {
            $this->projects_notifs->get($r['id_project'], 'id_project');
            ?>
            <b><?= $this->lng['notifications']['annonce-recouvrement'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['recouvrement-notif-texte1'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['recouvrement-notif-texte2'] ?>
            </div><?php
        }
        ?>
        <span class="date_notif"><?= date('d/m/Y', strtotime($r['added'])) ?></span>
    </div>
<?php endforeach; ?>

