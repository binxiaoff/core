<style type="text/css">
	.tabs .tab{display:block;}
	.field-large {width: 422px;}
	.tab .form-choose{margin-bottom:0;}
	.form-page form .row .pass-field-holder {
    width: 460px;
}
</style>

<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main form-page account-page account-page-personal">
    <div class="shell">
        
        <div class="section-c tabs-c">
            <nav class="tabs-nav">
                <ul class="navProfile">
                    
                    <?php /*?> Gestion de vos alertes <?php */?>
                    <?php /*?><li <?=(!isset($this->params[0]) || $this->params[0] == 1?'class="active"':'')?>>
                        <a id="title_1" href="#"><?=$this->lng['profile']['titre-4']?></a>
                    </li><?php */?>
                    
                    <?php /*?> Gestion de votre sécurité <?php */?>
                    <?php /*?><li <?=(isset($this->params[0]) && $this->params[0] == 2?'class="active"':'')?> >
                        <a id="title_2" href="#"><?=$this->lng['profile']['titre-3']?></a>
                    </li><?php */?>
                    
                     <li <?=(!isset($this->params[0]) || isset($this->params[0]) && $this->params[0] == 2?'class="active"':'')?> >
                        <a id="title_2" href="#"><?=$this->lng['profile']['titre-3']?></a>
                    </li>
                    
                    <?php /*?> Informations personnelles <?php */?>
                    <li <?=(isset($this->params[0]) && $this->params[0] == 3?'class="active"':'')?> >
                        <a id="title_3" href="#"><?=$this->lng['profile']['titre-1']?></a>
                    </li>
                </ul>
            </nav>

            <div class="tabs">
				
                <?php /*?> Gestion de vos alertes <?php */?>
               <?php /*?> <div class="tab page1">
                    Gestion de vos alertes
                </div><!-- /.tab --><?php */?>
                
                 <?php /*?> Gestion de votre sécurité <?php */?>
                <div class="tab page2">
                   <?=$this->fireView('/secu_new')?>
                </div><!-- /.tab -->
				
                <?php /*?> Informations personnelles <?php */?>
                <div class="tab page3">
                	<?
					if($this->Command->Function == 'societe'){
						echo $this->fireView('/societe_perso_new');
						echo $this->fireView('/societe_bank_new');
					}
					else{
						echo $this->fireView('/particulier_perso_new');
						echo $this->fireView('/particulier_bank_new');
					}
					?>
                </div><!-- /.tab -->

            </div>

        </div><!-- /.tabs-c -->

    </div>
</div>		
<!--#include virtual="ssi-footer.shtml"  -->

<script type="text/javascript">
	
	
	$(window).load(function() {
		<?
		if(isset($this->params[0]) && $this->params[0] > 1 && $this->params[0] <= 3){
			for($i=1;$i<=3;$i++){ if($this->params[0] != $i){ ?>$(".page<?=$i?>" ).hide();<? }}
		}
		/*else{ ?> $(".page2" ).hide(); $(".page3" ).hide();<? }*/
		else{ ?> $(".page3" ).hide();<? }
		?>
	});

</script>