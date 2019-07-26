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
                    <?php if (false === empty($this->userEntity)) { ?>
                        <a href="<?php echo $this->url; ?>/users/edit_perso/<?php echo $this->userEntity->getIdUser(); ?>" class="thickbox">
                            <?php echo $this->userEntity->getFirstName() . ' ' . $this->userEntity->getName(); ?>
                        </a>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <a href="<?php echo $this->url; ?>/logout" title="Se déconnecter"><strong>Se déconnecter</strong></a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="navigation">
    <ul id="menu_deroulant">
        <?php
        /** @var \Unilend\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        $menuHtml = '';
        foreach (static::MENU as $item) {
            $zone  = $item['zone'];
            $title = $item['title'];

            // Item visibility
            if (in_array($zone, $this->lZonesHeader)) {
                $active = isset($this->menu_admin) && $this->menu_admin === $zone ? ' class="active"' : '';
                $menuHtml .= '<li>';
                $menuHtml .= empty($item['uri']) ? '<span' . $active . '>' . $title . '</span>' : '<a href="' . $this->url . '/' . $item['uri'] . '"' . $active . '>' . $title . '</a>';

                if (false === empty($item['children'])) {
                    $menuHtml .= '<ul class="sous_menu">';
                    foreach ($item['children'] as $subItem) {
                        if (false === isset($subItem['zone']) || in_array($subItem['zone'], $this->lZonesHeader)) {
                            $menuHtml .= '<li><a href="' . $this->url . '/' . $subItem['uri'] . '">' . $subItem['title'] . '</a><li>';
                        }
                    }
                    $menuHtml .= '</ul>';
                }
                $menuHtml .= '</li>';
            }
        }

        ?>
        <?php echo $menuHtml; ?>
    </ul>
</div>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
