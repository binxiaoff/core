UPDATE clients SET
  email = CONCAT(id_client, '@devunilend.fr'),
  password = MD5(id_client),
  slug = id_client,
  prenom = CONCAT('Prenom ', id_client),
  nom = CONCAT('Nom ', id_client),
  nom_usage = CONCAT('Nom usage ', id_client),
  telephone = CONCAT(SUBSTRING('0100000000', 1, 10 - LENGTH(id_client)), id_client),
  mobile = CONCAT(SUBSTRING('0600000000', 1, 10 - LENGTH(id_client)), id_client),
  naissance = CONCAT('19', SUBSTRING(ROUND(RAND(id_client) * 100), -2), '-', ROUND(RAND(id_client) * 100) % 12, '-', ROUND(RAND(id_client) * 100) % 31),
  cni_passeport = IF(cni_passeport = '', '', CONCAT('cni', '-', id_client, IF(cni_passeport REGEXP '\.[a-zA-Z0-9]{3}', SUBSTRING(cni_passeport, -4), IF(cni_passeport REGEXP '\.[a-zA-Z0-9]{4}', SUBSTRING(cni_passeport, -5), 'ext'))))
WHERE email NOT LIKE '%@unilend.fr' OR id_client = 1977 OR email = 'contact@unilend.fr';

UPDATE login_log SET
  pseudo = CONCAT('Pseudo ', id_log_login),
  IP = CONCAT(ROUND(RAND(id_log_login) * 300 % 162), '.', ROUND(RAND(id_log_login) * 300 % 255), '.', ROUND(RAND(id_log_login) * 300 % 255), '.', ROUND(RAND(id_log_login) * 300 % 254))
WHERE pseudo NOT LIKE '%@unilend.fr';

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
  motif = IF(motif = '', '', CONCAT('0000', id_lender_account, 'ABC'))
WHERE id_client_owner NOT IN (SELECT id_client FROM clients WHERE email LIKE '%@unilend.fr');

UPDATE prospects SET
  prenom = CONCAT('Prenom ', id_prospect),
  nom = CONCAT('Nom ', id_prospect),
  email = CONCAT('prospect', id_prospect, '@devunilend.fr')
WHERE email NOT LIKE '%@unilend.fr';

UPDATE companies SET email_facture = CONCAT('company', id_company, '@devunilend.fr') WHERE email_facture NOT LIKE '%@unilend.fr';

UPDATE attachment SET path = CONCAT(type_owner, '-', id_owner, '-', id_type, IF(path REGEXP '\.[a-zA-Z0-9]{3}', SUBSTRING(path, -4), IF(path REGEXP '\.[a-zA-Z0-9]{4}', SUBSTRING(path, -5), 'ext')));
