<script type="text/javascript">
    $(function () {
        $(".searchBox").colorbox({
            onComplete: function () {
                $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
                $("#datepik_from").datepicker({
                    showOn: 'both',
                    buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
                    buttonImageOnly: true,
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
                });
                $("#datepik_to").datepicker({
                    showOn: 'both',
                    buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
                    buttonImageOnly: true,
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
                });
            }
        });

        $('#quick_search').submit(function(event) {
            var form = $(this),
                project = form.children('[name=project]').val(),
                lender = form.children('[name=lender]').val()

            if ('' != project && project == parseInt(project)) {
                event.preventDefault();
                window.location.replace('/dossiers/edit/' + project)
                return
            }

            if ('' != lender && lender == parseInt(lender)) {
                console.log('lender')
                var input = document.createElement('input')
                    input.type  = 'text'
                    input.name  = 'id'
                    input.value = lender

                form.attr('action', '/preteurs/gestion')
                form.append('<input type="hidden" name="form_search_preteur" value="1" />')
                form.append('<input type="hidden" name="id" value="' + lender + '" />')
                return
            }

            event.preventDefault();
        })
    });
</script>
<style>
    #quick_search {margin-top: 10px}
</style>
<div id="header">
    <div class="logo_header">
        <a href="<?= $this->lurl ?>"><img src="<?= $this->surl ?>/styles/default/images/logo.png" alt="Unilend"/></a>
    </div>
    <div class="titre_header">Administration</div>
    <div class="bloc_info_header">
        <div>
            <a href="<?= $this->lurl ?>/users/edit_perso/<?= $_SESSION['user']['id_user'] ?>" class="thickbox">
                <?= $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'] ?>
            </a>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <?= date('d/m/Y') ?>&nbsp;&nbsp;|&nbsp;&nbsp;
            <a href="<?= $this->lurl ?>/logout" title="Se déconnecter"><strong>Se déconnecter</strong></a>
        </div>
        <form id="quick_search" method="post">
            <input type="text" name="project" title="ID projet" placeholder="ID projet" size="10" />
            <input type="text" name="lender" title="ID client" placeholder="ID client" size="10" />
            <!-- Trick for enabling submitting form in Safari and IE -->
            <input type="submit" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" />
        </form>
    </div>
</div>
<div id="navigation">
    <ul id="menu_deroulant">
        <?php if (in_array('dashboard', $this->lZonesHeader)) : ?>
            <li>
                <a href="<?= $this->lurl ?>" title="Dashboard"<?= ($this->menu_admin == 'dashboard' ? ' class="active"' : '') ?>>Dashboard</a>
            </li>
        <?php endif; ?>
        <?php if (in_array('edition', $this->lZonesHeader)) : ?>
            <li>
                <a href="<?= $this->lurl ?>/tree" title="Edition"<?= ($this->menu_admin == 'edition' ? ' class="active"' : '') ?>>Edition</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/tree" title="Arborescence">Arborescence</a></li>
                    <li><a href="<?= $this->lurl ?>/blocs" title="Blocs">Blocs</a></li>
                    <li><a href="<?= $this->lurl ?>/menus" title="Menus">Menus</a></li>
                    <li><a href="<?= $this->lurl ?>/templates" title="Templates">Templates</a></li>
                    <li><a href="<?= $this->lurl ?>/traductions" title="Traductions"<?= ($this->menu_admin == 'traductions' ? ' class="active"' : '') ?>>Traductions</a></li>
                    <li><a href="<?= $this->lurl ?>/mails" title="Mails">Mails</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('configuration', $this->lZonesHeader)) : ?>
            <li>
                <a href="<?= $this->lurl ?>/settings" title="Configuration"<?= ($this->menu_admin == 'configuration' ? ' class="active"' : '') ?>>Configuration</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/settings" title="Paramètres">Paramètres</a></li>
                    <li><a href="<?= $this->lurl ?>/mails/emailhistory" title="Historique des Mails">Historique des Mails</a></li>
                    <li><a href="<?= $this->lurl ?>/users/logs" title="Historique des connexions">Logs connexions</a></li>
                    <li><a href="<?= $this->lurl ?>/partenaires" title="Campagnes">Campagnes</a></li>
                    <li><a href="<?= $this->lurl ?>/partenaires/types" title="Types de campagnes">Types de campagnes</a></li>
                    <li><a href="<?= $this->lurl ?>/partenaires/medias" title="Medias de campagnes">Medias de campagnes</a></li>
                    <li><a href="<?= $this->lurl ?>/project_rate_settings" title="Grille de taux">Grille de taux</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('stats', $this->lZonesHeader)) : ?>
            <li>
                <a href="<?= $this->lurl ?>/queries" title="Stats"<?= ($this->menu_admin == 'stats' ? ' class="active"' : '') ?>>Stats</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/queries" title="Requêtes">Requêtes</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/etape_inscription" title="Etape d'inscription">Etape d'inscription</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_source_emprunteurs" title="Requete source emprunteurs">Sources emprunteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_revenus_download" title="Revenus">Revenus</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_beneficiaires_csv" title="Bénéficiaires">Bénéficiaires</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_infosben" title="Infosben">Infosben</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_encheres" title="Toutes les enchères">Toutes les enchères</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/tous_echeanciers_pour_projet" title="Echeanciers projet">Echeanciers projet</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/autobid_statistic" title="Statistiques Autolend">Statistiques Autolend</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('preteurs', $this->lZonesHeader)) : ?>
            <li class="last">
                <a href="<?= $this->lurl ?>/preteurs" title="preteurs"<?= ($this->menu_admin == 'preteurs' ? ' class="active"' : '') ?>>Preteurs</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/preteurs/search" title="Recherche prêteurs">Recherche prêteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/activation" title="Activation prêteurs">Activation prêteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/offres_de_bienvenue" title="Offre de bienvenue">Offre de bienvenue</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/control_fiscal_city" title="Matching ville fiscale">Matching ville fiscale</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/control_birth_city" title="Matching ville de naissance">Matching ville de naissance</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('emprunteurs', $this->lZonesHeader)) : ?>
            <li class="last">
                <a href="<?= $this->lurl ?>/emprunteurs" title="emprunteurs"<?= ($this->menu_admin == 'emprunteurs' ? ' class="active"' : '') ?>>Emprunteurs</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/dossiers" title="Dossiers">Dossiers</a></li>
                    <li><a href="<?= $this->lurl ?>/emprunteurs/gestion" title="Emprunteurs">Emprunteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/prescripteurs/gestion" title="Prescripteur">Prescripteur</a></li>
                    <li><a href="<?= $this->lurl ?>/dossiers/funding" title="Dossiers en funding">Dossiers en funding</a></li>
                    <li><a href="<?= $this->lurl ?>/dossiers/remboursements" title="Remboursements">Remboursements</a></li>
                    <li><a href="<?= $this->lurl ?>/dossiers/no_remb" title="Erreurs remboursements">Erreurs remboursements</a></li>
                    <li><a href="<?= $this->lurl ?>/dossiers/status" title="Suivi statuts projets">Suivi statuts projets</a></li>
                    <li><a href="<?= $this->lurl ?>/product" title="Gestion produits">Gestion produits</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('transferts', $this->lZonesHeader)) : ?>
            <li class="last">
                <a href="<?= $this->lurl ?>/transferts" title="Dépôt de fonds"<?= ($this->menu_admin == 'transferts' ? ' class="active"' : '') ?>>Dépôt de fonds</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/transferts/preteurs" title="Prêteurs">Prêteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/transferts/emprunteurs" title="Emprunteurs">Emprunteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/transferts/non_attribues" title="Non attribués">Non attribués</a></li>
                    <li><a href="<?= $this->lurl ?>/transferts/rattrapage_offre_bienvenue" title="Rattrapage offre de bienvenue">Rattrapage offre de bienvenue</a></li>
                    <li><a href="<?= $this->lurl ?>/transferts/deblocage" title="Déblocage">Déblocage des fonds</a></li>
                    <li><a href="<?= $this->lurl ?>/transferts/succession" title="Succession">Succession (Transfert de solde et prêts)</a></li>
                    <li><a href="<?= $this->lurl ?>/client_atypical_operation" title="Lutte Anti-Balanchiment">Opérations atypiques</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('admin', $this->lZonesHeader)) : ?>
            <li class="last">
                <a href="<?= $this->lurl ?>/users" title="Administration"<?= ($this->menu_admin == 'admin' ? ' class="active"' : '') ?>>Administrateurs</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/zones" title="Droits Administrateurs">Droits Administrateurs</a></li>
                </ul>
            </li>
        <?php endif; ?>
    </ul>
</div>
