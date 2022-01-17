# MySQL users

The application uses several MySQL users to perform different tasks.

## API user

The `api` user is the main one used to perform most operations.
Its credentials are given to the application using the `DATABASE_URL` environment variable.
Its privileges are the following:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE ON `unilend`.* TO `api`@`%`
```

The CREATE permission is necessary for packages like `symfony/messenger` or `gesdinet/jwt-refresh-token-bundle`.
Those packages create a table at their first launch, and they use the default database connection.

## Migrations user

The `migrations` user is used by doctrine to update the schema.
Its credentials are given to the application using the `DATABASE_URL_MIGRATION` environment variable.
Its privileges are the following:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, REFERENCES, INDEX, ALTER, LOCK TABLES ON `unilend`.* TO `migrations`@`%`
```

## Nominative accounts

For the production database, nominative accounts have been created to better track connections and updates. These accounts are
only given to administrators. They have a readonly account, and a read/write account with the same permissions as the `api` user.
The privileges for the readonly account are the following:

```sql
GRANT SELECT, EXECUTE, SHOW VIEW ON `unilend`.* TO `jsmith`@`%`
```
