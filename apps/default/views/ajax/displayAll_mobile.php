<?php
if (count($this->lEnchere) > 0) {
    ?>
    <table class="table orders-table">
        <tr>
            <th width="125"><span id="triNum_mobile">N°<i class="icon-arrows"></i></span></th>
            <th width="180">
                <span id="triTx_mobile"><?= $this->lng['preteur-projets']['taux-dinteret'] ?> <i class="icon-arrows"></i></span>
                <small><?= $this->lng['preteur-projets']['taux-moyen'] ?> : <?= $this->ficelle->formatNumber($this->avgRate, 1) ?> %</small>
            </th>
            <th width="214">
                <span id="triAmount_mobile"><?= $this->lng['preteur-projets']['montant'] ?> <i class="icon-arrows"></i></span>
                <small><?= $this->lng['preteur-projets']['montant-moyen'] ?> : <?= $this->ficelle->formatNumber($this->avgAmount / 100) ?> €</small>
            </th>
            <th width="101"><span id="triStatuts_mobile"><?= $this->lng['preteur-projets']['statuts'] ?> <i class="icon-arrows"></i></span></th>
        </tr>
        <?
        foreach ($this->lEnchere as $key => $e) {
            if ($this->lenders_accounts->id_lender_account == $e['id_lender_account']) {
                $vous = true;
            } else {
                $vous = false;
            }

            if ($this->CountEnchere >= 12 && !isset($_POST['tri'])) {
                if ($e['ordre'] <= 5 || $e['ordre'] > $this->CountEnchere - 5) {
                    ?><tr <?= ($vous == true ? ' class="enchereVousColor"' : '') ?>>
                        <td><?= ($vous == true ? '<span class="enchereVous">' . $e['ordre'] . ' (' . $this->lng['preteur-projets']['vous'] . ')</span>' : $e['ordre']) ?></td>
                        <td><?= $this->ficelle->formatNumber($e['rate'], 1) ?> %</td>
                        <td><?= $this->ficelle->formatNumber($e['amount'] / 100, 0) ?> €</td>
                        <td class="<?= ($e['status'] == 1 ? 'green-span' : ($e['status'] == 2 ? 'red-span' : '')) ?>"><?= $this->status[$e['status']] ?></td>
                    </tr><?
                }
                if ($e['ordre'] == 6) {
                    ?><tr><td colspan="4" class="nth-table-row displayAll_mobile" style="cursor:pointer;">...</td></tr><?
                }
            } else {
                ?><tr <?= ($vous == true ? ' class="enchereVousColor"' : '') ?>>
                    <td><?= ($vous == true ? '<span class="enchereVous">' . $e['ordre'] . ' (' . $this->lng['preteur-projets']['vous'] . ')</span>' : $e['ordre']) ?></td>
                    <td><?= $this->ficelle->formatNumber($e['rate'], 1) ?> %</td>
                    <td><?= $this->ficelle->formatNumber($e['amount'] / 100, 0) ?> €</td>
                    <td class="<?= ($e['status'] == 1 ? 'green-span' : ($e['status'] == 2 ? 'red-span' : '')) ?>"><?= $this->status[$e['status']] ?></td>
                </tr><?
            }
        }
        ?>
    </table>
    <?
    if ($this->CountEnchere >= 12 && !isset($_POST['tri'])) {
        ?>
        <div class="single-project-actions">
            <a class="btn btn-large displayAll_mobile" ><?= $this->lng['preteur-projets']['voir-tout-le-carnet-dordres'] ?></a>
        </div>
        <?
    } else {
        ?><div class="displayAll_mobile"></div><?
    }
    ?>
    <script>
        $("#direction_mobile").html('<?=$this->direction?>');


        $("#triNum_mobile").click(function () {
            $("#tri_mobile").html('ordre');
            $(".displayAll_mobile").click();
        });

        $("#triTx_mobile").click(function () {
            $("#tri_mobile").html('rate');
            $(".displayAll_mobile").click();
        });

        $("#triAmount_mobile").click(function () {
            $("#tri_mobile").html('amount');
            $(".displayAll_mobile").click();
        });

        $("#triStatuts_mobile").click(function () {
            $("#tri_mobile").html('status');
            $(".displayAll_mobile").click();
        });

        $(".displayAll_mobile").click(function () {

            var tri = $("#tri_mobile").html();
            var direction = $("#direction_mobile").html();
            $.post(add_url + '/ajax/displayAll_mobile', {id: <?= $this->projects->id_project ?>, tri: tri, direction: direction}).done(function (data) {
                $('#bids_mobile').html(data)
            });
        });
    </script>
    <?
} else {
    ?><p><?= $this->lng['preteur-projets']['aucun-enchere'] ?></p><?
}