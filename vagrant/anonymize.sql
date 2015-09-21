UPDATE clients SET
  email = CONCAT(id_client, '@devunilend.fr'),
  password = MD5(id_client),
  slug = id_client,
  prenom = CONCAT('Prenom ', id_client),
  nom = CONCAT('Nom ', id_client),
  nom_usage = CONCAT('Nom usage ', id_client),
  telephone = CONCAT(SUBSTRING('0100000000', 1, 10 - LENGTH(id_client)), id_client),
  mobile = CONCAT(SUBSTRING('0600000000', 1, 10 - LENGTH(id_client)), id_client),
  cni_passeport = IF(cni_passeport = '', '', CONCAT('cni', '-', id_client, IF(cni_passeport REGEXP '\.[a-zA-Z0-9]{3}', SUBSTRING(cni_passeport, -4), IF(cni_passeport REGEXP '\.[a-zA-Z0-9]{4}', SUBSTRING(cni_passeport, -5), 'ext'))))
WHERE email NOT LIKE '%@unilend.fr';

UPDATE clients_adresses SET
  adresse1 = CONCAT(id_client, ', Rue du ', id_client),
  adresse2 = '',
  adresse3 = '',
  cp = SUBSTRING(CONCAT(id_client, '12345'), 1, 5),
  ville = CONCAT('Ville ', id_client),
  adresse_fiscal = IF(meme_adresse_fiscal = 0, '', CONCAT(id_client, ', Allée des ', id_client)),
  cp_fiscal = IF(meme_adresse_fiscal = 0, '', SUBSTRING(CONCAT(id_client, '23456'), 1, 5)),
  ville_fiscal = IF(meme_adresse_fiscal = 0, '', CONCAT('Ville ', id_client)),
  telephone = IF(telephone = '', '', CONCAT(SUBSTRING('0100000000', 1, 10 - LENGTH(id_adresse)), id_adresse)),
  mobile = IF(mobile = '', '', CONCAT(SUBSTRING('0600000000', 1, 10 - LENGTH(id_adresse)), id_adresse))
WHERE id_client NOT IN (SELECT id_client FROM clients WHERE email LIKE '%@unilend.fr');

UPDATE lenders_accounts SET
  iban = IF(iban = '', '', CONCAT(SUBSTRING(iban, 1, 17), SUBSTRING('0000000000', 1, 10 - LENGTH(id_lender_account)), id_lender_account)),
  motif = IF(motif = '', '', CONCAT('0000', id_lender_account, 'ABC')),
  fichier_cni_passeport = IF(fichier_cni_passeport = '', '', IF(fichier_cni_passeport REGEXP '\.[a-zA-Z0-9]{3}', CONCAT('passeport-', id_lender_account, SUBSTRING(fichier_cni_passeport, -4)), IF(fichier_cni_passeport REGEXP '\.[a-zA-Z0-9]{4}', CONCAT('passeport-', id_lender_account, SUBSTRING(fichier_cni_passeport, -5)), ''))),
  fichier_justificatif_domicile = IF(fichier_justificatif_domicile = '', '', IF(fichier_justificatif_domicile REGEXP '\.[a-zA-Z0-9]{3}', CONCAT('justificatif_domicile-', id_lender_account, SUBSTRING(fichier_justificatif_domicile, -4)), IF(fichier_justificatif_domicile REGEXP '\.[a-zA-Z0-9]{4}', CONCAT('justificatif_domicile-', id_lender_account, SUBSTRING(fichier_justificatif_domicile, -5)), ''))),
  fichier_rib = IF(fichier_rib = '', '', IF(fichier_rib REGEXP '\.[a-zA-Z0-9]{3}', CONCAT('rib-', id_lender_account, SUBSTRING(fichier_rib, -4)), IF(fichier_rib REGEXP '\.[a-zA-Z0-9]{4}', CONCAT('rib-', id_lender_account, SUBSTRING(fichier_rib, -5)), ''))),
  fichier_cni_passeport_dirigent = IF(fichier_cni_passeport_dirigent = '', '', IF(fichier_cni_passeport_dirigent REGEXP '\.[a-zA-Z0-9]{3}', CONCAT('cni_passeport_dirigent-', id_lender_account, SUBSTRING(fichier_cni_passeport_dirigent, -4)), IF(fichier_cni_passeport_dirigent REGEXP '\.[a-zA-Z0-9]{4}', CONCAT('cni_passeport_dirigent-', id_lender_account, SUBSTRING(fichier_cni_passeport_dirigent, -5)), ''))),
  fichier_extrait_kbis = IF(fichier_extrait_kbis = '', '', IF(fichier_extrait_kbis REGEXP '\.[a-zA-Z0-9]{3}', CONCAT('kbis-', id_lender_account, SUBSTRING(fichier_extrait_kbis, -4)), IF(fichier_extrait_kbis REGEXP '\.[a-zA-Z0-9]{4}', CONCAT('kbis-', id_lender_account, SUBSTRING(fichier_extrait_kbis, -5)), ''))),
  fichier_delegation_pouvoir = IF(fichier_delegation_pouvoir = '', '', IF(fichier_delegation_pouvoir REGEXP '\.[a-zA-Z0-9]{3}', CONCAT('delegation_pouvoir-', id_lender_account, SUBSTRING(fichier_delegation_pouvoir, -4)), IF(fichier_delegation_pouvoir REGEXP '\.[a-zA-Z0-9]{4}', CONCAT('delegation_pouvoir-', id_lender_account, SUBSTRING(fichier_delegation_pouvoir, -5)), ''))),
  fichier_statuts = IF(fichier_statuts = '', '', IF(fichier_statuts REGEXP '\.[a-zA-Z0-9]{3}', CONCAT('statuts-', id_lender_account, SUBSTRING(fichier_statuts, -4)), IF(fichier_statuts REGEXP '\.[a-zA-Z0-9]{4}', CONCAT('statuts-', id_lender_account, SUBSTRING(fichier_statuts, -5)), ''))),
  fichier_autre = IF(fichier_autre = '', '', IF(fichier_autre REGEXP '\.[a-zA-Z0-9]{3}', CONCAT('autre-', id_lender_account, SUBSTRING(fichier_autre, -4)), IF(fichier_autre REGEXP '\.[a-zA-Z0-9]{4}', CONCAT('autre-', id_lender_account, SUBSTRING(fichier_autre, -5)), ''))),
  fichier_document_fiscal = IF(fichier_document_fiscal = '', '', IF(fichier_document_fiscal REGEXP '\.[a-zA-Z0-9]{3}', CONCAT('document_fiscal-', id_lender_account, SUBSTRING(fichier_document_fiscal, -4)), IF(fichier_document_fiscal REGEXP '\.[a-zA-Z0-9]{4}', CONCAT('document_fiscal-', id_lender_account, SUBSTRING(fichier_document_fiscal, -5)), '')))
WHERE id_client_owner NOT IN (SELECT id_client FROM clients WHERE email LIKE '%@unilend.fr');

UPDATE prospects SET
  prenom = CONCAT('Prenom ', id_prospect),
  nom = CONCAT('Nom ', id_prospect),
  email = CONCAT('prospect', id_prospect, '@devunilend.fr')
WHERE email NOT LIKE '%@unilend.fr';

UPDATE attachment SET path = CONCAT(type_owner, '-', id_owner, '-', id_type, IF(path REGEXP '\.[a-zA-Z0-9]{3}', SUBSTRING(path, -4), IF(path REGEXP '\.[a-zA-Z0-9]{4}', SUBSTRING(path, -5), 'ext')));
