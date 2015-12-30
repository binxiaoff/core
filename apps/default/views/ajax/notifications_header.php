<?php foreach ($this->lNotifHeader as $r) { ?>
    <div class="notif <?= ($r['status'] == 1 ? 'view' : '') ?>">
        <?php
        // Offre refusée
        if ($r['type'] == 1) {
            $this->bids->get($r['id_bid'], 'id_bid');
            $this->projects_notifs->get($r['id_project'], 'id_project');
            $this->companies_notifs->get($this->projects_notifs->id_company, 'id_company');

            // decoupé
            if ($this->bids->amount != $r['amount']) {
                ?>
                <b><?= $this->lng['notifications']['offre-partiellement-refusee'] ?></b><br/>
                <div class="content_notif">
                <?php $montant = ($this->bids->amount - $r['amount']); ?>
                <?= $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a'] ?>
                <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate) ?> %</b><?= $this->lng['notifications']['offre-refusee-sur-le-projet'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a>
                <?= $this->lng['notifications']['offre-refusee-a-ete-decoupe'] ?> <b style="color:#b20066;"><?= number_format($r['amount'] / 100) ?> €</b><?= $this->lng['notifications']['offre-refusee-point'] ?>
                </div>
            <?php } else { ?>
                <b><?= $this->lng['notifications']['offre-refusee'] ?></b><br/>
                <div class="content_notif">
                    <?= $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a'] ?>
                    <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate) ?> %</b> <?= $this->lng['notifications']['offre-refusee-pour-un-montant-de'] ?>
                    <b style="color:#b20066;"><?= number_format($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['offre-refusee-sur-le-projet'] ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-refusee-nest-plus-recevable'] ?>
                </div>
                <?php
            }
        } // Remboursement
        elseif ($r['type'] == 2) {
            $this->projects_notifs->get($r['id_project'], 'id_project');

            ?>
            <b><?= $this->lng['notifications']['remboursement'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['remboursement-vous-venez-de-recevoir-un-remboursement-de'] ?>
                <b style="white-space:nowrap;color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['remboursement-pour-le-projet'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a><?= $this->lng['notifications']['remboursement-point'] ?>
            </div>
            <?php
        } // Offre placée
        elseif ($r['type'] == 3) {
            $this->bids->get($r['id_bid'], 'id_bid');
            $this->projects_notifs->get($r['id_project'], 'id_project');
            $this->companies_notifs->get($this->projects_notifs->id_company, 'id_company');
            ?>
            <b><?= $this->lng['notifications']['offre-placee'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['offre-placee-votre-offre-de-pret-de'] ?>
                <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($this->bids->amount / 100) ?> €</b> <?= $this->lng['notifications']['offre-placee-a'] ?>
                <b style="color:#b20066;"><?= number_format($this->bids->rate) ?> %</b> <?= $this->lng['notifications']['offre-placee-sur-le-projet'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-placee-point'] ?>
            </div>
            <?php
        } // Offre acceptée
        elseif ($r['type'] == 4) {
            $oAcceptedBids = $this->loadData('accepted_bids');
            $fAmount = $oAcceptedBids->getAcceptedAmount($r['id_bid']);
            $this->bids->get($r['id_bid'], 'id_bid');
            $this->projects_notifs->get($r['id_project'], 'id_project');

            ?>
            <b><?= $this->lng['notifications']['offre-acceptee'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['offre-acceptee-votre-offre-de-pret-de'] ?>
                <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate) ?> %</b> <?= $this->lng['notifications']['offre-acceptee-pour-un-montant-de'] ?>
                <b style="color:#b20066;white-space:nowrap;"><?= number_format($fAmount) ?> €</b> <?= $this->lng['notifications']['offre-acceptee-sur-le-projet'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['offre-acceptee-a-ete-acceptee'] ?>
            </div>
            <?php
        } // Confirmation alimentation par virement
        elseif ($r['type'] == 5) {
            ?>
            <b><?= $this->lng['notifications']['conf-alim-virement'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['conf-alim-virement-votre-alim-par-virement-dun-montant-de'] ?>
                <b style="white-space:nowrap;color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['conf-alim-virement-a-ete-ajoute-a-votre-solde'] ?>
            </div>
            <?php
        } // Confirmation alimentation par carte bancaire
        elseif ($r['type'] == 6) {
            ?>
            <b><?= $this->lng['notifications']['conf-alim-cb'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['conf-alim-cb-votre-alim-par-cb-dun-montant-de'] ?>
                <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['conf-alim-cb-a-ete-ajoute-a-votre-solde'] ?>
            </div>
            <?php
        } // Confirmation de retrait
        elseif ($r['type'] == 7) {
            ?>
            <b><?= $this->lng['notifications']['conf-retrait'] ?></b><br/>
            <div class="content_notif">
                <?= $this->lng['notifications']['conf-retrait-votre-retrait-dun-montant-de'] ?>
                <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b><?= $this->lng['notifications']['conf-retrait-a-ete-pris-en-compte'] ?>
            </div>
            <?php
        } // Annonce nouveau projet
        elseif ($r['type'] == 8) {
            $this->projects_notifs->get($r['id_project'], 'id_project');
            ?>
            <b><?= $this->lng['notifications']['annonce-nouveau-projet'] ?></b><br/>
            <div class="content_notif"><?= $this->lng['notifications']['annonce-nouveau-projet-nouveau-projet'] ?>
                <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['annonce-nouveau-projet-mis-en-ligne-le'] ?> <?= date('d/m/Y', strtotime($this->projects_notifs->date_publication_full)) ?> <?= $this->lng['notifications']['annonce-nouveau-projet-a'] ?> <?= date('H\Hi', strtotime($this->projects_notifs->date_publication_full)) ?><?= $this->lng['notifications']['annonce-nouveau-projet-montant-demande'] ?>
                <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($this->projects_notifs->amount) ?> €</b> <?= $this->lng['notifications']['annonce-nouveau-projet-sur-une-periode-de'] ?> <?= $this->projects_notifs->period ?> <?= $this->lng['notifications']['annonce-nouveau-projet-mois'] ?>
            </div>
            <?php
        }
        ?>
        <span class="date_notif"><?= date('d/m/Y', strtotime($r['added'])) ?></span>
    </div>
    <?php
}
