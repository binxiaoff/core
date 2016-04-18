<div class="main" <?= (isset($this->params[0]) && ! in_array($this->params[0], array('morale', 'nosign')) ? 'style="padding-bottom: 0px;"' : '') ?>>
    <div class="shell" <?= (isset($this->params[0]) && ! in_array($this->params[0], array('morale', 'nosign')) ? 'style="width:692px;"' : '') ?>>
        <?php if (isset($this->params[0]) && ! in_array($this->params[0], array('morale', 'nosign'))) : ?>
            <img alt='logo' src='<?= $this->surl ?>/styles/default/pdf/images/logo.png'>
        <?php endif; ?>
        <?= $this->content['contenu-cgu'] ?>
        <div style="page-break-after: always;"></div>
        <?= $this->mandat_de_recouvrement ?>
        <?= $this->mandat_de_recouvrement_avec_pret ?>
    </div>
</div>
<?= (isset($this->params[0]) ? '</body></html>' : '') ?>

