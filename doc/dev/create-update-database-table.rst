===============================================
How to create or update a table in the database
===============================================

1. Create or modify the entity class. Don't forget to use the existing traits to avoid the code duplication.
#. Launch the command ``doctrine:migrations:diff`` and check if the SQLs auto-generated are correct.
#. Add a description to the migration class previously auto-generated.
#. Launch the command ``doctrine:migrations:migrate`` or ``doctrine:migrations:execute --up [the_version]`` to apply the changes in the database
#. Finally, add the migration class to the VCS(git).