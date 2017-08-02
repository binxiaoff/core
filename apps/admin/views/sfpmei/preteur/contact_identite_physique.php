<tr>
    <th>Civilité</th>
    <td>
        <?php if ('Mme' === $this->clients->civilite) : ?>
            Madame
        <?php elseif ('M.' === $this->clients->civilite) : ?>
            Monsieur
        <?php else : ?>
            <em>Inconnu</em>
        <?php endif; ?>
    </td>
</tr>
<tr>
    <th>Prénom</th>
    <td><?= $this->clients->prenom ?></td>
</tr>
<tr>
    <th>Nom</th>
    <td><?= $this->clients->nom ?></td>
</tr>
<tr>
    <th>Nom d'usage</th>
    <td><?= $this->clients->nom_usage ?></td>
</tr>
<tr>
    <th>Date de naissance</th>
    <td><?= '0000-00-00' === $this->clients->naissance ? '' : \DateTime::createFromFormat('Y-m-d', $this->clients->naissance)->format('d/m/Y') ?></td>
</tr>
<tr>
    <th>Commune de naissance</th>
    <td><?= $this->clients->ville_naissance ?></td>
</tr>
<tr>
    <th>Pays de naissance</th>
    <td><?= $this->birthCountry ?></td>
</tr>
<tr>
    <th>Nationalité</th>
    <td><?= $this->birthCountry ?></td>
</tr>