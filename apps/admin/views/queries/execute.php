<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter();	
		<?
		if($this->queries->paging != '')
		{
		?>
			$(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->queries->paging?>});		
		<?
		}
		?>
	});
</script>
<form method="post" id="formQuery" action="<?=$this->lurl?>/queries/excel/<?=$this->params[0]?>" target="_blank">
	<?
	foreach($this->sqlParams as $param)
	{
	?>
		<input type="hidden" name="<?='param_'.str_replace('@','',$param[0])?>" value="<?=$_POST['param_'.str_replace('@','',$param[0])]?>"/>
	<?
	}
	?>
</form>
<form method="post" id="formQueryBrute" action="<?=$this->lurl?>/queries/export/<?=$this->params[0]?>" target="_blank">
	<?
	foreach($this->sqlParams as $param)
	{
	?>
		<input type="hidden" name="<?='param_'.str_replace('@','',$param[0])?>" value="<?=$_POST['param_'.str_replace('@','',$param[0])]?>"/>
	<?
	}
	?>
</form>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/queries" title="Stats">Stats</a> -</li>
        <li><a href="<?=$this->lurl?>/queries" title="Stats">Requêtes</a> -</li>
        <li><?=$this->queries->name?></li>
    </ul>
	<h1><?=$this->queries->name?></h1>
    <div class="btnDroite">
    <a onclick="document.getElementById('formQuery').submit();return false;" class="btn_link">Exporter vers Excel</a>
    <a onclick="document.getElementById('formQueryBrute').submit();return false;" class="btn_link">Export brut</a>
    </div>
    </div>
    
	<?
	if(count($this->result) > 0)
	{
	?>
    	<table class="tablesorter">
		<?
        $i = 1;
        foreach($this->result as $res)
        {
            if($i == 1)
            {
            ?>
                <thead>
                    <tr>
                    <?
                    foreach($res as $key=>$line)
                    {
                        if(!is_numeric($key))
                        {
                        ?>
                            <th><?=$key?></th>
                        <?
                        }
                    }
                    ?>
                    </tr>
                </thead>
                <tbody>
            <?
            }
            ?>
           	<tr<?=($i%2 == 1?'':' class="odd"')?>>
				<?
                foreach($res as $key=>$line)
                {
                    if(!is_numeric($key))
                    {
                    ?>
                        <td><?=$line?></td>
                    <?
                    }
                }
                ?>
            </tr>
            <?
			$i++;
		}
		?>
            </tbody>
        </table>
        <?
		if($this->queries->paging != '')
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
                        	<option value="<?=$this->queries->paging?>" selected="selected"><?=$this->queries->paging?></option>
                       	</select>
                    </td>
                </tr>
            </table>
		<?
		}
		?>
	<?
    }
    else
    {
    ?>
        <p>Il n'y a aucun résultat pour cette requête.</p>
    <?
    }
    ?>
</div>