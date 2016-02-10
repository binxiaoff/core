<?php if ($this->bIsBorrowerAndLender) { ?>
    <div class="change espace" style="float: right; padding-top:  24px;  position:  relative;  margin-left: 5px; ">
        <form action="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?>" method="post">
            <input type="hidden"
                   name="<?= ($this->bDisplayLender) ? 'acceder-espace-emprunteur' : 'acceder-espace-preteur' ?>">
            <input type="submit" class="btn btn small" style="float: right; "
                   value="<?= ($this->bDisplayLender) ? $this->lng['header']['acceder-espace-emprunteur'] : $this->lng['header']['acceder-espace-preteur'] ?>"</input>
        </form>
    </div>
    <?php
}
if ($this->bDisplayLender) {
    ?>
    <div class="logedin-panel right">
        <a href="<?= $this->lurl ?>/synthese"
           class="header_account_name">
            <strong><?= $this->lng['header']['bonjour'] ?> <?= $this->clients->prenom ?> <?= $this->clients->nom ?></strong>
        </a>
        <strong><?= $this->lng['header']['solde'] ?> : <span><span
                    id="solde"><?= $this->ficelle->formatNumber($this->solde) ?></span> €</span>&nbsp; <a
                href="<?= $this->lurl ?>/alimentation"
                style="font-size:11px;"><?= $this->lng['header']['ajouter-de-largent'] ?></a></strong>

        <div class="dd">
            <span class="bullet notext">bullet</span>
            <ul>
                <li><a href="<?= $this->lurl ?>/operations"><?= $this->lng['header']['pdf-de-mes-prets'] ?></a></li>
                <li>
                    <a href="<?= $this->lurl ?>/<?= $this->tree->getSlug(55, $this->language) ?>"><?= $this->tree->getTitle(55, $this->language) ?></a>
                </li>
                <li><a target="_blank"
                       href="<?= $this->surl ?>/pdf_cgv_preteurs"><?= $this->lng['header']['cgu-preteur'] ?></a></li>
                <?php if ($this->bIsBorrowerAndLender) : ?>
                    <li>
                        <a href="<?= $this->lurl ?>/espace_emprunteur"><?= $this->lng['acceder-emprunteur'] ?></a>
                    </li>
                <?php endif; ?>
                <li><a href="<?= $this->lurl ?>/logout"><?= $this->lng['header']['deconnexion'] ?></a></li>
            </ul>
        </div>
    </div><!-- /.login-panel -->
    <?php
} elseif ($this->bDisplayBorrower) {
    ?>
    <div class="logedin-panel right">
        <a href="<?= $this->lurl ?>/espace_emprunteur/identite" class="header_account_name">
            <span style="font-size: 0.8em;"><strong><?= $this->lng['header']['siren'] . $this->oCompanyDisplay->siren ?></strong></span></a>
        <span style="font-size: 0.8em;"><strong><?= $this->oCompanyDisplay->name ?></strong></span>
           <div class="dd">
                <span class="bullet notext">bullet</span>
                <ul>
                    <li>
                        <a href="<?= $this->lurl ?>/logout"><?= $this->lng['header']['deconnexion'] ?></a>
                    </li>
                    <?php if ($this->bIsBorrowerAndLender) : ?>
                    <li>
                        <a href="<?= $this->lurl ?>/synthese"><?= $this->lng['acceder-preteur'] ?></a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
    </div><!-- /.login-panel -->
    <?
}
