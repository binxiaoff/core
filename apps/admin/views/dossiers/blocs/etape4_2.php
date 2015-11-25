<div class="tab_title" id="title_etape4_2">Etape 4.2</div>
<div class="tab_content" id="etape4_2">
    <?php /*
            <table class="tablesorter" style="text-align:center;">
                <thead>
                <th></th>
                <?php
                foreach ($this->aDisplayRatioAndAnalyse['DateBilan'] as $aDateBilans) {
                    ?>
                    <th><?= $aDateBilans['DateStart'] ?> - <?= $aDateBilans['DateEnd'] ?></th>
                    <?php
                }
                ?>
                </thead>
                <tbody>
                <?php
                    foreach($this->aDisplayRatioAndAnalyse['TypeBilan'] as $sKeyArray => $sTypeBilan) {
                        ?>
                        <tr><td colspan="<?= count($this->aDisplayRatioAndAnalyse['DateBilan']) + 1 ?>" style="text-align: left; background-color: #ffffff;"><h2><?= $sTypeBilan ?> :</h2></td></tr>
                        <?php
                        foreach ($this->aDisplayRatioAndAnalyse['Order'.$sKeyArray] as $sKeyAltares) {
                            ?>
                            <tr>
                                <td><?= $this->aDisplayRatioAndAnalyse['Trad']['Altares_' . $sKeyAltares] ?></td>
                                <?php
                                foreach ($this->aDisplayRatioAndAnalyse['DateBilan'] as $aDateBilans) {
                                    if (false === isset($fTotal[$aDateBilans['Year']])) {
                                        $fTotal[$aDateBilans['Year']] = 0;
                                    }
                                    ?>
                                    <td>
                                        <input name="<?= $aDateBilans['Year'] ?>_<?= $sKeyAltares ?>"
                                               id="<?= $aDateBilans['Year'] ?>_<?= $sKeyAltares ?>" type="text" class="input_moy"
                                               value="<?= (isset($this->aDisplayRatioAndAnalyse['Bilan'][$aDateBilans['Year']][$sKeyAltares])) ? $this->aDisplayRatioAndAnalyse['Bilan'][$aDateBilans['Year']][$sKeyAltares]['fValueLine'] : 0 ?>"
                                               />
                                    </td>
                                    <?php
                                }
                                ?>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <td>Total</td>
                            <?php
                            foreach ($this->aDisplayRatioAndAnalyse['DateBilan'] as $aDateBilans) {
                            ?>
                                <td>0</td>
                            <?php
                            }
                            ?>
                        </tr>
                        <tr><td style="background-color: #ffffff;">&nbsp;</td></tr>
                        <?php
                    }
                ?>
                </tbody>
            </table>
            */ ?>
</div>
