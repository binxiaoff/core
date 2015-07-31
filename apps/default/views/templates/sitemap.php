<div class="main">
	<div class="shell content_sitemap">
    <?=$this->fireView('../blocs/breadcrumb')?>
	<h1><?=$this->tree->title?></h1>
	<?=$this->tree->getPlanDuSite($this->language)?>
	</div>
</div>