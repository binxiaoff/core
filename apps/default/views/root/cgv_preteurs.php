<?php /*?><style type="text/css">
h2{page-break-before: always;}
</style><?php */?>
<div class="main" <?=(isset($this->params[0]) && !in_array($this->params[0],array('morale','nosign'))?'style="padding-bottom: 0px;"':'')?>>
    <div class="shell" <?=(isset($this->params[0]) && !in_array($this->params[0],array('morale','nosign'))?'style="width:692px;"':'')?>>
    
    <?
	if(isset($this->params[0]) && !in_array($this->params[0],array('morale','nosign'))){
		?><img alt='logo' src='<?=$this->surl?>/styles/default/pdf/images/logo.png'><?
	}
	?>
    
	<?=$this->content['contenu-cgu']?>
    <div style="page-break-after: always;"></div>
    <?=$this->mandat_de_recouvrement?>
	</div>
</div>
<?=(isset($this->params[0])?'</body></html>':'')?>