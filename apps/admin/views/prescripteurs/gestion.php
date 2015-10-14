<script type="text/javascript">
    $(document).ready(function(){
        $(".tablesorter").tablesorter({headers:{7:{sorter: false}}});
        <?
        if($this->nb_lignes != '')
        {
        ?>
        $(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});
        <?
        }
        ?>

        $("#Reset").click(function() {
            $("#siret").val('');
            $("#nom").val('');
            $("#societe").val('');
            $("#prenom").val('');
            $("#email").val('');
            $('#status option[value="choisir"]').attr('selected', true);

        });

    });
    <?
    if(isset($_SESSION['freeow']))
    {
    ?>
    $(document).ready(function(){
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?
    }
    ?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/prescripteurs" title="Prescripteurs">Prescripteurs</a> -</li>
        <li>Gestion des prescripteurs</li>
    </ul>
    <?
    if(isset($_POST['form_search_prescripteur']))
    {
        ?><h1>Résultats de la recherche d'prescripteur <?=(count($this->aPrescripteurs)>0?'('.count($this->aPrescripteurs).')':'')?></h1><?
    }
    else
    {
        ?><h1>Liste des <?=count($this->aPrescripteurs)?> derniers prescripteurs</h1><?
    }
    ?>
    <div class="btnDroite"><a href="<?=$this->lurl?>/prescripteurs/add_client" class="btn_link thickbox">Ajouter un prescripteu</a></div>

    <style>
        table.formColor{width:697px;}
        .select{width:251px;}
    </style>
    <div style="width:697px;margin: auto;margin-bottom:20px;background-color: white;border: 1px solid #A1A5A7;border-radius: 10px 10px 10px 10px;margin: 0 auto 20px;padding:5px;">
        <form method="post" name="search_prescripteurs" id="search_prescripteurs" enctype="multipart/form-data" action="<?=$this->lurl?>/prescripteurs/gestion" target="_parent">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="nom">Nom :</label></th>
                        <td><input type="text" name="nom" id="nom" class="input_large" value="<?=$_POST['nom']?>"/></td>
                        <th><label for="prenom">Prénom :</label></th>
                        <td><input type="text" name="prenom" id="prenom" class="input_large" value="<?=$_POST['prenom']?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email :</label></th>
                        <td><input type="text" name="email" id="email" class="input_large" value="<?=$_POST['email']?>"/></td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:center;">
                            <input type="hidden" name="form_search_prescripteur" id="form_search_prescripteur" />
                            <input type="submit" value="Valider" title="Valider" name="send_prescripteur" id="send_prescripteur" class="btn" />
                            <input style="border-color: #A1A5A7;background-color:#A1A5A7; color:white;" type="button" value="Reset" title="Reset" name="Reset" id="Reset" class="btn" />
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>

    <?
    if(count($this->aPrescripteurs) > 0)
    {
        ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            <?
            $i = 1;
            foreach($this->aPrescripteurs as $c)
            {
                ?>
                <tr<?=($i%2 == 1?'':' class="odd"')?> id="prescripteur<?=$c['id_client']?>">
                    <td><?=$c['id_client']?></td>
                    <td><?=$c['nom']?></td>
                    <td><?=$c['prenom']?></td>
                    <td><?=$c['email']?></td>
                    <td align="center">
                        <a href="<?=$this->lurl?>/prescripteurs/edit/<?=$c['id_client']?>">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$c['nom'].' '.$c['prenom']?>" />
                        </a>
                        <script>
                            $("#prescripteur<?=$c['id_client']?>").click(function() {
                                $(location).attr('href','<?=$this->lurl?>/prescripteurs/edit/<?=$c['id_client']?>');
                            });
                        </script>
                    </td>
                </tr>
                <?
                $i++;
            }
            ?>
            </tbody>
        </table>
        <?
        if($this->nb_lignes != '')
        {
            ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay" />
                        <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
                        </select>
                    </td>
                </tr>
            </table>
            <?
        }
        ?>
        <?
    }
    else
    {
        ?>
        <?
        if(isset($_POST['form_search_prescripteurs']))
        {
            ?>
            <p>Il n'y a aucun prescripteurs pour cette recherche.</p>
            <?
        }
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>