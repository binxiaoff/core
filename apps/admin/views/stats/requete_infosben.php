<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter();
		<?
		if($this->nb_lignes != '')
		{
		?>
			$(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});
		<?
		}
		?>

	});
	<?
	if(isset($_SESSION['freeow']))
	{
	?>
		$(document).ready(function(){
			var title, message, opts, container;
			title = "<?=$_SESSION['freeow']['title']?>";
			message = "<?=$_SESSION['freeow']['message']?>";
			opts = {};
			opts.classes = ['smokey'];
			$('#freeow-tr').freeow(title, message, opts);
		});
	<?
	}
	?>

</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/settings" title="Configuration">Configuration</a> -</li>
        <li>Administrateurs</li>
    </ul>
	<h1>Requete infosben</h1>

    <div style="margin-bottom:20px; float:right;"><a href="<?=$this->lurl?>/stats/requete_infosben_csv" class="btn_link">Recuperation du CSV</a></div>
    <?php
		if(count($this->aLenders) > 0) {
	?>
	<table class="tablesorter">
		<thead>
		<tr>
			<th>Cdos</th>
			<th>Cbéné</th>
			<th>CEtabl</th>
			<th>CGuichet</th>
			<th>RéfCompte</th>
			<th>NatCompte</th>
			<th>TypCompte</th>
			<th>CDRC</th>
		</tr>
		</thead>
		<tbody>


	<?php
	$i =0;
			foreach ($this->aLenders as $aLender) {
					// Motif
					$sPrenom   = substr($this->ficelle->stripAccents(trim($aLender['prenom'])), 0, 1);
					$sNom      = $this->ficelle->stripAccents(trim($aLender['nom']));
					$motif     = mb_strtoupper($aLender['id_client'] . $sPrenom . $sNom, 'UTF-8');
					$motif     = substr($motif, 0, 10);
					?>
					<tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
						<td>1</td>
						<td><?= $motif ?></td>
						<td>14378</td>
						<td></td>
						<td><?= $aLender['id_client'] ?></td>
						<td>4</td>
						<td>6</td>
						<td>P</td>
					</tr>
					<?php
					$i++;
			}
		}
	?>
            </tbody>
        </table>
        <?
		if($this->nb_lignes != '')
		{
		?>
			<table>
                <tr>
                    <td id="pager">
                        <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay" />
                        <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                        	<option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
                       	</select>
                    </td>
                </tr>
            </table>
		<?
		}
		else
		{
		?>
    	<p>Il n'y a aucun dossier pour le moment.</p>
		<?
		}
		?>
</div>
<?php unset($_SESSION['freeow']); ?>