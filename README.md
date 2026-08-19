# Independent question bank copy

Moodle component: `local_courseqbankcopy`

Stable release: `1.0.0`

Independent question bank copy is a local Moodle plugin that changes the native
`Course > More > Course reuse > Import` workflow so question banks can be copied
into the destination course instead of being reused from the source course.

The copied categories, question bank entries, question versions, fixed quiz
questions, and random question references belong to the destination course. The
source course can therefore be archived or deleted without breaking the copied
question banks and quizzes.

The plugin uses Moodle extension points and does not require changes to Moodle
core files.

## Features

- Adds **Include independent copies of question banks** to the native initial
  import settings.
- Uses independent copy mode by default.
- Copies all question banks and their complete category hierarchies from the
  source course.
- Repoints fixed quiz questions to the copied question bank entries.
- Repoints random quiz questions to the copied categories and contexts.
- Validates the completed operation and fails explicitly if source references
  remain.
- Hides the mode selector from users who are not allowed to override the
  administrative default.
- Provides a read-only administrative diagnostic report.
- Removes completed and failed operation metadata after 30 days.

## Requirements and compatibility

- Moodle 5.1.x or 5.2.x.
- A PHP version supported by the installed Moodle release.
- Moodle cron configured and running regularly.
- No additional Moodle plugins or external services are required.

The automated test matrix covers Moodle 5.1 and 5.2 with MySQL and PostgreSQL.
Compatibility with additional question type plugins should be validated before
institution-wide deployment.

## Installation

### Install from a ZIP package

1. Download the release ZIP.
2. Sign in to Moodle as a site administrator.
3. Open `Site administration > Plugins > Install plugins`.
4. Upload the ZIP and complete Moodle's validation and installation steps.
5. Purge all Moodle caches after installation.

The ZIP must contain one top-level directory named `courseqbankcopy`.

### Install manually

Copy the plugin directory to:

```text
<moodle-directory>/public/local/courseqbankcopy
```

Then open the Moodle administration notifications page, or run the following
commands from Moodle's public directory:

```text
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
```

If an experimental core patch was previously used to change course import or
restore behaviour, restore the official Moodle core files before enabling this
plugin. No core patch is required by this release.

## Configuration

### Default import mode

Open:

```text
Site administration > Courses > Backups > General import defaults
```

The **Include independent copies of question banks** setting controls the
default import mode:

- Enabled: question banks are copied into the destination course.
- Disabled: Moodle keeps its standard question bank reuse behaviour.
- Locked: users cannot override the configured default during import.

Independent copy mode is enabled by default.

### Allowing reuse mode

Open:

```text
Site administration > Plugins > Local plugins > Independent question bank copy
```

Enable **Allow authorised users to choose reuse mode** if selected users should
be allowed to override the administrative default.

The user must also have this course capability:

```text
local/courseqbankcopy:choosereusemode
```

The Manager archetype receives this capability by default. Site administrators
can choose the mode unless the default setting is locked. Teachers and other
users without permission do not see the selector; the administrative default is
still submitted and enforced in the backend.

## Using the plugin

1. Open the destination course.
2. Go to `Course > More > Course reuse`.
3. Select **Import**.
4. Choose the source course.
5. Keep **Include independent copies of question banks** enabled.
6. Continue through the native Moodle import steps.

In independent copy mode, all question bank (`qbank`) activities from the source
course must be included. The plugin attempts to keep them selected and stops the
operation if a complete independent copy cannot be guaranteed.

After the import, fixed and random quiz question references are reconciled with
the copied banks. A final validation checks that the imported quizzes no longer
depend on the source question bank.

## Administrative diagnostics

Site administrators can open:

```text
Site administration > Plugins > Local plugins > Question bank copy diagnostics
```

Enter a destination course ID to generate a read-only report. The report does
not modify Moodle data and includes:

- copy operations and persistent mappings recorded by the plugin;
- question banks and categories owned by the destination course;
- main questions, internal Cloze subquestions, and total internal entries;
- fixed question references classified as independent, external, or invalid;
- random question references classified as independent, external, or invalid;
- the quiz, slot, category or question bank entry, context, and owner course for
  each reference;
- pending Moodle question bank migration tasks;
- a complete technical JSON report.

Reference statuses have the following meanings:

- **Independent:** the reference belongs to the destination course.
- **External:** the reference points to a question bank entry, category, or
  context outside the destination course.
- **Invalid:** a required question bank entry, category, or context no longer
  exists.

The JSON report can contain course names and internal identifiers. Share it only
with authorised support or administration staff.

## Scheduled cleanup

The plugin stores technical operation and mapping metadata for diagnostics. A
scheduled task removes completed and failed operation records after 30 days.
Moodle cron must run regularly for this cleanup to occur.

The plugin does not remove question banks, questions, categories, or quiz data
from destination courses.

## Architecture overview

The plugin integrates with Moodle through supported extension points:

1. An output hook prepares the import mode on Moodle's native import page.
2. The synchronous `course_backup_created` event transforms the temporary
   `questions.xml` before restore begins.
3. Local Backup and Restore API integration preserves module, category, and
   question bank entry mappings.
4. The `course_restored` event reconciles fixed and random quiz references and
   validates independence.
5. A scheduled task removes expired diagnostic metadata.

The import option is initialised by an early JavaScript file to prevent the
field from appearing after the native form has already rendered. The technical
decision and its maintenance criteria are documented in
`docs/decisao-frontend-carregamento-antecipado.md`.

## Limitations

- The plugin applies to Moodle's native course import workflow. It does not add
  a separate backup and restore interface.
- Independent copy mode requires all source question bank activities to be part
  of the import.
- Copying large question banks increases import duration, temporary processing,
  and database and file storage usage.
- The administrative diagnostic report is read-only and does not repair
  external or invalid references.
- The early import-page integration depends on the Moodle 5.1 and 5.2 import
  form structure and must be reviewed before declaring support for a new major
  Moodle version.
- Additional third-party question types may require specific compatibility
  testing.

## Testing

The repository contains PHPUnit tests for transformation, mode selection,
reference reconciliation, diagnostics, and edge cases. Behat scenarios exercise
the native course import flow with fixed and random quiz questions.

GitHub Actions runs Moodle Plugin CI checks, including PHP lint, Moodle coding
style, PHPDoc, plugin validation, upgrade savepoints, JavaScript and CSS checks,
PHPUnit, and selected Behat scenarios.

## Support and releases

- Report reproducible problems in the
  [GitHub issue tracker](https://github.com/jPerpetuo/local_courseqbankcopy/issues).
- See [CHANGES.md](CHANGES.md) for release notes.
- Marketplace-ready descriptive text is maintained in
  [docs/marketplace-listing.md](docs/marketplace-listing.md).

When reporting a problem, include the Moodle and plugin versions, the import
selection used, relevant diagnostic JSON with sensitive data removed, and clear
reproduction steps.

## Privacy

The plugin does not store personal user data and implements Moodle's Privacy API
as a null provider. Its database tables contain technical course import
operation identifiers, status information, and source-to-destination mappings.

## Licence

This plugin is licensed under the GNU General Public License v3 or later. See
`LICENSE` for the full licence text.
