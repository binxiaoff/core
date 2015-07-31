<!--#include virtual="ssi-header-login.shtml"  -->
		<div class="main">
			<div class="shell">
				
                
					<div class="section-c tabs-c">
                        <nav class="tabs-nav">
                            <ul>
                                <?php //<li ><a href="#"><?=$this->lng['preteur-operations']['titre-2']?></a></li><?php //fermer com ?>
                                <li class="active" ><a href="#"><?=$this->lng['preteur-operations']['titre-1']?></a></li>
                                <li ><a href="#"><?=$this->lng['preteur-operations']['titre-3']?></a></li>
                                <li ><a href="#"><?=$this->lng['preteur-operations']['titre-4']?></a></li>
                                
                                
                            </ul>
                        </nav>
    
                        <div class="tabs">
                            
                           
                            
                            <div class="tab vos_operations">
                                <?=$this->fireView('vos_operations')?>   
                            </div><!-- /.tab -->
                            
                            <div class="tab vos_prets">
                                <?=$this->fireView('vos_prets')?>
                            </div><!-- /.tab -->
                            
                           <?php /*?> <div class="tab histo_transac">
                                <?=$this->fireView('histo_transac')?>
                            </div><!-- /.tab --><?php */ ?>
    
                            <div class="tab doc_fiscaux">
                                <?=$this->fireView('doc_fiscaux')?>
                            </div><!-- /.tab -->
                            
                            
                            
                        </div>
    
                    </div><!-- /.tabs-c -->
					
                
                    
				
					<?php /*?>?>
                    
                    <div class="section-c tabs-c">
                        <h2><?=$this->lng['preteur-operations']['titre-1']?></h2>
                        
                        <div class="table-filter clearfix">
                            
                                Oups, l’espace prêteurs est temporairement indisponible.<br />
                                Mais c’est pour une bonne raison : on vous prépare des nouveautés à découvrir dans quelques heures.<br />
                                Revenez nous voir après (au choix) : <br />
                                - l’arrivée au bureau à 11h <br />
                                - la réunion de 11h<br />
                                - le café de 11h<br />
                                <br /><br />
                                Merci de votre patience et à tout à l’heure :)
                            
    
                        </div>
                    </div>
                    <?php	<?php */?>
				
				
			</div>
		</div>

<?
/*if(isset($this->params[0]) && $this->params[0] == 2){
	?><script type="text/javascript"> $("#vos_prets").click(); </script><?
}
elseif(isset($this->params[0]) && $this->params[0] == 3){
	?><script type="text/javascript"> $("#histo").click(); </script><?
}*/
?>
<!--#include virtual="ssi-footer.shtml"  -->
