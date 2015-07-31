<?php
header("Content-Type: application/vnd.ms-excel");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("content-disposition: attachment;filename=".$this->bdd->generateSlug($this->queries->name).".xls");
?>
<table border="0" cellpadding="0" cellspacing="0" style="width:100%; background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;">
	<tr>
    	<td colspan="3" style="font:italic 18px Arial, Helvetica, sans-serif; text-align:left;"><?=utf8_decode($this->queries->name)?></td>
  	</tr>
    <tr>
    	<td colspan="3">&nbsp;</td>
  	</tr>
    <tr>
    	<td style="width:100px;">&nbsp;</td>
        <td style="text-align:left; height:75px;"><img src="<?=$this->surl?>/images/admin/logo_<?=$this->cms?>.png" /></td>        
        <td style="width:100px;">&nbsp;</td>
  	</tr>
    <tr>
    	<td colspan="3">&nbsp;</td>
  	</tr>
</table>
<table border="0" cellpadding="0" cellspacing="0" style="width:100%; background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;">
	<?
    $i = 0;
    foreach($this->result as $res)
    {
        if($i == 0)
        {
        ?>
            <tr>
                <td style="width:100px;">&nbsp;</td>
                <?
                $y = 1;
                foreach($res as $key=>$line)
                {
                    if($y == count($res))
                    {
                        if(!is_numeric($key))
                        {
                        ?>
                            <td style="text-align:center; border-top:0; border-bottom:0; border-left:0; border-right:0; background-color:#2F86B2; font:bold 13px Arial, Helvetica, sans-serif; color:#fff;"><?=$key?></td>
                        <?
                        }
                    }
                    else
                    {
                        if(!is_numeric($key))
                        {	
                        ?>
                            <td style="text-align:center; border-top:0; border-bottom:0; border-left:0; border-right:1px solid #fff; background-color:#2F86B2; font:bold 13px Arial, Helvetica, sans-serif; color:#fff;"><?=$key?></td>
                        <?
                        }
                    }
                    
                    $y++;
                }
                ?>
                <td style="width:100px;">&nbsp;</td>
            </tr>
     	<?
		}
		?>
        <tr>
			<td style="width:100px;">&nbsp;</td>
			<?
            $z = 1;
            foreach($res as $key=>$line)
            {
                if($z == 2)
                {
                    if(!is_numeric($key))
                    {
                    ?>
                        <td style="text-align:left; border-top:0; border-bottom:1px solid #d9d9d9; border-left:1px solid #d9d9d9; border-right:1px solid #d9d9d9;"><?=utf8_decode(strip_tags($line))?></td>
                    <?
                    }
                }
                else
                {					
                    if(!is_numeric($key))
                    {
                    ?>
                        <td style="text-align:left; border-top:0; border-bottom:1px solid #d9d9d9; border-left:0; border-right:1px solid #d9d9d9;"><?=utf8_decode(strip_tags($line))?></td>
                    <?
                    }
                }
				
                $z++;	
            }
			?>
            <td style="width:100px;">&nbsp;</td>
    	</tr>
        <?       
        $i++;
    }
    ?>
</table>