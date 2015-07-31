<h1 class="inscription">
	<?
    if($this->page_preteur == 3)
        echo $this->lng['inscription-preteur-etape-header']['mode-de-versement'];
    else
        echo $this->lng['inscription-preteur-etape-header']['titre']
    ?>
   <?php /*?> - <?=$this->lng['inscription-preteur-etape-header']['etape-'.$this->page_preteur]?><?php */?>
</h1>
        
    <div class="proccess">
    	<?
		if($this->page_preteur > 1){
			?><a href="<?=$this->lurl?>/inscription_preteur/etape1/<?=$this->clients->hash?>"><?=$this->lng['inscription-preteur-etape-header']['etape-1']?></a><?
		}
		else{
			?><span><?=$this->lng['inscription-preteur-etape-header']['etape-1']?></span><?	
		}
		?><i class="divider icon-arrow-medium-thin-next"></i><?
		if($this->page_preteur > 2){
			?><a href="<?=$this->lurl?>/inscription_preteur/etape2/<?=$this->clients->hash?>"><?=$this->lng['inscription-preteur-etape-header']['etape-2']?></a><?
		}
		else{
			?><span><?=$this->lng['inscription-preteur-etape-header']['etape-2']?></span><?	
		}
		?>
        <i class="divider icon-arrow-medium-thin-next"></i>
		<span><?=$this->lng['inscription-preteur-etape-header']['etape-3']?></span>
        <a href="<?=$this->lurl?>/inscription_preteur/contact_form/<?=$this->hash_client = $this->clients->hash;?>" class="popup-link">Contact</a>
    </div><!-- /.proccess -->