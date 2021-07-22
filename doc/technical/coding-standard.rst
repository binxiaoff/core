`Return to index <../index.rst>`_

==============
Backend coding standard
==============

Introduction
============

This guide aims to:
 - Increase the readability of the code
 - Standardize the code style
 - Reduce the risk of bugs and regressions

Analysis tools
==============

Instead of reviewing the code manually, we use followning PHP Static analysis tools (via GrumPHP_) to ensure that the code matches the expected code syntax:
 - PHP_CodeSniffer_
 - PHP-CS-Fixer_
 - PHPStan_
 - PHPMD_ (not activated)

Besides these tools, make sure your `PhpStorm settings are synchronized <phpstorm-settings.rst>`_ with the team, which helps a lot.

KLS Standards and Best Practices
================================

Our coding standards follow PSR-1_, PSR-12_, PSR-4_ and `Symfony codding standards <https://symfony.com/doc/current/contributing/code/standards.html#symfony-coding-standards-in-detail>`_.

Besides these standards, we add some additional standards as listed below.

PHP
 - English is the official language for commenting code, commit messages, documentation, etc.
 - ``require`` and ``include`` must be used without parentheses
 - No double assignment on one line
 - Initialize all variables before using them
 - Prefer single quotation marks to double quotation marks
 - Expressions "old", "new", "back", "backup", ".class" are prohibited in filenames
 - Put a space after the type cast
 - For the chained method calls, the calls can be placed on the new line and must always be indented by 4 spaces. The ``;`` must be place on the new line without indentation
 - Always put type-hinting for the method's parameters and its return value
 - Use the minimum visibility possible
 - Use Composer to import external libraries
 - Prefer the strict comparison with false rather than the use of ``!``
 - When testing a value, put the value to be tested before the method variable or return
 - No Captain Obvious style comments (ex: "we loop all elements of the table" before a foreach)
 - Align the assignments (``=``, ``=>``)
 - Do not use deprecated classes, methods or functions
 - To compare a value with null, you must use the identical comparison (``===``), not "is_null()"
 - (In the context of a controller) For verifying the external parameter, you should use, if possible, "requirements" of routing
 - You may use the ``filter_var`` function to validate and clean the external input data. In some case, the usage of this function is insufficient, thus, the additional checks may be required.
 - Use complete name for variables, methods and classes. Abbreviations should be avoided.
 - Do not assign a variable in an if statement.
 - Do not group the import statements.
 - The annotation ``@var`` is not required if it's the same as the type hint. But it should be not ignore if the cases that the type hint cannot cover. For exemple, such as ``@var string[]``

API platform
 - The group name must be in the following format : ``entityName[:decorator]:action``. ``entityName`` and ``decorator`` is in lower camel case.
 - The group must always be put in the entity with which its name starts. For example, ``project:read`` must be put in ``Project``.
 - Available actions:
    - ``read``: general normalize context
    - ``write``: general denormalize context
    - ``create``: denormalize context dedicated to the creation, POST action
    - ``update``: denormalize context dedicated to the update (not available for creation), PUT or PATCH action
 - The optional ``decorator`` is used:
    - if we need some dynamic group for some users or roles (ex: ``project:admin:read`` is used for the fields only available for the site admin the project's arranger)
    - if we need some specific group for a dedicated consumer (ex: ``project:projectParticipation:read`` the field is only available for the nested ``project`` in ``ProjectParticipation``)
 - For each resource operation:
    - the general context should always be presented. For example, if we want to define the groups for a PUT, we put ``entityName:write`` and ``entityName:update`` at the same time.
    - it should use its own custom security access control. For example, we should not do ``"security": "is_granted('view', object.subresource)"`` that call a voter of an other resource.

Doctrine
 - Use annotations for meta-data
 - Use ``trait`` for the repeated columns in different tables
 - Name the FK column without the id. Ex: the name of ``id_client_submitter`` (which is a FK of Clients) in Project entity should named after ``submitterClient``.
 - The use of the named bind is required
 - About validation, favor the use of Doctrine asserts and constraint callback methods if no need to call external service
 - Use Query Builder
 - The queries should be put in xRepository classes
 - The usage of ``EntityManager::getRepository()`` should be avoided. Use ``ManagerRegistry::getManagerForClass()`` instead. Thus "repositoryClass" attribut of Entity may be ignored, if there is no usage.

Doctrine Migration
 - The ticket number must put in  "getDescription()".
 - Use ``INSERT IGNORE`` for the insertion of translations
 - One call to ``addSql()`` for one SQL statement.
 - Always provide ``down()``, if possible.
 - Always modify the entity classes. Don't modify the database directly. Then, use ``doctrine:migrations:diff`` to generate a migration.
 - One migration per ticket
 - Don't put sensitive data in the migration (personal data, password, etc...)
 - Don't modify an existing migration, generate a new one.

SQL
We choose to stick to `SQL Style Guide <https://www.sqlstyle.guide/>`_

We add also our own rules as follow :
 - The use of ``USING`` for SQL joins is strongly discouraged
 - The names of the tables are in the singular
 - The SQL keywords must be in capital letters
 - Use surrogate key as the primary key of a table. It must be called ``id``
 - Indentation in queries is done with 2 spaces
 - Join keywords (``INNER JOIN``, ``LEFT JOIN``, ``RIGHT JOIN``) must be indented against ``FROM``
 - For constant values that never change, hard coded in SQL is tolerated
 - The ``SELECT`` must be on the line following the PHP variable definition, indented by 4 spaces from the beginning of the variable name

.. _PSR-1: https://www.php-fig.org/psr/psr-1/
.. _PSR-12: https://www.php-fig.org/psr/psr-12/
.. _PSR-4: https://www.php-fig.org/psr/psr-4/
.. _PHP_CodeSniffer: https://github.com/squizlabs/PHP_CodeSniffer
.. _PHP-CS-Fixer: https://github.com/FriendsOfPHP/PHP-CS-Fixer
.. _PHPStan: https://github.com/phpstan/phpstan
.. _PHPMD: https://phpmd.org/
.. _GrumPHP: https://github.com/phpro/grumphp
