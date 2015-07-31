<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Contacts Importer by Svetlozar.NET (PHP)</title>
<LINK href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="content">
<div id="<?php echo $this->display_menu ? "importform" : "inviteform"; ?>" class="center">
<?= $this->include_form;?>
<?php require_once $this->include_form; ?>

</div>

</div>

</body>
</html>