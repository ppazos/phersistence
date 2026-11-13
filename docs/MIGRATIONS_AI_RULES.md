# Migrations: rules for AI agents

Copy the "Short version" section into the `CLAUDE.md` (or equivalent) of any project that uses Phersistence.

## Short version

1. Migrations live in `/migrations`, one file per migration: `<timestamp>_<slug>.php`.
2. Create them with `vendor/bin/phersistence make <slug>`. Never create the file by hand.
3. Fill in only the phases you need: `setup()`, `schema()`, `data()`, `cleanup()`. They always run in that order.
4. Use the driver-neutral Phersistence API. Do not write SQL or use MySQL-specific features.
5. Never edit or rename a migration that has been applied. Fix mistakes with a new migration.
6. One concern per migration.
7. Run `migrate` only when the developer asks you to. Never run it automatically.
8. Ask the developer before writing a migration that deletes data.

## File and naming rules

- File name: `<YYYYMMDDHHMMSS>_<slug>.php`, for example `20261111103000_add_person_email.php`.
- The slug is snake_case, describes the change, and is unique across the project. `make` refuses duplicates.
- The class name is derived from the slug (`AddPersonEmail`), never from the timestamp.
- The slug is the identity of the migration. The timestamp is only a sorting hint.
- History is stored in the `ph_migrations` table (slug, timestamp, checksum, status, batch, executed_at), created automatically on first run.

## Structure

A migration extends `PhMigration` and may implement any of these phases. Each is optional and they run in this fixed order:

| Phase | Purpose |
|-------|---------|
| `setup()` | Create intermediate or temporary structures needed to move data. |
| `schema()` | Create or alter tables, columns, indexes, constraints. |
| `data()` | Seed data, move data, change data, delete data. Runs inside a transaction. |
| `cleanup()` | Drop intermediate structures, and legacy columns or tables no longer needed. |

There is no `down()`. Migrations are forward-only. To undo something, write a new migration.

Schema changes are not transactional (database DDL commits implicitly). If `schema()` succeeds and `data()` fails, the schema stays changed and the migration is marked `failed`. Keep schema and data changes small so a failed migration is easy to reason about, and write `schema()` so it can be fixed forward.

## Which API to use

Inside a migration, `$this->driver` is the database driver. Use only its driver-neutral methods.

**Portable column types:** `int`, `long`, `float`, `double`, `decimal`, `boolean`, `date`, `time`, `datetime`, `text`, `string(N)` (for example `string(255)`). Never pass database-specific type strings.

**Schema**

| Method | Notes |
|--------|-------|
| `create_table($table)` | Adds the `id` primary key column automatically. Add the other columns with `add_column`. |
| `drop_table($table)`, `rename_table($table, $new)` | |
| `add_column($table, $column, $type, $nullable, $default = null)` | A mandatory column on a table with rows needs a `$default`, or see "Adding a mandatory column" below. String defaults must be quoted: `"'none'"`. |
| `modify_column($table, $column, $type, $nullable, $default = null)` | Replaces the whole column definition (type, nullability, default). |
| `rename_column($table, $column, $new)`, `drop_column($table, $column)` | |
| `add_index`, `add_unique`, `add_fulltext_index`, `add_fk` | |
| `drop_index($table, $name)`, `drop_fk($table, $name)` | `drop_index` also drops unique constraints. |
| `table_exists`, `column_exists`, `index_exists`, `fk_exists` | For inspection only. Do not use them to silence conflicts (see hard rules). |

**Data** (all use prepared statements; `$where` is a `column => value` map joined with AND, `null` means `IS NULL`)

| Method | Notes |
|--------|-------|
| `insert($table, $row)` | Returns the new id. |
| `select($table, $where = [], $order_by = null)` | Returns an array of rows. |
| `update($table, $values, $where)` | `$where` is required. Returns affected rows. |
| `delete($table, $where)` | `$where` is required. Returns affected rows. |
| `delete_all($table)` | Explicit way to empty a table. |

**Raw SQL (`executeRaw($sql, $params)`):** last resort, only when the methods above cannot express the operation. Always use parameter binding. Leave a comment saying why raw SQL was needed.

**Model classes inside migrations:** models change over time and a migration written against today's model can break later. Do not use them in migrations. Use the driver methods above.

## Hard rules

- **Never edit an applied migration.** The checksum is stored, and a mismatch is an error.
- **Never rename an applied migration.** The only permitted rename is `rebase` on an unapplied one (see below).
- **One concern per migration.** Do not mix unrelated changes.
- **Be explicit about guards.** Schema operations fail loudly if the target already exists or is missing (for example, `add_column` on an existing column). Do not add silent `if exists` workarounds to hide a conflict. If a conflict appears, stop and tell the developer.
- **Destructive changes need confirmation.** Dropping tables or columns, and deleting or overwriting data, require the developer to approve first. Show what will be affected.
- **Do not run `migrate` on your own.** Run it only when the developer asks. Report the outcome faithfully, including failures.
- **Do not touch `ph_migrations` directly.** The only tools for it are the CLI commands below.

## Ordering and out-of-order migrations

The timestamp sorts pending migrations, but it is not a hard key by itself. The runner enforces this:

- If a pending migration has a timestamp **older than the latest applied** migration, `migrate` fails with an out-of-order error:

  ```
  Out-of-order migration: 20261110_add_email_to_person is pending but 20261111_add_person_email already ran.
  Run: vendor/bin/phersistence rebase add_email_to_person
  ```

- The fix is `vendor/bin/phersistence rebase <slug>`. It renames the pending file with a fresh timestamp and changes nothing else. `rebase` refuses to run on a migration that is already applied.
- After a `rebase`, **re-read the migrations that now come before yours** and check whether they touch the same tables or columns. Adjust the migration if they do.
- Emergency override: `vendor/bin/phersistence migrate --allow-out-of-order` runs the out-of-order migrations anyway and prints a warning. Use it only when the developer explicitly asks for it. It is not part of the normal workflow.
- Two migrations from different branches can touch the same thing without any git conflict, because they are separate files. The conflict appears when the second one runs. Treat a failure like that as a real conflict: stop, report it, and propose a reconciling migration. Do not weaken the guards to make it pass.

## Workflow when asked to write a migration

1. Read the changes the developer made to the persistent classes (`git diff`).
2. Read the most recent migrations, and any unapplied ones, to understand the current schema and avoid overlap.
3. Run `vendor/bin/phersistence make <slug>` to generate the template.
4. Fill in only the phases you need, following the rules above.
5. Show the result to the developer and flag anything destructive.
6. Run `vendor/bin/phersistence status` to check for pending or out-of-order migrations.
7. Run `vendor/bin/phersistence migrate` only if the developer asked you to.
8. If the run fails, report the exact error and the migration status. Do not edit the failed migration; ask the developer how to proceed.

## CLI

Installed by composer as `vendor/bin/phersistence`. It reads `phersistence.php` from the project root (override with `--config=<file>`), which returns the connection data:

```php
<?php
return [
   'host' => 'localhost', 'user' => 'app', 'pass' => 'secret', 'db' => 'app_db',
   'migrations' => 'migrations'   // optional, default 'migrations'
];
```

| Command | Effect |
|---------|--------|
| `make <slug>` | Create a migration file from the template. |
| `status` | List migrations with their state: `applied`, `pending`, `out_of_order`, `failed`, `running`, `modified`, `missing`. |
| `migrate` | Run pending migrations in timestamp order. Fails on out-of-order. |
| `migrate --allow-out-of-order` | Run pending migrations even if out-of-order (warns). |
| `rebase <slug>` | Give an unapplied migration a fresh timestamp. |
| `baseline <slug>` | Mark a migration as applied without running it, for databases that already have its changes. |
| `clear-failed <slug>` | Remove the record of a failed migration so it can run again, after the developer fixed the database by hand. |

Add `--verbose` to any command to log every SQL statement. Exit code is 0 on success and 1 on error.

## Recipes

Every recipe below is covered by tests in `tests/migrations/TestMigrationScenarios.php`.

### Add a table

```php
class CreateCustomer extends PhMigration
{
   public function schema()
   {
      $this->driver->create_table('customer'); // adds id
      $this->driver->add_column('customer', 'name', 'string(100)', false);
   }
}
```

### Add a nullable column, or a mandatory column with a static default

```php
public function schema()
{
   $this->driver->add_column('customer', 'email', 'string(255)', true);
   $this->driver->add_column('customer', 'active', 'boolean', false, 1); // old rows get 1
}
```

### Adding a mandatory column whose value must be computed

Add it nullable, fill it in `data()`, and make it mandatory in `cleanup()`:

```php
public function schema()
{
   $this->driver->add_column('customer', 'code', 'string(20)', true);
}

public function data()
{
   foreach ($this->driver->select('customer') as $row) {
      $this->driver->update('customer', ['code' => 'C'. $row['id']], ['id' => $row['id']]);
   }
}

public function cleanup()
{
   $this->driver->modify_column('customer', 'code', 'string(20)', false);
}
```

### Change the type of a column

```php
public function schema()
{
   $this->driver->modify_column('customer', 'name', 'string(200)', false);
   $this->driver->modify_column('customer', 'visits', 'long', false, 0);
}
```

Existing values are converted by the database. If the conversion can lose data (for example `text` to `string(10)`), say so to the developer before writing it.

### Change nullable / mandatory

To make a column mandatory when it may contain nulls, fix the nulls first or the migration fails:

```php
public function data()
{
   $this->driver->update('customer', ['name' => 'unknown'], ['name' => null]);
}

public function cleanup()
{
   $this->driver->modify_column('customer', 'name', 'string(100)', false);
}
```

To make it optional, call `modify_column` in `schema()` with `$nullable = true`.

### Drop or rename a column or table

```php
public function schema()
{
   $this->driver->drop_column('customer', 'legacy');
   $this->driver->rename_column('customer', 'name', 'full_name');
   $this->driver->drop_table('old_stuff');
   $this->driver->rename_table('customer', 'client');
}
```

Dropping is destructive: ask the developer first.

### Seed default data

```php
public function data()
{
   foreach (['admin', 'editor'] as $role) {
      $this->driver->insert('role', ['name' => $role]);
   }
}
```

### Delete data

```php
public function data()
{
   $this->driver->delete('customer', ['kind' => 'test']);
}
```

Ask the developer first and say which rows are affected.

### Indexes, unique constraints and foreign keys

```php
public function schema()
{
   $this->driver->add_unique('customer', ['email'], 'customer_email_uq');
   $this->driver->add_index('order', ['customer_id'], 'order_customer_idx');
   $this->driver->add_fk('order', 'customer_id', 'fk_order_customer', 'customer', 'id');
}
```

Remove them with `drop_index` and `drop_fk` using the same names.

### Move data to a new table through an intermediate structure

Use all four phases: create a temporary table in `setup()`, the new table in `schema()`, copy the data in `data()`, and drop the temporary table and the legacy column in `cleanup()`. See `test_split_table_with_intermediate_structure` in `tests/migrations/TestMigrationScenarios.php`.

## If a migration fails

`migrate` stops at the failed migration and prints a report with the failed phase, the state it left the database in, and the next steps. Show that report to the developer in full. Do not summarize it away.

What stays and what doesn't:

| Failed phase | Remains in the database | Rolled back | Did not run |
|--------------|-------------------------|-------------|-------------|
| `setup()` | `setup()` changes made before the error | | `schema()`, `data()`, `cleanup()` |
| `schema()` | `setup()` and the `schema()` changes made before the error | | `data()`, `cleanup()` |
| `data()` | `setup()` and `schema()` | `data()` changes | `cleanup()` |
| `cleanup()` | `setup()`, `schema()`, `data()` and the `cleanup()` changes made before the error | | |

Schema changes cannot be rolled back because database DDL is not transactional. The failed migration is marked `failed` and blocks later runs. Migrations after it were not run.

Steps:

1. Run `status` and inspect the database to see which changes were applied.
2. Leave the database in a state where the migration can run again: undo its schema changes by hand, or keep them and adjust the migration so it does not repeat them. Do not do this on your own: tell the developer what you found and propose the fix.
3. Fix the cause of the error in the migration file. It never completed, so editing it is allowed.
4. Run `clear-failed <slug>` to remove the failed record, then run `migrate` again.
