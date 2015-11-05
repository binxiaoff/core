<script type="text/javascript">
	$(document).ready(function(){
		$(".searchBox").colorbox({
			onComplete:function(){
				$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
				$("#datepik_from").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
				$("#datepik_to").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
			}
		});
	});
</script>
<div id="header">
	<div class="logo_header">
    	<a href="<?=$this->lurl?>" title="Administration du site"><img src="<?=$this->surl?>/styles/default/images/logo.png" alt="Unilend" /></a>
    </div>
    <div class="titre_header">Administration de votre site</div>
    <div class="bloc_info_header">
    	<?php
    	// On ajoute le lien vers la page d'edition de profil
		?>
        <a href="<?=$this->lurl?>/users/edit_perso/<?=$_SESSION['user']['id_user']?>" class="thickbox">
            <?=$_SESSION['user']['firstname'].' '.$_SESSION['user']['name']?>
        </a>&nbsp;&nbsp;|&nbsp;&nbsp;


		<?=date('d/m/Y')?>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="<?=$this->lurl?>/logout" title="Se deconnecter"><strong>Se deconnecter</strong></a><br /><br />
        <a href="<?=$this->urlfront?>" title="Retourner sur le site" target="_blank"><strong>Retourner sur le site</strong></a>
    </div>
</div>
<div id="navigation">
	<ul id="menu_deroulant">
    	<?
		if(in_array('dashboard',$this->lZonesHeader) && $this->cms == 'iZicom')
		{
		?>
    		<li><a href="<?=$this->lurl?>" title="Dashboard"<?=($this->menu_admin == 'dashboard'?' class="active"':'')?>>Dashboard</a></li>
      	<?
		}
		if(in_array('edition',$this->lZonesHeader))
		{
		?>
        	<li>
            	<a href="<?=$this->lurl?>/tree" title="Edition"<?=($this->menu_admin == 'edition'?' class="active"':'')?>>Edition</a>
                <ul class="sous_menu">
                	<li><a href="<?=$this->lurl?>/tree" title="Arborescence">Arborescence</a></li>
                    <li><a href="<?=$this->lurl?>/blocs" title="Blocs">Blocs</a></li>
                    <li><a href="<?=$this->lurl?>/menus" title="Menus">Menus</a></li>
                    <li><a href="<?=$this->lurl?>/templates" title="Templates">Templates</a></li>
                    <li><a href="<?=$this->lurl?>/traductions" title="Traductions"<?=($this->menu_admin == 'traductions'?' class="active"':'')?>>Traductions</a></li>
                    <li><a href="<?=$this->lurl?>/mails" title="Mails">Mails</a></li>
                </ul>
          	</li>
        <?
		}
		if(in_array('configuration',$this->lZonesHeader))
		{
		?>
        	<li>
            	<a href="<?=$this->lurl?>/settings" title="Configuration"<?=($this->menu_admin == 'configuration'?' class="active"':'')?>>Configuration</a>
                <ul class="sous_menu">
                    <li><a href="<?=$this->lurl?>/settings" title="Paramètres">Paramètres</a></li>
                    <li><a href="<?=$this->lurl?>/mails/logs" title="Historique des Mails">Historique des Mails</a></li>
					<li><a href="<?=$this->lurl?>/users/logs" title="Historique des connexions">Logs connexions</a></li>
					<li><a href="<?=$this->lurl?>/routages" title="Routage">Routage</a></li>
                    <li><a href="<?=$this->lurl?>/partenaires" title="Campagnes">Campagnes</a></li>
                    <li><a href="<?=$this->lurl?>/partenaires/types" title="Types de campagnes">Types de campagnes</a></li>
                    <li><a href="<?=$this->lurl?>/partenaires/medias" title="Medias de campagnes">Medias de campagnes</a></li>
                </ul>
          	</li>
        <?
		}
		if(in_array('stats',$this->lZonesHeader))
		{
		?>
        	<li>
            	<a href="<?=$this->lurl?>/queries" title="Stats"<?=($this->menu_admin == 'stats'?' class="active"':'')?>>Stats</a>
                <ul class="sous_menu">
                	<li><a href="<?=$this->lurl?>/queries" title="Requêtes">Requêtes</a></li>
                    <?
                    if($this->google_analytics != '' && $this->google_mail != '' && $this->google_password != '')
                    {
                    ?>
                        <li><a href="<?=$this->lurl?>/stats" title="Google Analytics">Google Analytics</a></li>
                    <?
                    }
                    ?>
                    <li><a href="<?=$this->lurl?>/stats/etape_inscription" title="Etape d'inscription">Etape d'inscription</a></li>

                    <li><a href="<?=$this->lurl?>/stats/requete_revenus_csv" title="Requete revenus">Requete revenus</a></li>
                    <li><a href="<?=$this->lurl?>/stats/requete_dossiers" title="Requete dossiers">Requete dossiers</a></li>
                    <li><a href="<?=$this->lurl?>/stats/requete_beneficiaires" title="Requete beneficiaires">Requete beneficiaires</a></li>
                    <li><a href="<?=$this->lurl?>/stats/requete_infosben" title="Requete infosben">Requete infosben</a></li>
                    <li><a href="<?=$this->lurl?>/stats/requete_encheres" title="Requete Toutes les enchères">Requete Toutes les enchères</a></li>
                    <li><a href="<?=$this->lurl?>/stats/tous_echeanciers_pour_projet" title="Echeanciers projet">Echeanciers projet</a></li>
                    <li><a href="<?=$this->lurl?>/stats/infos_preteurs" title="Requete Infos preteurs">Requete infos preteurs</a></li>
                    <li><a href="<?=$this->lurl?>/stats/requete_donnees_financieres" title="Requete Données financières emprunteurs">Requete Données financières emprunteurs</a></li>
                    <li><a href="<?=$this->lurl?>/stats/requete_etude_base_preteurs" title="Requête étude de la base des prêteurs">Requête étude de la base des prêteurs</a></li>
                </ul>
          	</li>
        <?
		}
		if(in_array('preteurs',$this->lZonesHeader) && $this->cms == 'iZicom')
		{
		?>
        	<li class="last"><a href="<?=$this->lurl?>/preteurs" title="preteurs"<?=($this->menu_admin == 'preteurs'?' class="active"':'')?>>Preteurs</a>
                <ul class="sous_menu">
                    <li><a href="<?=$this->lurl?>/preteurs" title="Arbo preteurs">Arbo prêteurs</a></li>
                    <li><a href="<?=$this->lurl?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a></li>
                    <li><a href="<?=$this->lurl?>/preteurs/search" title="Recherche prêteurs">Recherche prêteurs</a></li>
                    <li><a href="<?=$this->lurl?>/preteurs/liste_preteurs_non_inscrits" title="Liste des prêteurs non inscrits">Liste des prêteurs non inscrits</a></li>
                    <li><a href="<?=$this->lurl?>/preteurs/activation" title="Activation prêteurs">Activation prêteurs</a></li>
                    <li><a href="<?=$this->lurl?>/preteurs/offres_de_bienvenue" title="Gestion offre de bienvenue">Gestion offre de bienvenue</a></li>
                    <li><a href="<?=$this->lurl?>/preteurs/control_fiscal_city" title="Matching ville fiscale">Matching ville fiscale</a></li>
                    <li><a href="<?=$this->lurl?>/preteurs/control_birth_city" title="Matching ville de naissance">Matching ville de naissance</a></li>
                </ul>
            </li>
        <?
		}
		if(in_array('emprunteurs',$this->lZonesHeader) && $this->cms == 'iZicom')
		{
		?>
        	<li class="last"><a href="<?=$this->lurl?>/emprunteurs" title="emprunteurs"<?=($this->menu_admin == 'emprunteurs'?' class="active"':'')?>>Emprunteurs</a>
                <ul class="sous_menu">
                    <li><a href="<?=$this->lurl?>/emprunteurs" title="Arbo emprunteurs">Arbo emprunteurs</a></li>
                    <li><a href="<?=$this->lurl?>/emprunteurs/gestion" title="Gestion emprunteurs">Gestion emprunteurs</a></li>
                    <li><a href="<?=$this->lurl?>/dossiers" title="Gestion des dossiers">Gestion des dossiers</a></li>
                    <li><a href="<?=$this->lurl?>/dossiers/funding" title="Dossiers en funding">Dossiers en funding</a></li>
                    <li><a href="<?=$this->lurl?>/dossiers/remboursements" title="Remboursements">Remboursements</a></li>
                    <li><a href="<?=$this->lurl?>/dossiers/no_remb" title="Erreurs remboursements">Erreurs remboursements</a></li>
                </ul>
            </li>
        <?
		}
		if(in_array('transferts',$this->lZonesHeader) && $this->cms == 'iZicom')
		{
		?>
        	<li class="last"><a href="<?=$this->lurl?>/transferts" title="Dépot de fonds"<?=($this->menu_admin == 'transferts'?' class="active"':'')?>>Dépot de fonds</a>
            	<ul class="sous_menu">
                    <li><a href="<?=$this->lurl?>/transferts" title="Virements">Virements</a></li>
                    <li><a href="<?=$this->lurl?>/transferts/prelevements" title="Prélèvements">Prélèvements</a></li>
					<li><a href="<?= $this->lurl ?>/transferts/virements_ra" title="Virements remboursement anticipé">Virements RA</a></li>
                </ul>
            </li>
        <?
		}
		if(in_array('admin',$this->lZonesHeader) && $this->cms == 'iZicom')
		{
		?>
        	<li class="last"><a href="<?=$this->lurl?>/users" title="Administrateurs"<?=($this->menu_admin == 'admin'?' class="active"':'')?>>Administrateurs</a>
            	<ul class="sous_menu">
                    <li><a href="<?=$this->lurl?>/zones" title="Droits Administrateurs">Droits Administrateurs</a></li>
            	</ul>
            </li>
        <?
		}

        if(isset($this->equinoa) && $this->equinoa == true)
		{
		?>
        	<li class="last"><a href="#" title="equinoa"<?=($this->menu_admin == 'equinoa'?' class="active"':'')?>>equinoa</a>
                <ul class="sous_menu">
                	<li><a href="<?=$this->lurl?>/produits" title="Produits">Produits</a></li>
                	<li><a href="<?=$this->lurl?>/temproduits" title="Templates Produits">Templates Produits</a></li>
                    <li><a href="<?=$this->lurl?>/promotions" title="Promotions">Promotions</a></li>
                    <li><a href="<?=$this->lurl?>/fdp" title="Frais de port">Frais de port</a></li>
                    <li><a href="<?=$this->lurl?>/fdp/types" title="Types de FDP">Types de FDP</a></li>
                    <li><a href="<?=$this->lurl?>/brands" title="Gestion des marques">Gestion des marques</a></li>

                    <li><a href="<?=$this->lurl?>/produits/avis" title="Avis des produits">Avis des produits</a></li>
                    <li><a href="<?=$this->lurl?>/commandes" title="Commandes reçues">Commandes reçues</a></li>
                    <li><a href="<?=$this->lurl?>/commandes/boxSearch" class="searchBox">Rechercher commandes</a></li>
                    <li><a href="<?=$this->lurl?>/clients" title="Gestion des clients">Gestion des clients</a></li>
                    <li><a href="<?=$this->lurl?>/clients/groupes" title="Groupes de clients">Groupes de clients</a></li>
                    <li><a href="<?=$this->lurl?>/clients/newsletter" title="Newsletter">Newsletter</a></li>
                    <li><a href="<?=$this->lurl?>/ventes" title="Ventes">Ventes</a></li>
                    <li><a href="<?=$this->lurl?>/indexation" title="Indexer le Site">Indexer le Site</a></li>
                    <li><a href="<?=$this->lurl?>/sitemap/<?=$this->cms?>" title="Créer le Sitemap">Créer le Sitemap</a></li>
                    <li><a href="<?=$this->lurl?>/settings/cache" title="Vider le cache">Vider le cache</a></li>
                    <li><a href="<?=$this->lurl?>/settings/crud" title="Vider le CRUD">Vider le CRUD</a></li>
                </ul>
            </li>
        <?
		}
		?>
    </ul>
</div>