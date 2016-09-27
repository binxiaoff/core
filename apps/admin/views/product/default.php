<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/emprunteurs" title="Emprunteur">Emprunteur</a> -</li>
        <li>Gestion des produits</li>
    </ul>
    <h1>Gestion des produits</h1>
    <?php if(count($this->productList) > 0) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>ID Produit</th>
                <th>Nom</th>
                <th>Pouvoir PDF template</th>
                <th>Pouvoir bloc slug</th>
                <th>status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->productList as $product) : ?>
            <tr<?=($i%2 == 1?'':' class="odd"')?>>
                <?php
                    switch ($product['status']) {
                        case \product::STATUS_OFFLINE:
                            $status = 'Desactivé FO (indisponible FO mais disponible BO)';
                            break;
                        case \product::STATUS_ONLINE:
                            $status = 'Activé';
                            break;
                        case \product::STATUS_ARCHIVED:
                            $status = 'Archivé (indisponible FO et BO)';
                            break;
                    }
                ?>
                <td><?= $product['id_product'] ?></td>
                <td><?= $this->translator->trans('product_label_' . $product['label']) ?></td>
                <td><?= $product['proxy_template'] ?></td>
                <td><?= $product['proxy_block_slug'] ?></td>
                <td><?= $status ?></td>
                <td>
                    <a href="/product/edit/<?= $product['id_product'] ?>" title="Consulter">
                        <img src="<?=$this->surl?>/images/admin/modif.png" alt="Consulter" />
                    </a>
                </td>
            </tr>
                <?php $i++; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Il n'y a aucun produit pour le moment.</p>
    <?php endif; ?>
</div>
