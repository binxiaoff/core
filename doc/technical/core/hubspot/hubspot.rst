`Return to index <../../../index.rst>`_

========
Hubspot
========

Our CEO, sale, and Customer Success Manager need to work with a customer relationship management (CRM). Hubspot has
been chosen as our CRM.
To have similar data between our database and Hubspot, we have created several commands that runned in a crontab.
Data that we trust are provided by our database.

Good to know : users are called as contact on Hubspot. I will continue to use the term users in this documentation to
refer to our database users and Hubspot contacts.

Test account
------------

We created a test account ``test@kls-platform.com`` (password on bitwarden).
If you want to test to launch all the commands on your local environment, you need to add to your ``.env.local`` the
``HUBSPOT_API_KEY`` (bitwarden)

Entity
------

To work with Hubspot, we created two entities, ``HubspotContact`` and ``HubspotCompany``. Each entity has two ``id``
that linked the object from our database
to the object from Hubspot. (``id_user`` -> ``id_contact``).
Each command has the same option which is the limit of the number of actions to perform (can be the number of
imported elements, or the number of exported / updated elements.

First commands to launch
------------------------
``kls:core:hubspot:company:import``

``kls:core:hubspot:contact:import``

These two commands create a mapping between the existing users and companies on Hubspot and those of our database. These commands don't modify any information on the users and
companies on our database.

Second commands to launch
-------------------------

``bin/console kls:core:hubspot:company:export``

``bin/console kls:core:hubspot:user:export``

Now that we have mapped all existing data from Hubspot, we need to export our data to Hubspot.
When performed, if the object sent to Hubspot already exist, the command will update it (ex for user : `u.updated >
hc.synchronized` or `hc.synchronized < :dateSubOneDay`

Thanks to the company domain name, Hubspot is able to link users to company, which it is not necessary to create a command for that.

Third command to get remaining api calls
----------------------------------------
As long as we use a licence without a proper developer account, we are limited to 250k calls per day.
If you want to check how many calls you did remain, you need to launch :
``kls:core:hubspot:api-usage:show``

Also, each API request will include the following rate limit headers in the response.

Commands rules
--------------

Each commands have an option that can be passed (``limit``).
For ``import..`` type commands, the ``limit`` option defines the number of items we want to import. In these commands there is a second ``limit`` option defined in the
HubspotClient corresponding to the number of elements we want to retrieve from Hubspot API.

For ``export..`` commands, the ``limit`` option defines the number of items we want to export. This same option is also used when fetching our data on our database.

Each payload sent to Hubspot corresponding to one object (can be a unique company or a unique user). We don't use batch object at all.
If one day, we want to use batch operations, please try to respect this rule given by Hubspot :

  Batch operations for creating, updating, and archiving should be limited to batches of 10

Logs
----

If you encounter some errors when you launch those commands, you have two different ways to see errors logs:

- You can ``tail -f var/logs`` and see errors printed on this file.
- Go to your settings integration page and click on Call log button to see errors.

Data sent
---------

Users fields
^^^^^^^^^^^^

+------------------+-------------------------------------+-------------------------------+
| Field            | Hubspot property Name               | additional comment            |
+==================+=====================================+===============================+
| Fist name        | ``firstname``                       |                               |
+------------------+-------------------------------------+-------------------------------+
| Last name        | ``lastname``                        |                               |
+------------------+-------------------------------------+-------------------------------+
| Email            | ``email``                           |                               |
+------------------+-------------------------------------+-------------------------------+
| Function         | ``function``                        |                               |
+------------------+-------------------------------------+-------------------------------+
| Phone            | ``phone``                           |                               |
+------------------+-------------------------------------+-------------------------------+
| User Status      | ``kls_user_status``                 |  ``invited`` or ``created``   |
+------------------+-------------------------------------+-------------------------------+
| Last connection  | ``kls_last_login``                  |                               |
+------------------+-------------------------------------+-------------------------------+
| Expiration init  | ``kls_init_token_expiry``           |                               |
| link             |                                     |                               |
+------------------+-------------------------------------+-------------------------------+
| User             | ``kls_user_staff``                  | Bank list, only active        |
| habilitations    |                                     | staff                         |
+------------------+-------------------------------------+-------------------------------+
| Manager          | ``kls_user_manager``                | Bank list, only active        |
| habilitations    |                                     | staff                         |
+------------------+-------------------------------------+-------------------------------+
| Admin            | ``kls_user_admin``                  | Bank list, only active        |
| habilitations    |                                     | staff                         |
+------------------+-------------------------------------+-------------------------------+
| Droit creation   | ``kls_staff_arrangement_creation``  | Bank list, only active staff  |
| arrangement      |                                     |                               |
+------------------+-------------------------------------+-------------------------------+
| Droit agency     | ``kls_staff_agency_creation``       | Bank list, only active staff  |
| creation         |                                     |                               |
+------------------+-------------------------------------+-------------------------------+

Company fields
^^^^^^^^^^^^

+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
| Field            | Hubspot property Name                  | additional comment                                                                                                                                 |
+==================+========================================+====================================================================================================================================================+
| Name             | ``firstname``                          |                                                                                                                                                    |
+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
| Domain           | ``domain``                             |                                                                                                                                                    |
+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
| Short code       | ``kls_short_code``                     |                                                                                                                                                    |
+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
| Banking group    | ``kls_bank_group``                     |                                                                                                                                                    |
+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
| Client status    | ``kls_company_status``                 | creation entreprise = ``prospect``                                                                                                                 |
|                  |                                        | status client = ``client``                                                                                                                         |
+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
| Initialization   | ``kls_user_init_percentage``           | `Cliquez ici <https://www.notion.so/lafabrique/Scripts-SQL-stats-et-support-77f22119abe940f18b6f5693c44ca5e0#be6adde4bd574934a7b813d1464064e7/>`_  |
| percentage       |                                        |                                                                                                                                                    |
+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
| Activated modules| ``kls_active_modules``                 | - agency                                                                                                                                           |
|                  |                                        | - arrangement                                                                                                                                      |
|                  |                                        | - arrangement_externe_bank                                                                                                                         |
|                  |                                        | - participation                                                                                                                                    |
+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
| Agency projects  | ``kls_agency_projects``                |  display a number                                                                                                                                  |
+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
| Arrangement      | ``kls_arrangement_projects``           |                                                                                                                                                    |
| folder           |                                        |  display a number                                                                                                                                  |
+------------------+----------------------------------------+----------------------------------------------------------------------------------------------------------------------------------------------------+
