<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20180808122950TECH517 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE pays TO pays_20180808');

        $this->addSql('RENAME TABLE pays_v2 TO pays');

        $this->addSql('
          ALTER TABLE client_address
            DROP KEY idx_client_address_pays_v2_id_country,
            DROP FOREIGN KEY fk_client_address_pays_v2_id_country,
            ADD KEY idx_client_address_pays_id_country (id_country),
            ADD CONSTRAINT fk_client_address_pays_id_country FOREIGN KEY (id_country) REFERENCES pays (id_pays) ON UPDATE CASCADE'
        );

        $this->addSql('
          ALTER TABLE company_address
            DROP KEY idx_company_address_pays_v2_id_country,
            DROP FOREIGN KEY fk_company_address_pays_v2_id_country,
            ADD KEY idx_company_address_pays_id_country (id_country),
            ADD CONSTRAINT fk_company_address_pays_id_country FOREIGN KEY (id_country) REFERENCES pays (id_pays) ON UPDATE CASCADE'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT
  c.id_client,
  (
    SELECT
      CASE id_user
      WHEN -1 THEN \'Oui\'
      ELSE \'Non\'
      END
    FROM clients_status_history csh_first_validation
    WHERE csh_first_validation.id_client = c.id_client AND csh_first_validation.id_status = 60
    ORDER BY csh_first_validation.added ASC, csh_first_validation.id ASC
    LIMIT 1
  ) AS validation_auto,
  c.ville_naissance AS CommuneNaissance,
  IF(
    co.id_client_owner IS NULL, (
      CASE c.funds_origin
      WHEN 1 THEN \'Revenus de mon travail / retraite\'
      WHEN 2 THEN \'Produit de la vente d’un bien immobilier\'
      WHEN 3 THEN \'Produit de la cession de mon entreprise / de mon fonds de commerce\'
      WHEN 4 THEN \'Epargne déjà constituée\'
      WHEN 5 THEN \'Héritage / donation\'
      ELSE c.funds_origin_detail
      END
    ),
    CASE c.funds_origin
      WHEN 1 THEN \'Trésorerie existante\'
      WHEN 2 THEN \'Résultat d\'\'exploitation\'
      WHEN 3 THEN \'Résultat exceptionnel (dont vente d’actifs)\'
      WHEN 4 THEN \'Augmentation de capital ou autre injection de liquidité\'
      ELSE c.funds_origin_detail
    END
  ) AS OrgineFonds,
  c.source,
  c.source2,
  (
    SELECT csh_first_validation.added
    FROM clients_status_history csh_first_validation
    WHERE csh_first_validation.id_client = c.id_client AND csh_first_validation.id_status = 60
    ORDER BY csh_first_validation.added ASC, csh_first_validation.id ASC
    LIMIT 1
  ) AS date_premiere_validation,
  stat.label AS StatusClient,
  IF(co.id_client_owner IS NULL, \'Physique\', \'Morale\') AS Type,
  IF(co.id_client_owner IS NOT NULL, co.name, \'\') AS \'Raison Sociale\',
  CASE c.civilite
  WHEN \'M.\' THEN \'Masculin\'
  ELSE \'Feminin\'
  END AS Sexe,
  c.nom AS Nom,
  c.nom_usage AS NomUsage,
  c.prenom AS Prenom,
  c.email AS Email,
  IF(co.id_client_owner IS NULL, IFNULL(ca_fiscal.address, \'\'), IFNULL(coad_fiscal.address, \'\')) AS \'adresse fiscal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_fiscal.zip, \'\'), IFNULL(coad_fiscal.zip, \'\')) AS \'CP fiscal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_fiscal.city, \'\'), IFNULL(coad_fiscal.city, \'\')) AS \'Ville fiscal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_postal.address, \'\'), IFNULL(coad_postal.address, \'\')) AS \'adresse postal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_postal.zip, \'\'), IFNULL(coad_postal.zip, \'\')) AS \'CP postal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_postal.city, \'\'), IFNULL(coad_postal.city, \'\')) AS \'Ville postal valide\',
  (
    SELECT p.iso
    FROM lenders_imposition_history lih
      JOIN pays p ON p.id_pays = lih.id_pays
    WHERE lih.id_lender = w.id
    ORDER BY lih.added DESC
    LIMIT 1
  ) AS iso_pays_fiscal,
  (
    SELECT COUNT(DISTINCT p.id_company)
    FROM projects p
      INNER JOIN loans l ON p.id_project = l.id_project
    WHERE p.status >= 80 AND l.id_lender = w.id
  ) AS \'Number of companies\',
  (
    SELECT COUNT(DISTINCT l.id_project)
    FROM loans l
      INNER JOIN projects p ON p.id_project = l.id_project
    WHERE p.status >= 100 AND l.id_lender = w.id
  ) AS \'Number of projects with problems\',
  IF(obd.id_offre_bienvenue_detail IS NOT NULL, \'Oui\', \'Non\') AS \'OffreBienvenue\',
  CASE
  WHEN cs.id_type = 1 AND cs.value = 1 AND a.status != 2 AND AVG(a.rate_min) <> a.rate_min THEN \'activé (avancé)\'
  WHEN cs.id_type = 1 AND cs.value = 1 AND a.status = 0 THEN \'activé (avancé)\'
  WHEN cs.id_type = 1 AND cs.value = 1 THEN \'activé (simple)\'
  ELSE \'non activé\'
  END AS \'Autolend\',
  IFNULL((SELECT MIN(cha.added) FROM clients_history_actions cha WHERE cha.nom_form = \'autobid_on_off\' AND cha.id_client = c.id_client), \'\') AS \'1ère activation autolend\'
FROM clients c
  INNER JOIN wallet w FORCE INDEX (idx_id_client) ON w.id_client = c.id_client AND w.id_type = 1
  INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
  LEFT JOIN clients_status stat ON csh.id_status = stat.id
  LEFT JOIN client_address ca_fiscal ON c.id_address = ca_fiscal.id
  LEFT JOIN client_address ca_postal ON c.id_postal_address = ca_postal.id
  LEFT JOIN companies co ON c.id_client = co.id_client_owner
  LEFT JOIN company_address coad_fiscal ON co.id_address = coad_fiscal.id
  LEFT JOIN company_address coad_postal ON co.id_postal_address = coad_postal.id
  LEFT JOIN client_settings cs ON c.id_client = cs.id_client AND cs.id_type = 1 AND cs.value = 1
  LEFT JOIN autobid a ON w.id = a.id_lender
  LEFT JOIN offres_bienvenues_details obd ON c.id_client = obd.id_client AND obd.id_offre_bienvenue = 1
WHERE csh.id_status IN (5, 10, 20, 30, 40, 50, 60, 65)
  AND EXISTS(
      SELECT id_status
      FROM clients_status_history
      WHERE id_client = c.id_client AND id_status = 60
  )
GROUP BY c.id_client"
          WHERE id_query = 7'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT
  c.id_client,
  CASE wt.label
    WHEN \'lender\' THEN \'Prêteur\'
    WHEN \'borrower\' THEN \'Emprunteur\'
  END AS statut,
  CASE c.type
  WHEN 1 THEN \'Physique\'
  WHEN 2 THEN \'Morale\'
  WHEN 3 THEN \'Physique\'
  ELSE \'Morale\'
  END AS type,
  c.prenom,
  c.nom,
  c.nom_usage,
  co.name,
  c.naissance,
  pays.fr AS pays_naissance,
  (
    SELECT id_legal_doc
    FROM acceptations_legal_docs
    WHERE id_client = c.id_client
    ORDER BY added DESC
    LIMIT 1
  ) AS id_page_CGV,
  IF (
      wt.label = \'lender\', (
        SELECT cshs1.added
        FROM clients_status_history cshs1
        WHERE cshs1.id_client = c.id_client AND cshs1.id_status = 60
        ORDER BY cshs1.added ASC
        LIMIT 1
      ),
      (
        SELECT psh1.added
        FROM projects_status_history psh1
          LEFT JOIN projects p1 ON psh1.id_project = p1.id_project
        WHERE co.id_company = p1.id_company AND psh1.id_project_status = 3
        ORDER BY psh1.added DESC
        LIMIT 1
      )
  ) AS date_validation
FROM clients c
  LEFT JOIN companies co ON c.id_client = co.id_client_owner
  LEFT JOIN acceptations_legal_docs acc ON c.id_client = acc.id_client
  LEFT JOIN pays ON c.id_pays_naissance = pays.id_pays
  LEFT JOIN projects p ON co.id_company = p.id_company
  INNER JOIN wallet w FORCE INDEX (idx_id_client) ON c.id_client = w.id_client
  INNER JOIN wallet_type wt ON w.id_type = wt.id
WHERE
  wt.label = \'lender\'
  AND (
    SELECT COUNT(*)
    FROM clients_status_history csh
    WHERE csh.id_client = c.id_client AND csh.id_status = 60
    ORDER BY csh.added DESC
    LIMIT 1
  ) > 0
  OR (
    SELECT COUNT(p.id_project)
    FROM projects p
    WHERE (
        SELECT ps.status
        FROM projects_status ps
          LEFT JOIN projects_status_history psh ON ps.id_project_status = psh.id_project_status
        WHERE psh.id_project = p.id_project
        ORDER BY psh.added DESC
        LIMIT 1
      ) >= 37
      AND co.id_company = p.id_company
    GROUP BY p.id_company
  ) > 0
GROUP BY c.id_client"
          WHERE id_query = 20'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT
  c.id_client,
  ca_postal.zip AS \'Code Postal correspondance\',
  ca_postal.city AS \'Ville correspondance\',
  postal_country.fr AS \'Pays correspondance\',
  ca_fiscal.zip AS \'Code Postal fiscal\',
  ca_fiscal.city AS \'Ville fiscal\',
  fiscal_country.fr AS \'Pays fiscal\',
  ca_postal.zip = ca_fiscal.zip AS \'Code postaux identiques\',
  ca_postal.city = ca_fiscal.city AS \'Villes identiques\',
  ca_postal.id_country = ca_fiscal.id_country AS \'Pays identiques\',
  DATE(c.added) AS \'Date d\'\'inscription client\',
  DATE(ca_fiscal.date_pending) AS \'Date de modification adresse fiscale\',
  DATE(ca_postal.date_pending) AS \'Date de modification adresse postale\'
FROM clients c
  INNER JOIN wallet w ON c.id_client = w.id_client
  INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = \'lender\'
  INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
  LEFT JOIN client_address ca_fiscal ON c.id_address = ca_fiscal.id
  LEFT JOIN client_address ca_postal ON c.id_postal_address = ca_postal.id
  LEFT JOIN pays fiscal_country ON ca_fiscal.id_country = fiscal_country.id_pays
  LEFT JOIN pays postal_country ON ca_postal.id_country = postal_country.id_pays
WHERE c.id_postal_address IS NOT NULL AND c.id_postal_address != 0
  AND csh.id_status = 60
  AND (
    ca_fiscal.zip != ca_postal.zip
    OR ca_fiscal.city != ca_postal.city
    OR ca_fiscal.id_country != ca_postal.id_country
  )"
          WHERE id_query = 74'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT DISTINCT
  lih_n.id_lender,
  lih_n.added \'Date de modification\',
  (
    SELECT fr
    FROM pays
    WHERE id_pays = (
      SELECT lih_o_1.id_pays
      FROM lenders_imposition_history lih_o_1
      WHERE lih_n.id_lender = lih_o_1.id_lender AND lih_n.added > lih_o_1.added
      ORDER BY lih_o_1.added DESC
      LIMIT 1
    )
  ) AS \'Ancien pays\',
  (
    SELECT fr
    FROM pays
    WHERE id_pays = lih_n.id_pays
  ) AS \'Nouveau pays\'
FROM lenders_imposition_history lih_n
WHERE lih_n.id_pays <> (
    SELECT lih_o_1.id_pays
    FROM lenders_imposition_history lih_o_1
    WHERE lih_n.id_lender = lih_o_1.id_lender AND lih_n.added > lih_o_1.added
    ORDER BY lih_o_1.added DESC
    LIMIT 1
  )
  AND DATE(lih_n.added) BETWEEN @begin_datedate@ AND @end_date@
ORDER BY lih_n.added DESC"
          WHERE id_query = 93'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT
  CASE c.type
  WHEN 1 THEN CONCAT(\'UNI\', c.id_client)
  WHEN 3 THEN CONCAT(\'UNI\', c.id_client)
  WHEN 2 THEN CONCAT(\'UNI\', c.id_client, \'BE1P\')
  WHEN 4 THEN CONCAT(\'UNI\', c.id_client, \'BE1P\')
  ELSE \'N/A\'
  END AS ID,
  \'Preteur\' AS Statut,
  c.prenom AS Prenom,
  c.nom AS Nom,
  c.nom_usage AS \'Nom usage\',
  IF(c.naissance = \'0000-00-00 00:00:00\', \'\', c.naissance) AS \'Date naissance\',
  IFNULL(pays.fr, \'\') AS \'Pays naissance\',
  (
    SELECT id_legal_doc
    FROM acceptations_legal_docs
    WHERE id_client = c.id_client
    ORDER BY added DESC
    LIMIT 1
  ) AS CGV,
  (
    SELECT cshs1.added
    FROM clients_status_history cshs1
    WHERE cshs1.id_client = c.id_client AND cshs1.id_status = 60
    ORDER BY cshs1.added ASC
    LIMIT 1
  ) AS \'Date validation\'
FROM clients c
  INNER JOIN wallet w FORCE INDEX (idx_id_client) ON c.id_client = w.id_client
  INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = \'lender\'
  INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
  LEFT JOIN acceptations_legal_docs acc ON c.id_client = acc.id_client
  LEFT JOIN pays ON c.id_pays_naissance = pays.id_pays
WHERE csh.id_status IN (5, 10, 20, 30, 40, 50, 60, 65)
  AND (
    SELECT COUNT(*)
    FROM clients_status_history csh
    WHERE csh.id_client = c.id_client AND csh.id_status = 60
    ORDER BY csh.added DESC
    LIMIT 1
  ) > 0
GROUP BY c.id_client

UNION ALL

SELECT
  CONCAT(\'UNI\', p.id_project, \'BE1E\') AS ID,
  \'Emprunteur\' AS Statut,
  c.prenom AS Prenom,
  c.nom AS Nom,
  c.nom_usage AS \'Nom usage\',
  IF(c.naissance = \'0000-00-00 00:00:00\', \'\', c.naissance) AS \'Date naissance\',
  IFNULL(pays.fr, \'\') AS \'Pays naissance\',
  IFNULL(IFNULL(
     (
       SELECT id_legal_doc
       FROM acceptations_legal_docs
       WHERE id_client = c.id_client
       ORDER BY added DESC
       LIMIT 1
     ), cgv.id_tree), \'\'
  ) AS CGV,
  (
    SELECT psh1.added
    FROM projects_status_history psh1
      INNER JOIN projects_status ps1 ON psh1.id_project_status = ps1.id_project_status
      INNER JOIN projects p1 ON psh1.id_project = p1.id_project
    WHERE co.id_company = p1.id_company AND ps1.label LIKE \'Remboursement\'
    ORDER BY psh1.added ASC
    LIMIT 1
  ) AS \'Date validation\'
FROM projects p
  INNER JOIN projects_status_history psh ON p.id_project = psh.id_project
  INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.label LIKE \'Remboursement\' -- Passage en remboursement = fonds débloqués
  INNER JOIN companies co ON p.id_company = co.id_company
  INNER JOIN clients c ON co.id_client_owner = c.id_client
  LEFT JOIN acceptations_legal_docs acc ON c.id_client = acc.id_client
  LEFT JOIN project_cgv cgv ON p.id_project = cgv.id_project AND cgv.status = 1
  LEFT JOIN pays ON c.id_pays_naissance = pays.id_pays
GROUP BY p.id_project"
          WHERE id_query = 117'
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE pays TO pays_v2');

        $this->addSql('RENAME TABLE pays_20180808 TO pays');

        $this->addSql('
          ALTER TABLE client_address
            DROP KEY idx_client_address_pays_id_country,
            DROP FOREIGN KEY fk_client_address_pays_id_country,
            ADD KEY idx_client_address_pays_v2_id_country (id_country),
            ADD CONSTRAINT fk_client_address_pays_v2_id_country FOREIGN KEY (id_country) REFERENCES pays_v2 (id_pays) ON UPDATE CASCADE'
        );

        $this->addSql('
          ALTER TABLE company_address
            DROP KEY idx_company_address_pays_id_country,
            DROP FOREIGN KEY fk_company_address_pays_id_country,
            ADD KEY idx_company_address_pays_v2_id_country (id_country),
            ADD CONSTRAINT fk_company_address_pays_v2_id_country FOREIGN KEY (id_country) REFERENCES pays_v2 (id_pays) ON UPDATE CASCADE'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT
  c.id_client,
  (
    SELECT
      CASE id_user
      WHEN -1 THEN \'Oui\'
      ELSE \'Non\'
      END
    FROM clients_status_history csh_first_validation
    WHERE csh_first_validation.id_client = c.id_client AND csh_first_validation.id_status = 60
    ORDER BY csh_first_validation.added ASC, csh_first_validation.id ASC
    LIMIT 1
  ) AS validation_auto,
  c.ville_naissance AS CommuneNaissance,
  IF(
      co.id_client_owner IS NULL, (
        CASE c.funds_origin
        WHEN 1 THEN \'Revenus de mon travail / retraite\'
        WHEN 2 THEN \'Produit de la vente d’un bien immobilier\'
        WHEN 3 THEN \'Produit de la cession de mon entreprise / de mon fonds de commerce\'
        WHEN 4 THEN \'Epargne déjà constituée\'
        WHEN 5 THEN \'Héritage / donation\'
        ELSE c.funds_origin_detail
        END
      ),
      CASE c.funds_origin
      WHEN 1 THEN \'Trésorerie existante\'
      WHEN 2 THEN \'Résultat d\'\'exploitation\'
      WHEN 3 THEN \'Résultat exceptionnel (dont vente d’actifs)\'
      WHEN 4 THEN \'Augmentation de capital ou autre injection de liquidité\'
      ELSE c.funds_origin_detail
      END
  ) AS OrgineFonds,
  c.source,
  c.source2,
  (
    SELECT csh_first_validation.added
    FROM clients_status_history csh_first_validation
    WHERE csh_first_validation.id_client = c.id_client AND csh_first_validation.id_status = 60
    ORDER BY csh_first_validation.added ASC, csh_first_validation.id ASC
    LIMIT 1
  ) AS date_premiere_validation,
  stat.label AS StatusClient,
  IF(co.id_client_owner IS NULL, \'Physique\', \'Morale\') AS Type,
  IF(co.id_client_owner IS NOT NULL, co.name, \'\') AS \'Raison Sociale\',
  CASE c.civilite
  WHEN \'M.\' THEN \'Masculin\'
  ELSE \'Feminin\'
  END AS Sexe,
  c.nom AS Nom,
  c.nom_usage AS NomUsage,
  c.prenom AS Prenom,
  c.email AS Email,
  IF(co.id_client_owner IS NULL, IFNULL(ca_fiscal.address, \'\'), IFNULL(coad_fiscal.address, \'\')) AS \'adresse fiscal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_fiscal.zip, \'\'), IFNULL(coad_fiscal.zip, \'\')) AS \'CP fiscal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_fiscal.city, \'\'), IFNULL(coad_fiscal.city, \'\')) AS \'Ville fiscal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_postal.address, \'\'), IFNULL(coad_postal.address, \'\')) AS \'adresse postal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_postal.zip, \'\'), IFNULL(coad_postal.zip, \'\')) AS \'CP postal valide\',
  IF(co.id_client_owner IS NULL, IFNULL(ca_postal.city, \'\'), IFNULL(coad_postal.city, \'\')) AS \'Ville postal valide\',
  (
    SELECT p.iso
    FROM lenders_imposition_history lih
      JOIN pays_v2 p ON p.id_pays = lih.id_pays
    WHERE lih.id_lender = w.id
    ORDER BY lih.added DESC
    LIMIT 1
  ) AS iso_pays_fiscal,
  (
    SELECT COUNT(DISTINCT p.id_company)
    FROM projects p
      INNER JOIN loans l ON p.id_project = l.id_project
    WHERE p.status >= 80 AND l.id_lender = w.id
  ) AS \'Number of companies\',
  (
    SELECT COUNT(DISTINCT l.id_project)
    FROM loans l
      INNER JOIN projects p ON p.id_project = l.id_project
    WHERE p.status >= 100 AND l.id_lender = w.id
  ) AS \'Number of projects with problems\',
  IF(obd.id_offre_bienvenue_detail IS NOT NULL, \'Oui\', \'Non\') AS \'OffreBienvenue\',
  CASE
  WHEN cs.id_type = 1 AND cs.value = 1 AND a.status != 2 AND AVG(a.rate_min) <> a.rate_min THEN \'activé (avancé)\'
  WHEN cs.id_type = 1 AND cs.value = 1 AND a.status = 0 THEN \'activé (avancé)\'
  WHEN cs.id_type = 1 AND cs.value = 1 THEN \'activé (simple)\'
  ELSE \'non activé\'
  END AS \'Autolend\',
  IFNULL((SELECT MIN(cha.added) FROM clients_history_actions cha WHERE cha.nom_form = \'autobid_on_off\' AND cha.id_client = c.id_client), \'\') AS \'1ère activation autolend\'
FROM clients c
  INNER JOIN wallet w FORCE INDEX (idx_id_client) ON w.id_client = c.id_client AND w.id_type = 1
  INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
  LEFT JOIN clients_status stat ON csh.id_status = stat.id
  LEFT JOIN client_address ca_fiscal ON c.id_address = ca_fiscal.id
  LEFT JOIN client_address ca_postal ON c.id_postal_address = ca_postal.id
  LEFT JOIN companies co ON c.id_client = co.id_client_owner
  LEFT JOIN company_address coad_fiscal ON co.id_address = coad_fiscal.id
  LEFT JOIN company_address coad_postal ON co.id_postal_address = coad_postal.id
  LEFT JOIN client_settings cs ON c.id_client = cs.id_client AND cs.id_type = 1 AND cs.value = 1
  LEFT JOIN autobid a ON w.id = a.id_lender
  LEFT JOIN offres_bienvenues_details obd ON c.id_client = obd.id_client AND obd.id_offre_bienvenue = 1
WHERE csh.id_status IN (5, 10, 20, 30, 40, 50, 60, 65)
      AND EXISTS(
          SELECT id_status
          FROM clients_status_history
          WHERE id_client = c.id_client AND id_status = 60
      )
GROUP BY c.id_client"
          WHERE id_query = 7'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT
  c.id_client,
  CASE wt.label
  WHEN \'lender\' THEN \'Prêteur\'
  WHEN \'borrower\' THEN \'Emprunteur\'
  END AS statut,
  CASE c.type
  WHEN 1 THEN \'Physique\'
  WHEN 2 THEN \'Morale\'
  WHEN 3 THEN \'Physique\'
  ELSE \'Morale\'
  END AS type,
  c.prenom,
  c.nom,
  c.nom_usage,
  co.name,
  c.naissance,
  pays_v2.fr AS pays_naissance,
  (
    SELECT id_legal_doc
    FROM acceptations_legal_docs
    WHERE id_client = c.id_client
    ORDER BY added DESC
    LIMIT 1
  ) AS id_page_CGV,
  IF (
      wt.label = \'lender\', (
        SELECT cshs1.added
        FROM clients_status_history cshs1
        WHERE cshs1.id_client = c.id_client AND cshs1.id_status = 60
        ORDER BY cshs1.added ASC
        LIMIT 1
      ),
      (
        SELECT psh1.added
        FROM projects_status_history psh1
          LEFT JOIN projects p1 ON psh1.id_project = p1.id_project
        WHERE co.id_company = p1.id_company AND psh1.id_project_status = 3
        ORDER BY psh1.added DESC
        LIMIT 1
      )
  ) AS date_validation
FROM clients c
  LEFT JOIN companies co ON c.id_client = co.id_client_owner
  LEFT JOIN acceptations_legal_docs acc ON c.id_client = acc.id_client
  LEFT JOIN pays_v2 ON c.id_pays_naissance = pays_v2.id_pays
  LEFT JOIN projects p ON co.id_company = p.id_company
  INNER JOIN wallet w FORCE INDEX (idx_id_client) ON c.id_client = w.id_client
  INNER JOIN wallet_type wt ON w.id_type = wt.id
WHERE
  wt.label = \'lender\' AND (
                            SELECT COUNT(*)
                            FROM clients_status_history csh
                            WHERE csh.id_client = c.id_client AND csh.id_status = 60
                            ORDER BY csh.added DESC
                            LIMIT 1
                          ) > 0
  OR (
       SELECT COUNT(p.id_project)
       FROM projects p
       WHERE (
               SELECT ps.status
               FROM projects_status ps
                 LEFT JOIN projects_status_history psh ON ps.id_project_status = psh.id_project_status
               WHERE psh.id_project = p.id_project
               ORDER BY psh.added DESC
               LIMIT 1
             ) >= 37
             AND co.id_company = p.id_company
       GROUP BY p.id_company
     ) > 0
GROUP BY c.id_client"
          WHERE id_query = 20'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT
  c.id_client,
  ca_postal.zip AS \'Code Postal correspondance\',
  ca_postal.city AS \'Ville correspondance\',
  postal_country.fr AS \'Pays correspondance\',
  ca_fiscal.zip AS \'Code Postal fiscal\',
  ca_fiscal.city AS \'Ville fiscal\',
  fiscal_country.fr AS \'Pays fiscal\',
  ca_postal.zip = ca_fiscal.zip AS \'Code postaux identiques\',
  ca_postal.city = ca_fiscal.city AS \'Villes identiques\',
  ca_postal.id_country = ca_fiscal.id_country AS \'Pays identiques\',
  DATE(c.added) AS \'Date d\'\'inscription client\',
  DATE(ca_fiscal.date_pending) AS \'Date de modification adresse fiscale\',
  DATE(ca_postal.date_pending) AS \'Date de modification adresse postale\'
FROM clients c
  INNER JOIN wallet w ON c.id_client = w.id_client
  INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = \'lender\'
  INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
  LEFT JOIN client_address ca_fiscal ON c.id_address = ca_fiscal.id
  LEFT JOIN client_address ca_postal ON c.id_postal_address = ca_postal.id
  LEFT JOIN pays_v2 fiscal_country ON ca_fiscal.id_country = fiscal_country.id_pays
  LEFT JOIN pays_v2 postal_country ON ca_postal.id_country = postal_country.id_pays
WHERE c.id_postal_address IS NOT NULL AND c.id_postal_address != 0
  AND csh.id_status = 60
  AND (ca_fiscal.zip != ca_postal.zip
        OR ca_fiscal.city != ca_postal.city
        OR ca_fiscal.id_country != ca_postal.id_country)"
          WHERE id_query = 74'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT DISTINCT
  lih_n.id_lender,
  lih_n.added \'Date de modification\',
  (
    SELECT fr
    FROM pays_v2
    WHERE id_pays =
          (SELECT lih_o_1.id_pays
           FROM lenders_imposition_history lih_o_1
           WHERE lih_n.id_lender = lih_o_1.id_lender AND lih_n.added > lih_o_1.added
           ORDER BY lih_o_1.added DESC
           LIMIT 1)
  ) AS        \'Ancien pays\',
  (
    SELECT fr
    FROM pays_v2
    WHERE id_pays = lih_n.id_pays
  ) AS        \'Nouveau pays\'

FROM lenders_imposition_history lih_n
WHERE lih_n.id_pays <> (SELECT lih_o_1.id_pays
                        FROM lenders_imposition_history lih_o_1
                        WHERE lih_n.id_lender = lih_o_1.id_lender AND lih_n.added > lih_o_1.added
                        ORDER BY lih_o_1.added DESC
                        LIMIT 1) AND DATE(lih_n.added) BETWEEN @begin_datedate@ AND @end_date@
ORDER BY lih_n.added DESC"
          WHERE id_query = 93'
        );

        $this->addSql('
          UPDATE queries
          SET `sql` = "SELECT
  CASE c.type
  WHEN 1 THEN CONCAT(\'UNI\', c.id_client)
  WHEN 3 THEN CONCAT(\'UNI\', c.id_client)
  WHEN 2 THEN CONCAT(\'UNI\', c.id_client, \'BE1P\')
  WHEN 4 THEN CONCAT(\'UNI\', c.id_client, \'BE1P\')
  ELSE \'N/A\'
  END AS ID,
  \'Preteur\' AS Statut,
  c.prenom AS Prenom,
  c.nom AS Nom,
  c.nom_usage AS \'Nom usage\',
  IF(c.naissance = \'0000-00-00 00:00:00\', \'\', c.naissance) AS \'Date naissance\',
  IFNULL(pays_v2.fr, \'\') AS \'Pays naissance\',
  (
    SELECT id_legal_doc
    FROM acceptations_legal_docs
    WHERE id_client = c.id_client
    ORDER BY added DESC
    LIMIT 1
  ) AS CGV,
  (
    SELECT cshs1.added
    FROM clients_status_history cshs1
    WHERE cshs1.id_client = c.id_client AND cshs1.id_status = 60
    ORDER BY cshs1.added ASC
    LIMIT 1
  ) AS \'Date validation\'
FROM clients c
  INNER JOIN wallet w FORCE INDEX (idx_id_client) ON c.id_client = w.id_client
  INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = \'lender\'
  INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
  LEFT JOIN acceptations_legal_docs acc ON c.id_client = acc.id_client
  LEFT JOIN pays_v2 ON c.id_pays_naissance = pays_v2.id_pays
WHERE csh.id_status IN (5, 10, 20, 30, 40, 50, 60, 65)
      AND (
            SELECT COUNT(*)
            FROM clients_status_history csh
            WHERE csh.id_client = c.id_client AND csh.id_status = 60
            ORDER BY csh.added DESC
            LIMIT 1
          ) > 0
GROUP BY c.id_client

UNION ALL

SELECT
  CONCAT(\'UNI\', p.id_project, \'BE1E\') AS ID,
  \'Emprunteur\' AS Statut,
  c.prenom AS Prenom,
  c.nom AS Nom,
  c.nom_usage AS \'Nom usage\',
  IF(c.naissance = \'0000-00-00 00:00:00\', \'\', c.naissance) AS \'Date naissance\',
  IFNULL(pays_v2.fr, \'\') AS \'Pays naissance\',
  IFNULL(IFNULL(
             (
               SELECT id_legal_doc
               FROM acceptations_legal_docs
               WHERE id_client = c.id_client
               ORDER BY added DESC
               LIMIT 1
             ), cgv.id_tree), \'\'
  ) AS CGV,
  (
    SELECT psh1.added
    FROM projects_status_history psh1
      INNER JOIN projects_status ps1 ON psh1.id_project_status = ps1.id_project_status
      INNER JOIN projects p1 ON psh1.id_project = p1.id_project
    WHERE co.id_company = p1.id_company AND ps1.label LIKE \'Remboursement\'
    ORDER BY psh1.added ASC
    LIMIT 1
  ) AS \'Date validation\'
FROM projects p
  INNER JOIN projects_status_history psh ON p.id_project = psh.id_project
  INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.label LIKE \'Remboursement\' -- Passage en remboursement = fonds débloqués
  INNER JOIN companies co ON p.id_company = co.id_company
  INNER JOIN clients c ON co.id_client_owner = c.id_client
  LEFT JOIN acceptations_legal_docs acc ON c.id_client = acc.id_client
  LEFT JOIN project_cgv cgv ON p.id_project = cgv.id_project AND cgv.status = 1
  LEFT JOIN pays_v2 ON c.id_pays_naissance = pays_v2.id_pays
GROUP BY p.id_project"
          WHERE id_query = 117'
        );
    }
}
