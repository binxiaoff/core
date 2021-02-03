Doctrine migration rollup
=========================

We do the rollup in a production release apart from regular releases.
In a "rollup" release, there is no new migration. So, in general, we do it immediately after a regular release.
This process is done on the local environment, except if there is a specific indication. Here is the process:

Preparation
-----------

1. Create a release branch for the API project from the master branch.
#. Check that the local environment database is up to date with the command ``doctrine:migrations:migrate``, then ``doctrine: migration: diff``.
#. Remove all migrations that are in the branch.
#. Prepare an SQL script to update the ``core_migration_versions`` table of other environments (preprod, demo, develop, local, etc...), which explicitly deletes the lines of old migrations and inserts a new migration generated from the next step. For example:

 .. code-block:: SQL

  DELETE FROM core_migration_versions WHERE version = 'DoctrineMigrations\\Version20200212145951';
  DELETE ...
  INSERT INTO core_migration_versions (version, executed_at, execution_time) VALUES ('DoctrineMigrations\\Version00000000000000', null, null);

5. Generate a new migration with ``doctrine:migrations:dump-schema``. Then, rename this migration class to ``Version00000000000000``, its file name to ``Version00000000000000.php``,
so that the migration will always be executed at first.
#. Commit and push the change.

Deployment
----------

1. Backup ``core_migration_versions`` table on prod into ``core_migration_versions_backup``.

 .. code-block:: SQL

  CREATE TABLE core_migration_versions_backup LIKE core_migration_versions;
  INSERT INTO core_migration_versions_backup SELECT * FROM core_migration_versions;

2. Deploy the code in prod by running the Ansible playbook named ``migration-rollup.yml``.
#. Delete the core_migration_versions_backup table if all goes well.

Post-deployment
---------------
1. Update the local dev of other developers: they need to update their DB. And then, execute the SQL script generated in step 4
#. Update the other envs :
  - If it's a normal evns attached to ``develop`` branche : deploy the last release before the "rollup", then, deployer ``develop `` with the Ansible playbook named ``migration-rollup.yml``
  - If it's a epic branche : Update the env to the last version of the epic, then, execute the SQL script generated in step 4