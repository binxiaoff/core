<style type="text/css">
.form-page .form-col { width: auto; float: none; }
	.form-page .form-col-inner { width: 410px; float: none; margin: 0 auto; margin-top:20px;}
	.form-page .form-col .printimg { display: block; margin: 0 auto 50px; }
</style>

<div class="main form-page form-page-last">
    <div class="shell">
        <div class="register-form">
            <div class="form-cols clearfix">
                <div class="form-col">
                    <div class="form-col-inner">
                        <img src="<?=$this->surl?>/styles/default/preteurs/images/logo.png" alt="print" class="printimg" />
                        
                        <p><a href="#" class="btn-print right"></a><?=$this->lng['etape3']['depuis-le-compte-bancaire-renseigne']?></p>
                        
                        <p><span><?=$this->lng['etape3']['votre-argent-apparaitra']?></span></p>
                        
                        <ul>
                            <li>
                                <span><?=$this->lng['etape3']['motif']?></span>
                        
                                <strong>
                                    <?=$this->motif?>
                        
                                    <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape3']['motif-description']?>"></i>
                                </strong>
                                <br />
                        
                                <?php /*?><em><?=$this->lng['etape3']['obligatoire-permet-laffectation-sur-votre-compte']?></em><?php */?>
                            </li>
                        
                            <li>
                                <span><?=$this->lng['etape3']['bic']?></span>
                        
                                <p><?=strtoupper($this->bic)?></p>
                            </li>
                        
                            <li>
                                <span><?=$this->lng['etape3']['iban']?></span>
                        
                                <p>
                                    <?=$this->iban?>
                                    
                                </p>
                            </li>
                        </ul>
                        
                        <strong><?=$this->lng['etape3']['compte-a-crediter']?></strong>
                        
                        <ul>
                            <li>
                                <span><?=$this->lng['etape3']['titulaire-du-compte']?></span>
                        
                                <p><?=$this->titulaire?></p>
                            </li>
                        
                            <li>
                                <span><?=$this->lng['etape3']['domiciliation']?></span>
                        
                                <p><?=$this->domiciliation?></p>
                            </li>
                        </ul>
                    </div> <!-- form-col-inner -->
                </div> <!-- form-col right -->
            </div> <!-- form-cols clearfix -->
		</div> <!-- register-form -->
	</div> <!-- shell -->
</div>
<script type="text/javascript">
$('.btn-print').on('click', function(event) {
	event.preventDefault();

	window.print();
});
</script>
</body>
</html>