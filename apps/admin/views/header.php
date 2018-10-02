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
                    <?php if (false === empty($this->userEntity)) : ?>
                        <a href="<?= $this->lurl ?>/users/edit_perso/<?= $this->userEntity->getIdUser() ?>" class="thickbox">
                            <?= $this->userEntity->getFirstName() . ' ' . $this->userEntity->getName() ?>
                        </a>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <a href="<?= $this->lurl ?>/logout" title="Se déconnecter"><strong>Se déconnecter</strong></a>
                    <?php endif; ?>
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
        <?php
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        $menuHtml = '';
        foreach (static::MENU as $item) {
            $zone  = $item['zone'];
            $title = $item['title'];

            // Item visibility
            if (in_array($zone, $this->lZonesHeader)) {
                $active = isset($this->menu_admin) && $this->menu_admin === $zone ? ' class="active"' : '';
                $menuHtml .= '<li>';
                $menuHtml .= empty($item['uri']) ? '<span' . $active . '>' . $title . '</span>' : '<a href="' . $this->lurl . '/' . $item['uri'] . '"' . $active . '>' . $title . '</a>';

                if (false === empty($item['children'])) {
                    $menuHtml .= '<ul class="sous_menu">';
                    foreach ($item['children'] as $subItem) {
                        if (false === isset($subItem['zone']) || in_array($subItem['zone'], $this->lZonesHeader)) {
                            $menuHtml .= '<li><a href="' . $this->lurl . '/' . $subItem['uri'] . '">' . $subItem['title'] . '</a><li>';
                        }
                    }
                    $menuHtml .= '</ul>';
                }
                $menuHtml .= '</li>';
            }
        }

        ?>
        <?= $menuHtml ?>
    </ul>
</div>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
