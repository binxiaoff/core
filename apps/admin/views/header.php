<div id="header">
    <div class="row">
        <div class="col-md-6">
            <div class="logo_header">
                <a href="<?php echo $this->url; ?>"><img src="<?php echo $this->furl; ?>/assets/images/logo/logo-and-type-245x52.png" alt="CALS"/></a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bloc_info_header">
                <div>
                    <?php if (false === empty($this->getUser())) { ?>
                        <?php echo $this->getUser()->getFirstName() . ' ' . $this->getUser()->getLastName(); ?>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <a href="<?php echo $this->furl; ?>/logout" title="Se déconnecter"><strong>Se déconnecter</strong></a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="navigation">
    <ul>
        <li<?= isset($this->menu_admin) && 'traductions' === $this->menu_admin ? ' class="active"' : ''; ?>><a href="/traductions">Traductions</a></li>
        <li<?= isset($this->menu_admin) && 'mails' === $this->menu_admin ? ' class="active"' : ''; ?>><a href="/mails">Emails</a></li>
        <li<?= isset($this->menu_admin) && 'mailshistory' === $this->menu_admin ? ' class="active"' : ''; ?>><a href="/mails/emailhistory">Historique envois emails</a></li>
        <li<?= isset($this->menu_admin) && 'settings' === $this->menu_admin ? ' class="active"' : ''; ?>><a href="/settings">Paramètres</a></li>
    </ul>
</div>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
