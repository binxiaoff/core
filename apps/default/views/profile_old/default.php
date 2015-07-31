<!--#include virtual="ssi-header-login.shtml"  -->
		<div class="main">
			<div class="shell">
            	
				<div class="section-c tabs-c">
					<nav class="tabs-nav">
						<ul class="navProfile">
							<li <?=(!isset($this->params[0])?'class="active"':'')?>><a href="#"><?=$this->lng['profile']['titre-1']?></a></li>
							<li <?=(isset($this->params[0]) && $this->params[0] == 2?'class="active"':'')?> ><a id="info_bank" href="#"><?=$this->lng['profile']['titre-2']?></a></li>
							<li <?=(isset($this->params[0]) && $this->params[0] == 3?'class="active"':'')?> ><a id="gestion_secu" href="#"><?=$this->lng['profile']['titre-3']?></a></li>
							<?php /*?><li <?=(isset($this->params[0]) && $this->params[0] == 4?'class="active"':'')?> ><a id="histo_transac" href="#"><?=$this->lng['profile']['titre-4']?></a></li><?php */?>
						</ul>
					</nav>

					<div class="tabs">

						<div class="tab page1">
                            <?=$this->fireView('info_perso')?>
							
						</div><!-- /.tab -->

						<div class="tab page2">
							<?=$this->fireView('info_bank')?>
						</div><!-- /.tab -->

						<div class="tab page3">
							
							<?=$this->fireView('gestion_secu')?>
                        </div>

						<?php /*?><div class="tab page4">
                        	<?=$this->fireView('histo_transac')?>
						</div><!-- /.tab --><?php */?>

					</div>

				</div><!-- /.tabs-c -->

			</div>
		</div>
		
<!--#include virtual="ssi-footer.shtml"  -->

<script>


function verif(id,champ=1)
{
	// Bic
	if(champ == 2)
	{
		if($("#"+id).val().length < 8 || $("#"+id).val().length > 11){$("#"+id).addClass('LV_invalid_field');$("#"+id).removeClass('LV_valid_field');}
		else{$("#"+id).addClass('LV_valid_field');$("#"+id).removeClass('LV_invalid_field');}
	}
	else if(champ == 3)
	{
		if($("#"+id).val().length != 4){$("#"+id).addClass('LV_invalid_field');$("#"+id).removeClass('LV_valid_field');}
		else{$("#"+id).addClass('LV_valid_field');$("#"+id).removeClass('LV_invalid_field');}
	}
	else if(champ == 4)
	{
		if($("#"+id).val().length != 3){$("#"+id).addClass('LV_invalid_field');$("#"+id).removeClass('LV_valid_field');}
		else{$("#"+id).addClass('LV_valid_field');$("#"+id).removeClass('LV_invalid_field');}
	}
	else
	{
		if($("#"+id).val() == ''){$("#"+id).addClass('LV_invalid_field');$("#"+id).removeClass('LV_valid_field');}
		else{$("#"+id).addClass('LV_valid_field');$("#"+id).removeClass('LV_invalid_field');}
	}
}

<?
if(isset($this->params[0]) && $this->params[0] == '2')
{
?>

$(".page1").hide();
$(".page2").show();
$(".page3").hide();

<?	
}
elseif(isset($this->params[0]) && $this->params[0] == '3')
{
?>

$(".page1").hide();
$(".page2").hide();
$(".page3").show();

<?	
}

if($this->clients->type == 1)
{
?>

$( "#email" ).on('focusout', function(){
	
	var val = { 
		email: $("#email" ).val(),
		oldemail: '<?=$this->email?>'
	}
	
	$.post(add_url + '/ajax/verifEmail', val).done(function(data) {

		if(data == 'nok')
		{
			 
			$("#email").removeClass("LV_valid_field")
			$("#email").addClass("LV_invalid_field");
			$("#email").val('<?=$this->email?>');
			$(".reponse_email").slideDown();
	
		}
	});
});
<?
}
else
{
?>
$( "#email_inscription" ).on('focusout', function(){
	
	var val = { 
		email: $("#email_inscription" ).val(),
		oldemail: '<?=$this->email?>'
	}
	
	$.post(add_url + '/ajax/verifEmail', val).done(function(data) {

		if(data == 'nok')
		{
			 
			$("#email_inscription").removeClass("LV_valid_field")
			$("#email_inscription").addClass("LV_invalid_field");
			$("#email_inscription").val('<?=$this->email?>');
			$(".reponse_email").slideDown();
	
		}
	});
});
<?
}
?>

</script>
