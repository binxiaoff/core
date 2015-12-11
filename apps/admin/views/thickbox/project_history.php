<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <h1>Historique des statuts du dossier</h1>
    <table class="tablesorter">
        <thead>
            <tr>
                <th rowspan="2"></th>
                <th rowspan="2">Statut</th>
                <th rowspan="2">Date</th>
                <th rowspan="2">Utilisateur</th>
                <th colspan="4">PS, RJ, LJ</th>
            </tr>
            <tr>
                <?php if (false === empty($this->aProjectHistoryDetails)): ?>
                    <th>Mandataire</th>
                    <th>Date jugement</th>
                    <th>Mail</th>
                    <th>Site</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php $iOrder = 1; ?>
        <?php foreach ($this->aHistory as $aHistory): ?>
            <tr>
                <td><?= $iOrder++; ?></td>
                <td><strong><?= $aHistory['status'] ?></strong></td>
                <td><?= date('d/m/Y à H:i', strtotime($aHistory['date'])) ?></td>
                <td><?= $aHistory['user'] ?></td>
                <?php if (false === empty($this->aProjectHistoryDetails)): ?>
                    <td><?= empty($aHistory['decision_date']) ? '' : date('d/m/Y', strtotime($aHistory['decision_date'])) ?></td>
                    <td><?= nl2br($aHistory['receiver']) ?></td>
                    <td><?= nl2br($aHistory['mail_content']) ?></td>
                    <td><?= nl2br($aHistory['site_content']) ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
