<div id="header">
    <div class="row">
        <div class="col-md-6">
            <div class="logo_header">
                <a href="<?= $this->lurl ?>"><img src="<?= $this->surl ?>/styles/default/images/logo.png" alt="Unilend"/></a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bloc_info_header">
                <div>
                    <a href="<?= $this->lurl ?>/users/edit_perso/<?= $_SESSION['user']['id_user'] ?>" class="thickbox">
                        <?= $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'] ?>
                    </a>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <a href="<?= $this->lurl ?>/logout" title="Se déconnecter"><strong>Se déconnecter</strong></a>
                </div>
                <form id="quick_search" method="post">
                    <?php if (in_array('emprunteurs', $this->lZonesHeader)) : ?>
                        <input type="text" name="projectName" title="Raison sociale" placeholder="Raison sociale" size="20" />
                        <input type="text" name="siren" title="SIREN" placeholder="SIREN" size="10" />
                        <input type="text" name="projectId" title="ID projet" placeholder="ID projet" size="10" />
                    <?php endif; ?>
                    <?php if (in_array('preteurs', $this->lZonesHeader)) : ?>
                        <input type="text" name="lender" title="ID client" placeholder="ID client" size="10" />
                    <?php endif; ?>
                    <!-- Trick for enabling submitting form in Safari and IE -->
                    <input type="submit" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" />
                </form>
            </div>
        </div>
    </div>
</div>
<div id="navigation">
    <ul id="menu_deroulant">
        <?php generateMenu($menuItems, 'oldbo', $this->menu_admin, $this->lZonesHeader) ?>
    </ul>
</div>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<?php else : ?>
<div id="page-container" class="header-navbar-fixed">
    <header id="header-navbar">
        <div class="content-mini content-mini-full content-boxed">
            <div class="nav-header pull-right">
                <div class="collapse navbar-collapse remove-padding" id="sub-header-nav">
                    <ul class="nav nav-pills nav-sub-header">
                        <?php generateMenu($menuItems, 'oneui', $this->menu_admin, $this->lZonesHeader) ?>
                    </ul>
                </div>
            </div>
            <ul class="nav-header pull-left">
                <li class="header-content">
                    <a class="logo" href="/">
                        <img src="oneui/img/logo-and-type-unilend-209x44-purple@2x.png" width="209" height="44" alt="Unilend">
                    </a>
                </li>
            </ul>
        </div>
    </header>
    <main id="main-container">
        <div class="content content-boxed">
<?php endif; ?>
