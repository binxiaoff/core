<?
// preteur
if ($this->clients->status_pre_emp == 1)
{
    ?>
    <div class="logedin-panel right">

        <a href="<?= $this->lurl ?>/synthese" class="header_account_name"><strong><?= $this->lng['header']['bonjour'] ?> <?= $this->clients->prenom ?> <?= $this->clients->nom ?></strong></a>
        <?php /* ?><strong><?=$this->lng['header']['solde']?> : <span><span id="solde"><?=number_format($this->solde, 2, ',', ' ')?></span> €</span></strong><?php */ ?>

        <strong><?= $this->lng['header']['solde'] ?> : <span><span id="solde"><?= number_format($this->solde, 2, ',', ' ') ?></span> €</span>&nbsp; <a href="<?= $this->lurl ?>/alimentation" style="font-size:11px;"><?= $this->lng['header']['ajouter-de-largent'] ?></a></strong>

        <div class="dd">
            <span class="bullet notext">bullet</span>
            <ul>
                <!--<li><a href="<?= $this->lurl ?>/profile"><?= $this->lng['header']['mon-profil'] ?></a></li>-->
                <li><a href="<?= $this->lurl ?>/operations"><?= $this->lng['header']['pdf-de-mes-prets'] ?></a></li>
                <?php /* ?><li><a href="<?=$this->lurl?>/<?=$this->tree->getSlug(54,$this->language)?>"><?=$this->tree->getTitle(54,$this->language)?></a></li>
                  <li><a href="<?=$this->lurl?>/<?=$this->tree->getSlug(55,$this->language)?>"><?=$this->tree->getTitle(55,$this->language)?></a></li><?php */ ?>

                <li><a target="_blank" href="<?= $this->surl ?>/pdf_cgv_preteurs" ><?= $this->lng['header']['cgu-preteur'] ?></a></li>
                <?
                /* if($this->contentCGU['pdf-cgu'] != false)
                  {
                  ?><li><a target="_blank" href="<?=$this->surl?>/var/fichiers/<?=$this->contentCGU['pdf-cgu']?>" ><?=$this->lng['header']['cgu-preteur']?></a></li><?
                  } */
                ?>


                <li><a href="<?= $this->lurl ?>/logout"><?= $this->lng['header']['deconnexion'] ?></a></li>
            </ul>
        </div>
    </div><!-- /.login-panel -->
    <?
}
// emprunteur
else
{
    ?>
    <style>
        .navigation .shell > ul.styled-nav > li{padding: 0 17px;}
    </style>
    <div class="logedin-panel right">

        <a href="<?= $this->lurl ?>/synthese_emprunteur" class="header_account_name"><strong><?= $this->lng['header']['bonjour'] ?> <?= $this->clients->prenom ?> <?= $this->clients->nom ?></strong></a>
        <div class="dd">
            <span class="bullet notext">bullet</span>
            <ul>
                <?
                if ($this->etape_transition != true)
                {
                    ?>
                    <li><a href="<?= $this->lurl ?>/unilend_emprunteur"><?= $this->lng['header']['mon-profil'] ?></a></li>
                    <li><a href="#"><?= $this->lng['header']['pdf-de-mes-financements'] ?></a></li>

                    <?
                    if ($this->contentCGUDepotDossier['pdf-cgu'] != false)
                    {
                        ?><li><a target="_blank" href="<?= $this->surl ?>/var/fichiers/<?= $this->contentCGUDepotDossier['pdf-cgu'] ?>" ><?= $this->lng['header']['cgu-emprunteur'] ?></a></li><?
                    }
                }
                ?>
                <li><a href="<?= $this->lurl ?>/logout"><?= $this->lng['header']['deconnexion'] ?></a></li>
            </ul>
        </div>
    </div><!-- /.login-panel -->
    <?
}
