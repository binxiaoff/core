<?php
include_once 'example/contacts.main.php';
$handler = new ContactsHandler();
$handler->handle_request($_POST);




?>
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
<div class="center">
	<form id="import_form" action="" class="center" method="post">
	<?php if (!$this->current_class->ExternalAuth) {?>
		<table>
			<tr>
				<td>Email:</td>
				<td><input type="text" name="email" value="" style="width:90%" /></td>
			</tr>
			<tr>
				<td>Password:</td>
				<td><input type="password" name="pswd" value="" style="width:90%" /></td>
			</tr>
		</table>
	<?php } ?>
		<input type="hidden" name="state" value=""/>
		<input type="hidden" name="contacts_option" value="Gmail"/>
	<?php if ($this->error_returned && $this->error_message) {?>
		<span style="color:red;"><?php echo $this->error_message; ?></span><br/>
	<?php } ?>
		<button type="submit" id="btnContactsForm" value="import" />
	</form>
</div>

</div>

</body>
</html>