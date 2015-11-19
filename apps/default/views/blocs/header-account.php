<?
// preteur
if ($this->bLender === true) {
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
                <li><a href="<?= $this->lurl ?>/logout"><?= $this->lng['header']['deconnexion'] ?></a></li>
            </ul>
        </div>
    </div><!-- /.login-panel -->
    <?
} // emprunteur
else {
    ?>
    <div class="logedin-panel right">
        <a href="<?= $this->lurl ?>/espace_emprunteur/identite" class="header_account_name">
            <strong><?= $this->company->siren ?></strong></a>
            <strong><?= $this->company->name ?></strong>
        <a href="<?= $this->lurl ?>/logout"><?= $this->lng['header']['deconnexion'] ?></a>
    </div><!-- /.login-panel -->
    <?
}
