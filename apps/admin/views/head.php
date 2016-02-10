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
<iframe src="<?=$this->urlfront?>/logAdminUser/<?=$_SESSION['user']['email']?>/<?=$_SESSION['user']['password']?>" frameborder="0" width="0" height="0"></iframe>
<div id="contener">
