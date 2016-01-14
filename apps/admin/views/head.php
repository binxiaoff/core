<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Administration <?=$this->cms?></title>
    <link rel="shortcut icon" href="<?=$this->surl?>/images/admin/favicon.png" type="image/x-icon" />
    <script type="text/javascript">
		var add_surl = '<?=$this->surl?>';
		var add_url = '<?=$this->lurl?>';
	</script>
	<?php $this->callCss();?>
    <?php $this->callJs();?>
</head>
<body>
<script>var dataLayer = [<?= json_encode($this->aDataLayer) ?>];</script>
<!-- Google Tag Manager -->
<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-MB66VL"
				  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
			new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
			j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
			'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-MB66VL');</script>
<!-- End Google Tag Manager -->
<iframe src="<?=$this->urlfront?>/logAdminUser/<?=$_SESSION['user']['email']?>/<?=$_SESSION['user']['password']?>" frameborder="0" width="0" height="0"></iframe>
<div id="contener">