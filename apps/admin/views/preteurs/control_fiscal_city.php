<script type="text/javascript">
    $(document).ready(function(){
        $(".tablesorter").tablesorter({headers:{7:{sorter: false}}});
<?php
    if ($this->nb_lignes != '') :
?>
        $(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});
<?php
    endif;
?>
    });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/emprunteurs" title="Emprunteurs">Prêteurs</a> -</li>
        <li>Matching villes fiscals Prêteurs</li>
    </ul>
    <h1>Liste des Prêteurs à matcher</h1>
<?php
    if (isset($this->aLenders) && count($this->aLenders) > 0) :
?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>ID</th>
                <th>CP Fiscale</th>
                <th>Ville Fiscale</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
<?php
        foreach ($this->aLenders as $i => $aLender) :
?>
                <tr<?=($i%2 == 1?'':' class="odd"')?> id="row_"<?=$aLender['id_adresse']?>>
                    <td><?=$aLender['id_client']?></td>
                    <td id="td_cp_<?=$aLender['id_adresse']?>"><?=$aLender['cp_fiscal']?></td>
                    <td id="td_city_<?=$aLender['id_adresse']?>"><?=$aLender['ville_fiscal']?></td>
                    <td><?=$aLender['nom']?></td>
                    <td><?=$aLender['prenom']?></td>
                    <td align="center">
                        <a href="#" class="edit_lender" data-adresseId="<?=$aLender['id_adresse']?>">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Modifier <?=$c['nom'].' '.$c['prenom']?>" />
                        </a>
                    </td>
                </tr>
                <tr id="edit_lenders_<?=$aLender['id_adresse']?>" style="display: none">
                    <td colspan="2">
                        <label for="cp_<?=$aLender['id_adresse']?>">Code postal :</label> <input type="text" class="input_large" name="cp" id="cp_<?=$aLender['id_adresse']?>" data-autocomplete="post_code">
                    </td>
                    <td colspan="2">
                        <label for="city_<?=$aLender['id_adresse']?>">Ville :</label> <input type="text" class="input_large" name="city" id="city_<?=$aLender['id_adresse']?>" data-autocomplete="city" >
                    </td>
                    <td><a href="#" class="save_lender btn_link" data-adresseId="<?=$aLender['id_adresse']?>">Sauvegarder</a></td>
                    <td><a href="#" class="close_edit btn_link" data-adresseId="<?=$aLender['id_adresse']?>">Fermer</a></td>
                </tr>
<?php
            endforeach;
?>
            </tbody>
        </table>
<?php
            if ($this->nb_lignes != '') :
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
<?php
            endif;
        else:
        if(isset($_POST['form_search_emprunteur'])) :
?>
            <p>Il n'y a aucun prêteur à matcher.</p>
<?php
        endif;
    endif;
?>
</div>
<?php unset($_SESSION['freeow']); ?>
<script>
    $('.edit_lender').click(function(e){
        e.preventDefault();
        var adresseId = $(this).data('adresseId');
        $('#edit_lenders_'+adresseId).show("slow");
        initAutocompleteCity($('#city_'+adresseId), $('#cp_'+adresseId));
    });

    $('.close_edit').click(function(e){
        e.preventDefault();
        var adresseId = $(this).data('adresseId');
        $('#edit_lenders_'+adresseId).hide("fast");
    });

    $('.save_lender').click(function(e){
        e.preventDefault();
        var adresseId = $(this).data('adresseId');

        var cp = $('#cp_'+adresseId).val();
        var city = $('#city_'+adresseId).val();
        $.post(
            '<?=$this->lurl?>/ajax/patchClientAdresse/' + adresseId,
            {cp_fiscal: cp, ville_fiscal: city}
        ).done(function(data){
            if (data == 'ok') {
                $('#td_cp_'+adresseId).html(cp);
                $('#td_city_'+adresseId).html(city);
                $('#edit_lenders_'+adresseId).hide("fast");
            } else {
                alert('Erreur, merci de réessayer.');
            }
        });
    });
</script>
