<?php session_start();
if(isset($_POST["captcha"]))
{
	if (isset($_SESSION["captcha"]) && $_SESSION["captcha"]==$_POST["captcha"])
	{
		$content_captcha = '<h1 style="color:#009900">CAPTCHA OK</h1>';
	}
	else
	{
		$content_captcha = '<h1 style="color:#fc0000">CAPTCHA NOT OK</h1>';
	}
} else {$content_captcha = '';}
if(isset($_POST["title"]))	{$content_title = $_POST["title"];}	else {$content_title = '';}
if(isset($_POST["content"])){$content_content = $_POST["content"];} else {$content_content = '';}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb" >
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Phoca Captcha</title>
		<script type="text/javascript">
function reloadCaptcha() {
now = new Date();
var capObj = document.getElementById('phoca-captcha');
if (capObj) {
	capObj.src = capObj.src + (capObj.src.indexOf('?') > -1 ? '&' : '?') + Math.ceil(Math.random()*(now.getTime()));
 }
}
		</script>
		<style type="text/css">
body{font-family:sans-serif;}
a img {border:0}
		</style>
	</head>
	<body>
		<form action="index.php" method="post" name="phoca-form" id="phoca-form">
			<table border="0">	
				
				<tr>
					<td><b>Image Verification: </b></td>		
					
					<td width="5" align="left"><img src="image.php" alt="Captcha Image" id="phoca-captcha" /></td>
					<td width="5" align="left"><input type="text" id="captcha" name="captcha" size="6" maxlength="6" style="border:1px solid #cccccc"/></td>
					
					<td width="5" align="left"><a href="javascript:reloadCaptcha();" title="Reload Image" ><img src="./images/icon-reload.gif" alt="Reload Image"  /></a></td>
					<td align="left"><small style="color:#fc0000;"></small></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="4"><input type="submit" name="save" value="Submit" /> &nbsp;<input type="reset" name="reset" value="Reset" /></td>
				</tr>
			</table>
		</form>
		<hr />
		<?php
		echo $content_captcha;
		?>			
	</body>
</html>
