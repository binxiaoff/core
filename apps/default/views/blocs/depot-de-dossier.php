<ul style="display:inline; padding-left:0px;">
	<li style="display:inline;color:#A1A5A7;"><a style="color:#A1A5A7;" href="<?=$this->lurl?>"><?=$this->lng['depot-de-dossier-header']['accueil']?></a> ></li>
    
	<li style="display:inline;color:#6B6E70"><?=$this->lng['depot-de-dossier-header']['titre']?></li>

</ul>
<h1><?=$this->lng['depot-de-dossier-header']['titre'.($this->page==2?'-etape-2':'')]?></h1>

<?php /*?><div class="proccess">
    <?=($this->page > 1 ?'<a href="#">'.$this->lng['depot-de-dossier-header']['etape'].' 1</a>':'<span>'.$this->lng['depot-de-dossier-header']['etape-1'].'</span>')?>
    <i class="divider icon-arrow-medium-thin-next"></i>
    <?=($this->page > 2?'<a href="'.$this->lurl.'/depot_de_dossier/etape2/'.$this->clients->hash.'">'.$this->lng['depot-de-dossier-header']['etape'].' 2</a>':'<span>'.$this->lng['depot-de-dossier-header']['etape-2'].'</span>')?>
    <i class="divider icon-arrow-medium-thin-next"></i>
    <?=($this->page > 3?'<a href="'.$this->lurl.'/depot_de_dossier/etape3/'.$this->clients->hash.'">'.$this->lng['depot-de-dossier-header']['etape'].' 3</a>':'<span>'.$this->lng['depot-de-dossier-header']['etape-3'].'</span>')?>
    <i class="divider icon-arrow-medium-thin-next"></i>
    <?=($this->page > 4?'<a href="'.$this->lurl.'/depot_de_dossier/etape4/'.$this->clients->hash.'">'.$this->lng['depot-de-dossier-header']['etape'].' 4</a>':'<span>'.$this->lng['depot-de-dossier-header']['etape-4'].'</span>')?>
    <i class="divider icon-arrow-medium-thin-next"></i>
    <span><?=$this->lng['depot-de-dossier-header']['etape-5']?></span>
</div><!-- /.proccess --><?php */?>
