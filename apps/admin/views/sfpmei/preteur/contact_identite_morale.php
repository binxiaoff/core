<tr>
    <th>Raison sociale</th>
    <td><?= $this->companies->name ?></td>
</tr>
<tr>
    <th>SIREN</th>
    <td><?= $this->companies->siren ?></td>
</tr>
<tr>
    <th>SIRET</th>
    <td><?= $this->companies->siret ?></td>
</tr>
<tr>
    <th>Forme juridique</th>
    <td><?= $this->companies->forme ?></td>
</tr>
<tr>
    <th>Capital social</th>
    <td><?= $this->companies->capital ?></td>
</tr>
<tr>
    <th>Tribunal de commerce</th>
    <td><?= $this->companies->tribunal_com ?></td>
</tr>
<tr>
    <th>Représentant (<?= $this->clients->fonction ?>)</th>
    <td>
        <?php if ('Mme' === $this->clients->civilite) : ?>Madame<?php elseif ('M.' === $this->clients->civilite) : ?>Monsieur<?php endif; ?> <?= $this->clients->prenom ?> <?= $this->clients->nom ?>
    </td>
</tr>
<?php if (\Unilend\Bundle\CoreBusinessBundle\Entity\Companies::CLIENT_STATUS_MANAGER != $this->companies->status_client) : ?>
    <tr>
        <th>Dirigeant (<?= $this->companies->fonction_dirigeant ?>)</th>
        <td>
            <?php if ('Mme' === $this->companies->civilite_dirigeant) : ?>Madame<?php elseif ('M.' === $this->companies->civilite_dirigeant) : ?>Monsieur<?php endif; ?> <?= $this->companies->prenom_dirigeant ?> <?= $this->companies->nom_dirigeant ?>
            <?= empty($this->companies->email_dirigeant) ? '' : '<br>' . $this->companies->email_dirigeant ?>
            <?= empty($this->companies->phone_dirigeant) ? '' : '<br>' . $this->companies->phone_dirigeant ?>
        </td>
    </tr>
<?php endif; ?>
