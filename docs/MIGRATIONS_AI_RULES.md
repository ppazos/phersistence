# Migrations: rules for AI agents

Status: design spec for issue #105. The runner and CLI are not implemented yet; this document defines the contract they must follow.

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

- **Schema:** the driver-neutral API (`create_table`, `add_column`, `add_index`, `add_unique`, `add_fk`, `drop_column`, ...) with portable types (`string(255)`, `int`, `bool`, `text`, `datetime`). Never pass database-specific type strings.
- **Data:** the ORM (`findBy`, `save`, `delete`, ...) or the driver-neutral API.
- **Raw SQL (`executeRaw`):** last resort, only when the API cannot express the operation. Always use parameter binding. Leave a comment saying why raw SQL was needed.
- **Model classes inside migrations:** models change over time and a migration written against today's model can break later. Prefer the driver-level API. Use model classes only for seed data and say so in a comment.

## Hard rules

- **Never edit an applied migration.** The checksum is stored, and a mismatch is an error.
- **Never rename an applied migration.** The only permitted rename is `rebase` on an unapplied one (see below).
- **One concern per migration.** Do not mix unrelated changes.
- **Be explicit about guards.** Schema operations fail loudly if the target already exists or is missing (for example, `add_column` on an existing column). Do not add silent `if exists` workarounds to hide a conflict. If a conflict appears, stop and tell the developer.
- **Destructive changes need confirmation.** Dropping tables or columns, and deleting or overwriting data, require the developer to approve first. Show what will be affected.
- **Do not run `migrate` on your own.** Run it only when the developer asks. Report the outcome faithfully, including failures.
- **Do not touch `ph_migrations` directly.**

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

## CLI summary

| Command | Effect |
|---------|--------|
| `make <slug>` | Create a migration file from the template. |
| `status` | List applied, pending, failed, and out-of-order migrations. |
| `migrate` | Run pending migrations in timestamp order. Fails on out-of-order. |
| `migrate --allow-out-of-order` | Run pending migrations even if out-of-order (warns). |
| `rebase <slug>` | Give an unapplied migration a fresh timestamp. |
| `baseline` | Mark the initial schema migration as applied without running it, for databases that already have the schema. |

## Examples

### Add a column

```php
class AddPersonEmail extends PhMigration
{
   public function schema()
   {
      $this->driver->add_column('person', 'email', 'string(255)', true);
   }
}
```

### Add a column and backfill it

```php
class AddPersonFullname extends PhMigration
{
   public function schema()
   {
      $this->driver->add_column('person', 'fullname', 'string(512)', true);
   }

   public function data()
   {
      // Uses the ORM for the backfill; kept small and independent of later model changes.
      foreach ($this->driver->query('person') as $row) {
         // update fullname from firstname and lastname
      }
   }
}
```

### Split a table (uses all phases)

```php
class SplitAddressFromPerson extends PhMigration
{
   public function setup()   { /* create a temporary mapping table */ }
   public function schema()  { /* create the new address table */ }
   public function data()    { /* copy rows through the mapping table */ }
   public function cleanup() { /* drop the mapping table and the old columns */ }
}
```

The bodies above are placeholders; the final examples will be completed when the runner API is implemented.
