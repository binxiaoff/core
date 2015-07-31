<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<h1>Détail de la commande N° <?=$this->transactions->id_transaction?></h1>
    <table border="0" cellspacing="0" cellpadding="10" width="100%" style="border: 1px solid #00675F;">
    	<tr>
        	<td colspan="3">&nbsp;</td>
      	</tr>
    	<tr>
        	<td align="center">            	
                <?php
				if($this->transactions->etat != 3)
				{
				?>
                	<a href="<?=$this->lurl?>/clients/detailsClient/aCommande/<?=$this->transactions->id_transaction?>/<?=$this->transactions->id_client?>" title="ANNULER LA COMMANDE" onClick="return confirm('Etes vous sur de vouloir annuler cette commande ?');" style="text-decoration:underline">
            		<strong>ANNULER LA COMMANDE</strong>
              	</a>
                <?php
				}
				else
				{
				?>
                	<a title="COMMANDE ANNUL&Eacute;E" style="text-decoration:none; cursor:default;">
                        <strong>COMMANDE ANNUL&Eacute;E</strong>
                    </a>
                <?php
				}
				?>
          	</td>
       	</tr>
        <tr>
        	<td colspan="3">&nbsp;</td>
      	</tr>
   	</table>
    <br><br> 
    <table border="0" cellspacing="0" cellpadding="10" width="100%">
        <tr>
            <th width="300" align="center" height="20">LIVRAISON</th>
            <th width="300" align="center">FACTURATION</th>
        </tr>
        <tr>
            <td width="300" align="center">
            	<?=$this->transactions->civilite_liv?> <?=$this->transactions->prenom_liv?> <?=$this->transactions->nom_liv?><br />
				<?=($this->transactions->societe_liv != ''?$this->transactions->societe_liv.'<br />':'')?>
                <?=$this->transactions->adresse1_liv?><br />
                <?=($this->transactions->adresse2_liv != ''?$this->transactions->adresse2_liv.'<br />':'')?>
                <?=$this->transactions->cp_liv?> <?=$this->transactions->ville_liv?><br />
                <?=$this->clients_adresses->getPays($this->transactions->id_pays_liv)?>
            </td>
            <td width="300" align="center">
            	<?=$this->transactions->civilite_fac?> <?=$this->transactions->prenom_fac?> <?=$this->transactions->nom_fac?><br />
				<?=($this->transactions->societe_fac != ''?$this->transactions->societe_fac.'<br />':'')?>
                <?=$this->transactions->adresse1_fac?><br />
                <?=($this->transactions->adresse2_fac != ''?$this->transactions->adresse2_fac.'<br />':'')?>
                <?=$this->transactions->cp_fac?> <?=$this->transactions->ville_fac?><br />
                <?=$this->clients_adresses->getPays($this->transactions->id_pays_fac)?>
            </td>
        </tr>
  	</table>
    <br><br> 
    <table border="0" cellspacing="0" cellpadding="0" class="detailscmd">
        <tr>
            <th width="330" align="left" height="30">PRODUIT</th>
            <th width="90" align="center">PRIX UNITAIRE</th>
            <th width="90" align="center">QUANTITE</th>
            <th width="90" align="center">PRIX TOTAL</th>
        </tr>
        <?php
		$i = 1;
		foreach($this->lProduits as $p)
		{
		?>
			<tr<?=($i%2 == 1?'':' class="odd"')?>>
				<td align="left" height="50"><?=$p['nom']?><br /><?=$p['detail'].' '.$p['type_detail']?></td>
				<td align="center"><?=number_format(($p['montant_promo']==0?$p['prix']:$p['prix_promo']),2,',',' ')?> &euro;</td>
				<td align="center"><?=$p['quantite']?></td>
				<td align="center"><?=number_format(($p['quantite']*($p['montant_promo']==0?$p['prix']:$p['prix_promo'])),2,',',' ')?> &euro;</td>
			</tr>
		<?php	
			$i++;
		}
		?>
        <?php
		foreach($this->lKdos as $k)
		{
		?>
			<tr<?=($i%2 == 1?'':' class="odd"')?>>
				<td align="left" height="50"><?=$k['nom']?><br /><?=$k['detail'].' '.$k['type_detail']?></td>
				<td align="center">
					<?php
					if(($k['montant_promo']==0?$k['prix']:$k['prix_promo']) > 0)
					{
					?>
						<?=number_format(($k['montant_promo']==0?$k['prix']:$k['prix_promo']),2,',',' ')?> &euro;
					<?php
					}
					?>
				</td>
				<td align="center"><?=$k['quantite']?></td>
				<td align="center">Gratuit</td>
			</tr>
		<?php	
			$i++;
		}
		?>
   	</table>
   	<table border="0" cellspacing="0" cellpadding="0" class="totalcmd">
        <tr<?=($i%2 == 1?'':' class="odd"')?>>
        	<th width="300" height="20" class="white">&nbsp;</th>
            <th width="90" class="white">&nbsp;</th>
            <td width="120" align="right">SOUS-TOTAL :</td>
            <td width="90" align="center"><?=number_format((($this->transactions->montant/100)-($this->transactions->fdp/100)),2,',',' ')?>&euro;</td>
        </tr>
        <tr<?=($i%2 == 1?'':' class="odd"')?>>
        	<th width="300" height="20" class="white">&nbsp;</th>
            <th width="90" class="white">&nbsp;</th>
            <td width="120" align="right">FDP :</td>
            <td width="90" align="center"><?=number_format($this->transactions->fdp/100,2,',',' ')?>&euro;</td>
        </tr>
        <?php
		if($this->transactions->montant_reduc > 0)
		{
		?>
        	<tr<?=($i%2 == 1?'':' class="odd"')?>>
            	<th width="300" height="20" class="white">&nbsp;</th>
            	<th width="90" class="white">&nbsp;</th>
                <td width="120" align="right">R&Eacute;DUCTIONS :</td>
                <td width="90" align="center">- <?=number_format($this->transactions->montant_reduc/100,2,',',' ')?>&euro;</td>
            </tr>
      	<?php
		}
		?>
        <tr<?=($i%2 == 1?'':' class="odd"')?>>
        	<th width="300" height="20" class="white">&nbsp;</th>
            <th width="90" class="white">&nbsp;</th>
            <td width="120" align="right"><strong>TOTAL :</strong></td>
            <td width="90" align="center"><strong><?=number_format($this->transactions->montant/100,2,',',' ')?>&euro;</strong></td>
        </tr>
  	</table>    
</div>