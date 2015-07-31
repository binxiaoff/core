<ul style="display:inline; padding-left:0px;">
	<?
    $i = 1;
    foreach($this->breadCrumb as $key => $b)
    {
        if($i < $this->nbBreadCrumb){
            ?><li style="display:inline;color:#A1A5A7;"><a style="color:#A1A5A7;" href="<?=$this->lurl.'/'.$b['slug']?>"><?=$b['title']?></a> ></li> <?
        }
        else{
            ?><li style="display:inline;color:#6B6E70"><?=$b['title']?></li> <?
        }
        $i++;
    }
    ?>
</ul>
