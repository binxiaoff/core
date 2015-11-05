<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main">
    <div class="shell">

    	<?
		if(count($this->lProjetsFunding)>0)
		{
		?>
        <div class="section-c">
            <table class="table projects-table">
            <tr>
                <th width="350">

                </th>

                <th width="90">
                    <div class="th-wrap"><i title="<?=$this->lng['projects']['info-1']?>" class="icon-clock tooltip-anchor"></i></div>
                </th>
                <th width="80">
                    <div class="th-wrap"><i title="<?=$this->lng['projects']['info-2']?>" class="icon-gauge tooltip-anchor"></i></div>
                </th>
                <th width="80">
                    <div class="th-wrap"><i title="<?=$this->lng['projects']['info-3']?>" class="icon-bank tooltip-anchor"></i></div>
                </th>
                <th width="90">
                    <div class="th-wrap"><i title="<?=$this->lng['projects']['info-4']?>" class="icon-calendar tooltip-anchor"></i></div>
                </th>
                <th width="80">
                    <div class="th-wrap"><i class="<?=$this->lng['projects']['info-5']?>" title="" data-original-title="Capacité de rembourssement"></i></div>
                </th>
                <th width="80">
                    <div class="th-wrap"><i class="<?=$this->lng['projects']['info-6']?>" title="" data-original-title="Capacité de rembourssement"></i></div>
                </th>
                <th width="150">
                    <div class="th-wrap"><i title="<?=$this->lng['projects']['info-7']?>" class="icon-arrow-next tooltip-anchor"></i></div>
                </th>
            </tr>
            <?
			foreach($this->lProjetsFunding as $pf)
			{
				$this->projects_status->getLastStatut($pf['id_project']);

				$result = $this->echeanciers->getNextRembEmprunteur($pf['id_project']);
				$montant_mensuel = $result['montant'];

				if($pf['date_fin'] != '0000-00-00 00:00:00') $date_fin = $pf['date_fin'];
				else $date_fin = $pf['date_retrait'];
				?>
                <tr>
                    <td>
						<?
                        if($pf['photo_projet'] != '')
                        {
                            ?><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $pf['photo_projet'] ?>" alt="<?=$pf['photo_projet']?>" class="thumb"><?
                        }
                        ?>

                        <div class="description">
                            <h5><a href="<?=$this->lurl?>/projects_emprunteur/detail/<?=$pf['slug']?>"><?=$pf['title']?></a></h5>
                            <h6><?=$this->companies->city.($this->companies->zip!=''?', ':'').$this->companies->zip?></h6>
                            <p><?=$pf['nature_project']?></p>
                        </div><!-- /.description -->
                    </td>
                    <td><?=$this->dates->formatDate($pf['added'],'d-m-Y');?></td>
                    <td><?=$this->companies->risk?></td>
                    <td><?=number_format($pf['amount'], 0, ',', ' ')?>€</td>
                    <td><?=$this->dates->formatDate($date_fin,'d-m-Y');?></td>
                    <?

					// rejeté
					if($this->projects_status->status == 30 || $this->projects_status->statut == 70)
					{
						?><td>Rejeté</td><?
					}
					// funding
					elseif($this->projects_status->status == 50)
					{
						?><td>En cours</td><?
					}
					// remboursement et plus
					else
					{
						?><td><?=$this->ficelle->formatNumber($montant_mensuel)?>€/mois</td><?
					}
					?>
                    <td><a href="#" class="tooltip-anchor icon-pdf" data-original-title="" title=""></a></td>
                    <td><a class="btn btn-pinky btn-small multi" href="<?=$this->lurl?>/projects_emprunteur/detail/<?=$pf['slug']?>"><?=$this->lng['projects']['voir-le-projet']?></a></td>
                </tr>
				<?
			}
			?>
        </table><!-- /.table -->
        </div>
        <?
		}
		?>
    </div>
</div>

<!--#include virtual="ssi-footer.shtml"  -->