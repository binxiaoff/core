
<table class="table" id="table_tri">
<tr>
    <th width="350">
        <div class="th-wrap"><i title="<?=$this->lng['preteur-projets']['info-nom-projet']?>" class="icon-person tooltip-anchor"></i></div>
    </th>
    <th width="90">
        <div class="th-wrap"><i title="<?=$this->lng['preteur-projets']['info-capacite-remboursement']?>" class="icon-gauge tooltip-anchor"></i></div>
    </th>
    <th width="90">
        <div class="th-wrap"><i title="<?=$this->lng['preteur-projets']['info-montant']?>" class="icon-bank tooltip-anchor"></i></div>
    </th>
    <th width="60">
        <div class="th-wrap"><i title="<?=$this->lng['preteur-projets']['info-duree']?>" class="icon-calendar tooltip-anchor"></i></div>
    </th>
    <th width="60">
        <div class="th-wrap"><i title="<?=$this->lng['preteur-projets']['info-tx-cible']?>" class="icon-graph tooltip-anchor"></i></div>
    </th>
    <th width="110">
        <div class="th-wrap"><i title="<?=$this->lng['preteur-projets']['info-temps-restant']?>" class="icon-clock tooltip-anchor"></i></div>
    </th>
    <th width="120">
        <div class="th-wrap"><i title="<?=$this->lng['preteur-projets']['info-cta']?>" class="icon-arrow-next tooltip-anchor"></i></div>
    </th>
</tr>
<?
foreach($this->lProjetsFunding as $pf)
{
	$this->projects_status->getLastStatut($pf['id_project']);
	
	// On recupere les info companies
	$this->companies->get($pf['id_company'],'id_company');
	$this->companies_details->get($pf['id_company'],'id_company');
	
	$inter = $this->dates->intervalDates(date('Y-m-d h:i:s'),$pf['date_retrait'].' '.$this->heureFinFunding.':00');
	if($inter['mois']>0) $dateRest = $inter['mois'].' '.$this->lng['preteur-projets']['mois'];
	else $dateRest = '';
	
	// dates pour le js
	$mois_jour = $this->dates->formatDate($pf['date_retrait'],'F d');
	$annee = $this->dates->formatDate($pf['date_retrait'],'Y');
	
	// favori
	if($this->favoris->get($this->clients->id_client,'id_project = '.$pf['id_project'].' AND id_client'))
		$favori = 'active';
	else
		$favori = '';
	
	
	$CountEnchere = $this->bids->counter('id_project = '.$pf['id_project']);
	//$avgRate = $this->bids->getAVG($pf['id_project'],'rate');
	
	// moyenne pondéré
	$montantHaut = 0;
	$montantBas = 0;
	// si fundé ou remboursement
	if($this->projects_status->status==60 || $this->projects_status->status==80)
	{
		foreach($this->loans->select('id_project = '.$pf['id_project']) as $b)
		{
			$montantHaut += ($b['rate']*($b['amount']/100));
			$montantBas += ($b['amount']/100);
		}
	}
	// funding ko
	elseif($this->projects_status->status==70)
	{
		foreach($this->bids->select('id_project = '.$pf['id_project']) as $b)
		{
			$montantHaut += ($b['rate']*($b['amount']/100));
			$montantBas += ($b['amount']/100);
		}	
	}
	// emprun refusé
	elseif($this->projects_status->status==75)
	{
		foreach($this->bids->select('id_project = '.$pf['id_project'].' AND status = 1') as $b)
		{
			$montantHaut += ($b['rate']*($b['amount']/100));
			$montantBas += ($b['amount']/100);
		}	
	}
	else
	{
		foreach($this->bids->select('id_project = '.$pf['id_project'].' AND status = 0') as $b)
		{
			$montantHaut += ($b['rate']*($b['amount']/100));
			$montantBas += ($b['amount']/100);
		}
		
	}
	if($montantHaut>0 && $montantBas >0)
	$avgRate = ($montantHaut/$montantBas);
	else $avgRate = 0;
	
	?>
	
	<tr class="unProjet" id="project<?=$pf['id_project']?>">
		<td>
        	<?
			if($this->projects_status->status >= 60)
			{
				$dateRest = 'Terminé';
			}
			else
			{
			?>
			<script>
				var cible<?=$pf['id_project']?> = new Date('<?=$mois_jour?>, <?=$annee?> <?=$this->heureFinFunding?>:00');
				var letime<?=$pf['id_project']?> = parseInt(cible<?=$pf['id_project']?>.getTime() / 1000, 10);
				setTimeout('decompte(letime<?=$pf['id_project']?>,"val<?=$pf['id_project']?>")', 500);
			</script>
			<?
			}
			
			if($pf['photo_projet'] != '')
			{
				?><a class="lien" href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>"><img src="<?=$this->photos->display($pf['photo_projet'],'photos_projets','photo_projet_min')?>" alt="<?=$pf['photo_projet']?>" class="thumb"></a><?
			}
			?>
			<div class="description">
            	<?
				if($_SESSION['page_projet'] == 'projets_fo')
				{
					?><h5><a href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>"><?=$pf['title']?></a></h5><?	
				}
				else
				{
					?><h5><a href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>"><?=$pf['title']?></a></h5><?
				}
				?>
				<h6><?=$this->companies->city.($this->companies->zip!=''?', ':'').$this->companies->zip?></h6>
				<p><?=$pf['nature_project']?></p>
			</div><!-- /.description -->
		</td>
		<td>
            <a class="lien" href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>">
            	<div class="cadreEtoiles"><div class="etoile <?=$this->lNotes[$pf['risk']]?>"></div></div>
            </a>
        </td>
		<td style="white-space:nowrap;">
			<a class="lien" href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>">
				<?=number_format($pf['amount'], 0, ',', ' ')?>€
            </a>
        </td>
		<td style="white-space:nowrap;">
			<a class="lien" href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>">
				<?=($pf['period']==1000000?$this->lng['preteur-projets']['je-ne-sais-pas']:$pf['period'].' '.$this->lng['preteur-projets']['mois'])?>
            </a>
        </td>
        
        <td>
            <a class="lien" href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>">
				<?
                if($CountEnchere>0)
                {
                    ?><?=number_format($avgRate, 1, ',', ' ')?>%<?
                }
                else
                {
                    ?><?=($pf['target_rate']=='-'?$pf['target_rate']:number_format($pf['target_rate'], 1, ',', ' %'))?><?
                }
                ?>
            </a>
        </td>
		<td>
            <a class="lien" href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>">	
            	<strong id="val<?=$pf['id_project']?>"><?=$dateRest?></strong>
            </a>
        </td>
		<td>
        	<?
			//if($_SESSION['page_projet'] == 'projets_fo')
			//{
				if($this->projects_status->status >= 60)
				{
					?><a href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>" class="btn btn-info btn-small multi grise1 btn-grise"><?=$this->lng['preteur-projets']['voir-le-projet']?></a><?
				}
				else
				{
				?><a href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>" class="btn btn-info btn-small">PRÊTez</a><?
				}
			/*}
			else
			{
			?>
        
			<a href="<?=$this->lurl?>/projects/detail/<?=$pf['slug']?>" class="btn btn-info btn-small multi grise1 btn-grise"><?=$this->lng['preteur-projets']['voir-le-projet']?></a>
			
            <?
			}*/
			if(isset($_SESSION['client']))
			{
				?>
				<a class="fav-btn <?=$favori?>" id="fav<?=$pf['id_project']?>" onclick="favori(<?=$pf['id_project']?>,'fav<?=$pf['id_project']?>',<?=$this->clients->id_client?>,0);"><?=$this->lng['preteur-projets']['favori']?> <i></i></a>
				<?
			}
			?>
		</td>
	</tr>
	<?
}
?>

</table><!-- /.table -->
<div id="positionStart" style="display:none;"><?=$this->lProjetsFunding[0]['positionStart']?></div>
<div class="loadmore" style="display:none;">
    <?=$this->lng['preteur-projets']['chargement-en-cours']?>
</div>
<div class="nbProjet" style="display:none;"><?=$this->nbProjects?></div>
<div id="ordreProject" style="display:none;"><?=$this->ordreProject?></div>
<div id="where" style="display:none;"><?=$this->where?></div>
<div id="valType" style="display:none;"><?=$this->type?></div>

<script>
$('.tooltip-anchor').tooltip();
</script>


