<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main">
    <div class="shell">
        <div class="section-c">
            <div class="emprunto-box">
                <div class="box clearfix">
                    <strong><?=$this->lng['synthese']['societe']?> : <?=$this->companies->name?></strong>
                    <strong><a href="<?=$this->lurl?>/societe_emprunteur"><?=$this->lng['synthese']['modifier']?></a></strong>
                </div>
                <div class="row clearfix">
                    <p><strong class="green-span"><i class="icon-contact-green"></i><?=$this->lng['synthese']['contact']?> </strong>: <?=$this->clients->prenom.' '.$this->clients->nom?> - <?=$this->clients->fonction?> <br /><?=$this->clients->email?> - <?=$this->clients->telephone?></p>
                </div>
                <div class="row colored clearfix">
                    <div class="col left">
                        <p><?=$this->lng['synthese']['montant-emprunte']?> : <?=$this->ficelle->formatNumber($this->sum)?> €	</p>
                        <p><?=$this->lng['synthese']['mensualite']?> : <?=$this->ficelle->formatNumber($this->montant_mensuel)?> €</p>
                    </div>
                    <div class="col right">
                        <p><?=$this->lng['synthese']['nombre-de-preteurs']?> : <?=$this->nbPeteurs?></p>
                    </div>
                </div>
                <a href="<?=$this->lurl?>/<?=($this->nbProjets>1?'projects_emprunteur':'projects_emprunteur/detail/'.$this->slug)?>" <?=($this->nbProjets>1?'style="width:260px;"':'')?> class="btn alone"><?=($this->nbProjets>1?$this->lng['synthese']['voir-tous-mes-projets']:$this->lng['synthese']['voir-le-projet'])?> <i class="icon-arrow-next"></i></a>
            </div>
        </div>
    </div>
</div>

<!--#include virtual="ssi-footer.shtml"  -->