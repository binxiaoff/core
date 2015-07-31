<script type="text/javascript">
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
        <li><a href="<?=$this->lurl?>/tree" title="Edition">Edition</a> -</li>
        <li><a href="<?=$this->lurl?>/menus" title="Menus">Menus</a> -</li>
        <li>Liste des &eacute;l&eacute;ments</li>
    </ul>
	<h1>Liste des &eacute;l&eacute;ments du menu <?=$this->menus->nom?></h1>
    <div class="btnDroite"><a href="<?=$this->lurl?>/menus/addElement/<?=$this->menus->id_menu?>" class="btn_link thickbox">Ajouter un &eacute;l&eacute;ment</a></div>
    <?
	if(count($this->lElements) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Target</th>
                    <th>Position</th>
                    <th>&nbsp;</th>  
                </tr>
          	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lElements as $e)
			{
			?>
            	<tr<?=($i%2 == 1?'':'class="odd"')?>>
                    <td><?=$e['nom']?></td>
                    <td><?=($e['complement']=='LX'?'Lien Externe':'Lien Interne')?></td>
                    <td><?=($e['target']==1?'_Blank':'Normal')?></td>
                    <td align="center">
                    	<?
						if(count($this->lElements) > 1)
						{
							if($e['ordre'] > 0)
							{
							?>
								<a href="<?=$this->lurl?>/menus/elements/<?=$this->menus->id_menu?>/up/<?=$e['id']?>" title="Remonter">
                                	<img src="<?=$this->surl?>/images/admin/up.png" alt="Remonter" />
                               	</a> 
							<?
							}							
							if($e['ordre'] < (count($this->lElements)-1))
							{
							?>
								<a href="<?=$this->lurl?>/menus/elements/<?=$this->menus->id_menu?>/down/<?=$e['id']?>" title="Descendre">
                                	<img src="<?=$this->surl?>/images/admin/down.png" alt="Descendre" />
                               	</a>
							<?
							}
						}
						?>
                    </td>
                    <td align="center">
                        <a href="<?=$this->lurl?>/menus/elements/<?=$this->menus->id_menu?>/status/<?=$e['id']?>/<?=$e['status']?>" title="<?=($e['status']==1?'Passer hors ligne':'Passer en ligne')?>">
                        	<img src="<?=$this->surl?>/images/admin/<?=($e['status']==1?'offline':'online')?>.png" alt="<?=($e['status']==1?'Passer hors ligne':'Passer en ligne')?>" />
                       	</a> 
                        <a href="<?=$this->lurl?>/menus/editElement/<?=$e['id']?>/<?=$this->menus->id_menu?>" class="thickbox">
                        	<img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$e['nom']?>" />
                       	</a>
                        <a href="<?=$this->lurl?>/menus/elements/<?=$this->menus->id_menu?>/delete/<?=$e['id']?>" title="Supprimer <?=$e['nom']?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?=$e['nom']?> ?')">
                        	<img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$e['nom']?>" />
                       	</a>
                	</td>
                </tr> 
            <?
				$i++;
            }
            ?>
            </tbody>
        </table>
	<?
    }
    else
    {
    ?>
        <p>Il n'y a aucun &eacute;l&eacute;ment pour le moment.</p>
    <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>