<?php /*?><ul style="display:inline; padding-left:0px;">
	<li style="display:inline;color:#A1A5A7;"><a style="color:#A1A5A7;" href="<?=$this->lurl?>"><?=$this->lng['inscription-preteur-etape-header']['accueil']?></a> ></li>
	<li style="display:inline;color:#6B6E70"><?=$this->lng['inscription-preteur-etape-header']['titre']?></li>

</ul><?php */?>
<h1 class="inscription"><?=$this->lng['inscription-preteur-etape-header']['titre']?></h1>
<div class="proccess">
    <?=($this->page_preteur > 1 ?'<a href="'.$this->lurl.'/inscription_preteur/etape1/'.$this->params[0].'">'.$this->lng['inscription-preteur-etape-header']['etape'].' 1</a>':'<span>'.$this->lng['inscription-preteur-etape-header']['etape-1'].'</span>')?>
    <i class="divider icon-arrow-medium-thin-next"></i>
    <?=($this->page_preteur > 2?'<a href="'.$this->lurl.'/inscription_preteur/etape2/'.$this->params[0].'">'.$this->lng['inscription-preteur-etape-header']['etape'].' 2</a>':'<span>'.$this->lng['inscription-preteur-etape-header']['etape-2'].'</span>')?>
    <i class="divider icon-arrow-medium-thin-next"></i>
    <span><?=$this->lng['inscription-preteur-etape-header']['etape-3']?></span>
</div><!-- /.proccess -->
