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
        <?php

        $menuHtml = '';
        foreach (static::MENU as $item) {
            $handle = $item['handle'];
            $title  = $item['title'];

            // Item visibility
            if (in_array($handle, $this->lZonesHeader)) {
                // Check user and adjust title for Dashboard item
                if ($title === 'Dashboard') {
                    if (in_array($_SESSION['user']['id_user_type'], [\users_types::TYPE_RISK, \users_types::TYPE_COMMERCIAL]) || in_array($_SESSION['user']['id_user'], [23, 26])) {
                        $title = 'Mon flux';
                    }
                }

                $active = $this->menu_admin === $handle ? ' class="active"' : '';
                $menuHtml .= '<li><a href="' . $this->lurl . '/' . $handle . '"' . $active . '>' . $title . '</a>';
                if (false === empty($item['children'])) {
                    $menuHtml .= '<ul class="sous_menu">';
                    foreach ($item['children'] as $subItem) {
                        $menuHtml .= '<li><a href="' . $this->lurl . '/' . $subItem['handle'] . '">' . $subItem['title'] . '</a><li>';
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
