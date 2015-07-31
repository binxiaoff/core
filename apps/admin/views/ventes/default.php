<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/ventes" title="Ventes">Ventes</a></li>
    </ul>
	<h1>Données de ventes</h1>
    <form method="post" name="periode" id="periode" action="<?=$this->lurl?>/ventes" enctype="multipart/form-data">
  		<table class="cont_periode">
        	<tr>
            	<td>
                	<table class="form_periode">
                        <tr>
                            <th>Choisissez un mois et une année</th>
                        </tr>
                        <tr>
                            <td>
                                <input type="submit" name="prev" id="prev" value="<<" class="btn_periode" />
                                <input type="text" name="mois" id="mois" value="<?=($this->mois<10?'0'.str_replace('0','',$this->mois):$this->mois)?>" class="input_court center" />
                                <input type="text" name="annee" id="annee" value="<?=$this->annee?>" class="input_court center" />
                                <input type="submit" name="voir" id="voir" value="Voir" class="btn_periode" />
                                <input type="submit" name="next" value=">>" class="btn_periode" />
                            </td>
                        </tr>
                    </table>
                </td>
                <td>
                	<table class="form_periode">
                        <tr>
                            <th colspan="2">Choisissez une période précise</th>
                        </tr>
                        <tr>
                            <td class="multiple"><strong>Du</strong> <?=$this->dates->selectDateYearDesc($_POST['du-annee'].'-'.$_POST['du-mois'].'-'.$_POST['du-jour'],'du','select_periode')?></td>
                            <td rowspan="2" class="btn_tab"><input type="submit" name="intervalle" id="intervalle" value="Voir" class="btn_periode" /></td>
                        </tr>
                        <tr>
                            <td class="multiple"><strong>Au</strong> <?=$this->dates->selectDateYearDesc($_POST['au-annee'].'-'.$_POST['au-mois'].'-'.$_POST['au-jour'],'au','select_periode')?></td>
                        </tr>
                    </table>
                </td>
           	</tr>
       	</table>
	</form>
    <h2>Chiffre du <?=$this->deb_jour.'/'.$this->deb_mois.'/'.$this->deb_annee?> au <?=$this->fin_jour.'/'.$this->fin_mois.'/'.$this->fin_annee?> sur <?=$this->nb_jours?> jour<?=($this->nb_jours>1?'s':'')?></h2>    
    <table class="tablesorter">
    	<thead>
            <tr>
                <th>&nbsp;</th>
                <th>Total sur p&eacute;riode</th>
                <th>Moyenne quotidienne</th>
            </tr>
        </thead>
        <tbody>
        <?
		if($this->NBcmd != 0)
		{
		?>
        	<tr>
                <td><strong>CA réalisé</strong></td>
                <td><?=number_format($this->CAcmd,2,',',' ')?> €</td>
                <td><?=number_format($this->CAcmd/$this->nb_jours,2,',',' ')?> €</td>
            </tr>
            <tr class="odd">
                <td><strong>Nombre de commandes</strong></td>
                <td><?=number_format($this->NBcmd,0,',',' ')?> commande(s)</td>
                <td><?=number_format($this->NBcmd/$this->nb_jours,2,',',' ')?> commande(s)</td>
            </tr>
            <tr>
                <td><strong>Panier moyen</strong></td>
                <td><?=number_format($this->CAcmd/$this->NBcmd,2,',',' ')?> €</td>
                <td><?=number_format(($this->CAcmd/$this->NBcmd)/$this->nb_jours,2,',',' ')?> €</td>
            </tr>
		<?
		}
		else
		{
		?>
			<tr>
				<td colspan="3" align="center"><em>Aucunes commandes sur la p&eacute;riode !</em></td>
			</tr>
		<?
		}
		?>
		<?
		if($this->NBadb != 0)
		{
		?>
        	<tr class="odd">
                <td><strong>Nb d'abandons</strong></td>
                <td><?=number_format($this->NBadb,0,',',' ')?> panier(s)</td>
                <td><?=number_format($this->NBadb/$this->nb_jours,2,',',' ')?> panier(s)</td>
            </tr>
            <tr>
                <td><strong>% d'abandons</strong></td>
                <td><?=number_format(($this->NBadb/($this->NBadb+$this->NBcmd))*100,2,',',' ')?> %</td>
                <td><?=number_format((($this->NBadb/($this->NBadb+$this->NBcmd))*100)/$this->nb_jours,2,',',' ')?> %</td>
            </tr>
		<?
		}
		else
		{
		?>
        	<tr class="odd">
				<td colspan="3" align="center"><em>Aucun abandon sur la p&eacute;riode !</em></td>
			</tr>
		<?
		}
		?>        
        </tbody>
  	</table>
    <?
	if(count($this->lPartenaires) > 0)
	{
	?>
    	<br><br>
        <h2>Campagnes du <?=$this->deb_jour.'/'.$this->deb_mois.'/'.$this->deb_annee?> au <?=$this->fin_jour.'/'.$this->fin_mois.'/'.$this->fin_annee?> sur <?=$this->nb_jours?> jour<?=($this->nb_jours>1?'s':'')?></h2>    
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Campagne</th>
                    <th>CA sur p&eacute;riode</th>
                    <th>Nombre de clics</th>
                    <th>Nombre de commandes</th>
                </tr>
            </thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lPartenaires as $p)
			{
				// Recuperation du CA et du Nb de commandes
				$capart = $this->partenaires->statCA($p['id_partenaire'],$this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);
				$nbcmd = $this->partenaires->statCmde($p['id_partenaire'],$this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);
				$nbclic = $this->partenaires->nbClic($p['id_partenaire'],$this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);
			
				if($nbcmd > 0)
                {
                ?>
                    <tr<?=($i%2 == 1?'':' class="odd"')?>>
                        <td><strong><?=$p['nom']?></strong></td>
                        <td><?=number_format($capart,2,',',' ')?> €</td>
                        <td><?=number_format($nbclic,0,',',' ')?> clic(s)</td>
                        <td><?=number_format($nbcmd,0,',',' ')?> commande(s)</td>
                    </tr>
                <?
					$i++;
                }
            }
            ?>        
            </tbody>
        </table>
 	<?php
	}
	?>
</div>