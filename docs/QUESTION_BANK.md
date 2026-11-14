# Question bank replacement

The canonical catalog contains 400 original questions: 10 existing categories,
four difficulty levels, and 10 questions in every category/level cell. Each
normal-stage cell has nine technical questions and one light question; sudden
death has ten technical questions. Correct answer keys are balanced at 25 per
letter per level. Options have at most 20 characters and a within-question
length spread of at most six characters. Browser regressions measure all 1,600
options with the player renderer at a 320px viewport, without clipping text.

Difficulty progresses from concepts to application, combined analysis and
expert edge cases. This is an editorial progression, not a measured exponential
difficulty curve. Dataset headers link the primary references consulted.

## Inspect before applying

```bash
php artisan trivia:sync-questions
```

This is a read-only dry run. The output reports questions to replace/remove,
teams to reset, answers to clear, and deliveries to clear. Invalid catalogs,
missing or ambiguous module/level mappings, and running/paused/tiebreak games
are refused before mutation. Exactly one event configuration is required.

## Apply an authorized replacement

Notify the operator and take a complete database backup before applying. Keep
the backup outside the web root with restrictive permissions. For example, on
the authorized VPS with local MySQL administrative access:

```bash
umask 077
mysqldump --single-transaction --no-tablespaces seventech_trivia > /root/seventech-before-bank.sql
php artisan trivia:sync-questions --apply
```

`--apply` replaces the old bank and clears current responses and deliveries.
It resets team scores, phase, answered/correct counts, elapsed answer time,
streaks and screen-exit incidents. Teams, members, names, session tokens,
recovery PINs, module/level identities and archived event snapshots remain.
The event returns to registration with a new selection seed and no active clock.

All database changes use one transaction and preserve foreign-key enforcement.
`DELETE` is used instead of `TRUNCATE`, which would implicitly commit. Failure
rolls the entire operation back. If the bank and tournament are already current
and clean, applying again makes no changes. Running it after a later finished
tournament resets that tournament, so the command is an explicit operator action.

Use `DatabaseSeeder` only for a new database. Never rerun the complete seeder on
an established production installation. Changes here need no schema migration.

## Verification and recovery

Confirm 400 active questions, 10 in every category/level, no current answers or
deliveries, zero team progress, retained members, and event state `registro`.
Check player registration, score layouts and option rendering through HTTPS.

Runtime files and a database backup must be retained together for rollback.
After a successful replacement commits, restoring old history requires the
database backup; reverting PHP files alone does not restore removed records.
Only restore into production after stopping new tournament activity and obtaining
authorization for overwriting subsequent data.

Regression tests run only against isolated `seventech_trivia_test` instances.
PHPUnit and the browser preview use different instances because PHPUnit rebuilds
tables. See `tests/browser/README.md` for the browser runner.
