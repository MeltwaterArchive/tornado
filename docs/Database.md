# Tornado Database

## Migrations

Tornado makes use of the (Doctrine Migrations) [http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/]
package to manage its database migrations. See below for a quick guide as how to use.

### Approach

Database changes should always be *additive*; they should be backwards compatible unless you know that the fields are no
longer required for any installed version of the software. For example, if you want to rename a column, add the newly
named one and copy the data over from the old one; wait until a new version of the software is released in order to
remove the old one.

### Creation

In order to create a new database migration, run the `migrations:generate` command through the console:

```
$ php app/console migrations:generate
```

This will generate a class in the `src/app/migrations` directory with a prefix of `Version` followed by a timestamp. It
is this timestamp that is used to track whether a migration version has been applied in a given database; these versions
are stored in the `doctrine_migration_versions` table.

Each of these migrations require both an `up()` and a `down()` method; use `up()` to perform the migration, and `down()`
to reverse it. Eg:

```
public function up(Schema $schema) {
    $this->addSql('ALTER TABLE workbook ADD COLUMN created_at TIMESTAMP');
}

public function down(Schema $schema) {
    $this->addSql('ALTER TABLE workbook DROP COLUMN created_at');
}
```

This will then allow the seamless transition between versions.

### Application

To apply migrations, use the console to run the `migrations:migrate` command. This will then inform you that the action
will change the database, and ask you to confirm the action. Should you wish to go to a previous version of the database
schema, you can determine which version you wish to move to and supply it to the command:

```
$ php app/console migrations:migrate 20160302130404
```

This will then reverse any migrations after the specified version. If you are uncertain as to the current status of the
schema, use the `migrations:status` command. This will list the current version, the latest version and the number of
pending migrations (if any).