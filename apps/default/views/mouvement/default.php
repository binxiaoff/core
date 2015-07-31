<!--#include virtual="ssi-header-login.shtml"  -->
		<div class="main">
			<div class="shell">
				
				<div class="section-c tabs-c">
					<nav class="tabs-nav">
						<ul>
							<li class="active"><a href="#"><?=$this->lng['preteur-mouvement']['titre']?></a></li>
							<li ><a href="#" id="histo_transac"><?=$this->lng['preteur-mouvement']['titre-2']?></a></li>
						</ul>
					</nav>

					<div class="tabs">

						<div class="tab detail_transac">
							<?=$this->fireView('detail_transac')?>
                            
						</div><!-- /.tab -->

						<div class="tab histo_transac">
                            <?=$this->fireView('histo_transac')?>
						</div><!-- /.tab -->
						
					</div>

				</div><!-- /.tabs-c -->
				
			</div>
		</div>

<?
if(isset($this->params[0]) && $this->params[0] == 2)
{
	?>      
	<script>
		setTimeout(function() {
			$("#histo_transac").click();
		}, 0);
	</script>
	<?
}
?>
<!--#include virtual="ssi-footer.shtml"  -->
<script>
function loadGraph(year,id)
{
	$.post(add_url + '/ajax/loadGraph', {year: year}).done(function(data) {
		
		$('#cadregraph').html(data);
		
		$("#listeYear>li").removeClass( "active" )
		$("#"+id).addClass("active");
					
	});
}
</script>