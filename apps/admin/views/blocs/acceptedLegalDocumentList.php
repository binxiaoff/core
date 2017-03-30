<div class="content_cgv_accept">
    <h2>Acceptation CGV</h2>
    <?php if (count($this->legalDocument) > 0) : ?>
        <table class="tablesorter cgv_accept">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Version</th>
                    <th>URL</th>
                    <th>Date validation</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->legalDocument as $item) :
                $tree = $this->treeRepository->findOneBy(['idTree' => $item->getIdLegalDoc(), 'idLangue' => $this->language]);
                ?>
                <tr>
                    <td><?= $tree->getAdded()->format('d/m/Y') ?></td>
                    <td><?= $tree->getTitle() ?></td>
                    <td>
                        <a target="_blank" href="<?= $this->furl . '/' . $tree->getSlug() ?>"><?= $this->furl . '/' . $tree->getSlug() ?></a>
                    </td>
                    <td><?= $item->getAdded()->format('d/m/Y H:i:s') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p style="text-align:center;">Aucun CGV signé</p>
    <?php endif; ?>
</div>