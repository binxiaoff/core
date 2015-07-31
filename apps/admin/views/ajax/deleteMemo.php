<?
if(count($this->lProjects_comments) > 0)
{
?>
	<table class="tablesorter">
		<thead>
			<tr>
				<th width="120" align="center">Date ajout</th>
				<th align="center">Contenu</th>
				<th width="50" align="center">&nbsp;</th>  
			</tr>
		</thead>
		<tbody>
		<?
		$i = 1;
		foreach($this->lProjects_comments as $p)
		{

		?>
			<tr<?=($i%2 == 1?'':' class="odd"')?>>
				<td align="center"><?=$this->dates->formatDate($p['added'],'d/m/Y H:i:s')?></td>
				<td><?=nl2br($p['content'])?></td>
				<td align="center">
                	<a href="<?=$this->lurl?>/dossiers/addMemo/<?=$p['id_project']?>/<?=$p['id_project_comment']?>" class="thickbox"><img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier" /></a>
                	<img style="cursor:pointer;" onclick="deleteMemo(<?=$p['id_project_comment']?>,<?=$p['id_project']?>);" src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer" />
                </td>
			</tr>   
		<?
			$i++;
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
	?>
<?
}
?>
<script>
/* Elements Jquery */
$(document).ready(function()
{
	$(".thickbox").colorbox();
});
</script>