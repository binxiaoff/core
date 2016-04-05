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
    });
</script>
<?php if ($this->Config['env'] != 'prod'): ?>
<script>
    jQuery.ajax(
        { url: "https://unilend.atlassian.net/s/4c6a101758c3c334ffe9cd010c34dc33-T/en_USrrn36f/71001/b6b48b2829824b869586ac216d119363/2.0.10/_/download/batch/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector-embededjs/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector-embededjs.js?locale=en-US&collectorId=f40c1120", type: "get", cache: true, dataType: "script" }
    );
</script>
<?php endif; ?>
<div id="header">
    <div class="logo_header">

        <a href="<?= $this->lurl ?>" title="Administration du site"><img src="<?= $this->surl ?>/styles/default/images/logo.png" alt="Unilend"/></a>
    </div>
    <div class="titre_header">Administration de votre site</div>
    <div class="bloc_info_header">
        <a href="<?= $this->lurl ?>/users/edit_perso/<?= $_SESSION['user']['id_user'] ?>" class="thickbox">
            <?= $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'] ?>
        </a>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <?= date('d/m/Y') ?>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="<?= $this->lurl ?>/logout" title="Se deconnecter"><strong>Se deconnecter</strong></a><br/><br/>
        <a href="<?= $this->urlfront ?>" title="Retourner sur le site" target="_blank"><strong>Retourner sur le site</strong></a>
    </div>
</div>
<div id="navigation">
    <ul id="menu_deroulant">
        <?php if (in_array('dashboard', $this->lZonesHeader) && $this->cms == 'iZicom'): ?>
            <li>
                <a href="<?= $this->lurl ?>" title="Dashboard"<?= ($this->menu_admin == 'dashboard' ? ' class="active"' : '') ?>>Dashboard</a>
            </li>
        <?php endif; ?>
        <?php if (in_array('edition', $this->lZonesHeader)): ?>
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
        <?php if (in_array('configuration', $this->lZonesHeader)): ?>
            <li>
                <a href="<?= $this->lurl ?>/settings" title="Configuration"<?= ($this->menu_admin == 'configuration' ? ' class="active"' : '') ?>>Configuration</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/settings" title="Paramètres">Paramètres</a></li>
                    <li><a href="<?= $this->lurl ?>/mails/logs" title="Historique des Mails">Historique des Mails</a></li>
                    <li><a href="<?= $this->lurl ?>/users/logs" title="Historique des connexions">Logs connexions</a></li>
                    <li><a href="<?= $this->lurl ?>/routages" title="Routage">Routage</a></li>
                    <li><a href="<?= $this->lurl ?>/partenaires" title="Campagnes">Campagnes</a></li>
                    <li><a href="<?= $this->lurl ?>/partenaires/types" title="Types de campagnes">Types de campagnes</a></li>
                    <li><a href="<?= $this->lurl ?>/partenaires/medias" title="Medias de campagnes">Medias de campagnes</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('stats', $this->lZonesHeader)): ?>
            <li>
                <a href="<?= $this->lurl ?>/queries" title="Stats"<?= ($this->menu_admin == 'stats' ? ' class="active"' : '') ?>>Stats</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/queries" title="Requêtes">Requêtes</a></li>
                    <?php if ($this->google_analytics != '' && $this->google_mail != '' && $this->google_password != ''): ?>
                        <li><a href="<?= $this->lurl ?>/stats" title="Google Analytics">Google Analytics</a></li>
                    <?php endif; ?>
                    <li><a href="<?= $this->lurl ?>/stats/etape_inscription" title="Etape d'inscription">Etape d'inscription</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_revenus_csv" title="Requete revenus">Requete revenus</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_dossiers" title="Requete dossiers">Requete dossiers</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_beneficiaires" title="Requete beneficiaires">Requete beneficiaires</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_infosben" title="Requete infosben">Requete infosben</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_encheres" title="Requete Toutes les enchères">Requete Toutes les enchères</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/tous_echeanciers_pour_projet" title="Echeanciers projet">Echeanciers projet</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/infos_preteurs" title="Requete Infos preteurs">Requete infos preteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/stats/requete_donnees_financieres" title="Requete Données financières emprunteurs">Requete Données financières emprunteurs</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('preteurs', $this->lZonesHeader)): ?>
            <li class="last">
                <a href="<?= $this->lurl ?>/preteurs" title="preteurs"<?= ($this->menu_admin == 'preteurs' ? ' class="active"' : '') ?>>Preteurs</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/preteurs" title="Arbo preteurs">Arbo prêteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/search" title="Recherche prêteurs">Recherche prêteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/liste_preteurs_non_inscrits" title="Liste des prêteurs non inscrits">Liste des prêteurs non inscrits</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/activation" title="Activation prêteurs">Activation prêteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/offres_de_bienvenue" title="Gestion offre de bienvenue">Gestion offre de bienvenue</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/control_fiscal_city" title="Matching ville fiscale">Matching ville fiscale</a></li>
                    <li><a href="<?= $this->lurl ?>/preteurs/control_birth_city" title="Matching ville de naissance">Matching ville de naissance</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('emprunteurs', $this->lZonesHeader)): ?>
            <li class="last">
                <a href="<?= $this->lurl ?>/emprunteurs" title="emprunteurs"<?= ($this->menu_admin == 'emprunteurs' ? ' class="active"' : '') ?>>Emprunteurs</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/emprunteurs" title="Arbo emprunteurs">Arbo emprunteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/emprunteurs/gestion" title="Gestion emprunteurs">Gestion emprunteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/prescripteurs/gestion" title="Gestion prescripteur">Gestion prescripteur</a></li>
                    <li><a href="<?= $this->lurl ?>/dossiers" title="Gestion des dossiers">Gestion des dossiers</a></li>
                    <li><a href="<?= $this->lurl ?>/dossiers/funding" title="Dossiers en funding">Dossiers en funding</a></li>
                    <li><a href="<?= $this->lurl ?>/dossiers/remboursements" title="Remboursements">Remboursements</a></li>
                    <li><a href="<?= $this->lurl ?>/dossiers/no_remb" title="Erreurs remboursements">Erreurs remboursements</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('transferts', $this->lZonesHeader)): ?>
            <li class="last">
                <a href="<?= $this->lurl ?>/transferts" title="Dépôt de fonds"<?= ($this->menu_admin == 'transferts' ? ' class="active"' : '') ?>>Dépôt de fonds</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/transferts/preteurs" title="Prêteurs">Prêteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/transferts/emprunteurs" title="Emprunteurs">Emprunteurs</a></li>
                    <li><a href="<?= $this->lurl ?>/transferts/non_attribues" title="Non attribués">Non attribués</a></li>
                    <li><a href="<?= $this->lurl ?>/transferts/rattrapage_offre_bienvenue" title="Rattrapage offre de bienvenue">Rattrapage offre de bienvenue</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <?php if (in_array('admin', $this->lZonesHeader)): ?>
            <li class="last">
                <a href="<?= $this->lurl ?>/users" title="Administrateurs"<?= ($this->menu_admin == 'admin' ? ' class="active"' : '') ?>>Administrateurs</a>
                <ul class="sous_menu">
                    <li><a href="<?= $this->lurl ?>/zones" title="Droits Administrateurs">Droits Administrateurs</a></li>
                </ul>
            </li>
        <?php endif; ?>
    </ul>
</div>
