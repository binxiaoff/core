`Return to index <../index.rst>`_

Doctrine migration rollup
=========================

We do the rollup in a production release apart from regular releases.
In a "rollup" release, there is no new migration. So, in general, we do it immediately after a regular release.
This process is done on the local environment, except if there is a specific indication. Here is the process:

Preparation
-----------

1. Create a release branch for the API project from the master branch.
#. Remove all migrations that are in the branch.
#. Prepare an SQL script. This script will be used later to update the ``core_migration_versions`` table on other environments (preprod, demo, develop, local, etc...). It explicitly deletes the lines of old migrations and inserts the new migration named ``Version00000000000000``. For example:

 .. code-block:: SQL

  DELETE FROM core_migration_versions WHERE version = 'DoctrineMigrations\\Version20200212145951';
  DELETE ...
  INSERT INTO core_migration_versions (version, executed_at, execution_time) VALUES ('DoctrineMigrations\\Version00000000000000', null, null);

4. Generate a new migration which contains the creation of the whole schema. There are 2 different ways of doing so :

  * with ``bin/console doctrine:schema:create --dump-sql`` and put it into the migration manually (see the `issue <https://github.com/doctrine/migrations/issues/820>`_ on github)
  * drop all the tables in the local dev environments, and execute ``doctrine:migrations:diff`` (``doctrine/doctrine-migrations-bundle`` doesn't support the option ``--from-empty-schema``)

5. Rename the migration class to ``Version00000000000000``, and its file name to ``Version00000000000000.php``, so that the migration will always be executed at very first. 
#. Commit and push the change.

Deployment
----------

1. Backup ``core_migration_versions`` table on the production into ``core_migration_versions_backup``.

 .. code-block:: SQL

  CREATE TABLE core_migration_versions_backup LIKE core_migration_versions;
  INSERT INTO core_migration_versions_backup SELECT * FROM core_migration_versions;

2. Deploy the code on the production by running the Ansible playbook named ``migration-rollup.yml``.
#. Delete the core_migration_versions_backup table if all goes well.

Post-deployment
---------------
1. Ask other developers to update the local dev. To do so, they need first update their DB to the last version. Then, execute the SQL script generated in the Preparation section.
#. Update the other environments :
  * If it's a normal environments attached to ``develop`` branche : deploy the last release before the "rollup", then, deployer ``develop `` with the Ansible playbook named ``migration-rollup.yml``
  * If it's a epic branche : Update the env to the last version of the epic, then, execute the SQL script generated in step 4
