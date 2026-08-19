# Change log

All notable changes to this plugin are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project uses semantic versioning for public releases.

## [1.1.0] - 2026-08-19

### Changed

- Expanded the declared compatibility range to Moodle 5.0 through 5.2.
- Added automated coverage for the initial Moodle 5.0 release and the current
  Moodle 5.0 stable branch.
- Added MariaDB 10.11 to the automated database matrix.

### Compatibility

- Moodle 5.0.x.
- Moodle 5.1.x.
- Moodle 5.2.x.

### Validation

- The automated matrix passed on Moodle 5.0.0 and the current Moodle 5.0 stable
  branch, including PHPUnit and a Behat course-import scenario.
- Manual acceptance testing on a separate Moodle 5.0 site was not performed
  because no such environment was available for this release.

## [1.0.0] - 2026-08-19

### Added

- Independent copies of complete question banks during Moodle's native course
  import workflow.
- Reconciliation of fixed quiz questions with copied question bank entries.
- Reconciliation of random quiz questions with copied categories and contexts.
- Administrative default and lock setting under Moodle's general import
  defaults.
- Capability-controlled access to reuse mode. Users without permission do not
  see the selector, while the configured default remains enforced.
- Read-only administrative diagnostics for independent, external, and invalid
  fixed and random question references.
- Diagnostic counts for main questions, internal Cloze subquestions, and total
  internal question entries.
- Scheduled cleanup of expired operation and mapping metadata.
- Automated PHPUnit, Behat, coding-standard, validation, and compatibility
  checks for Moodle 5.1 and 5.2.

### Compatibility

- Moodle 5.1.x.
- Moodle 5.2.x.
- MySQL/MariaDB-compatible production deployments and PostgreSQL, subject to
  the database versions supported by the selected Moodle release.

### Notes

- This release requires no Moodle core patch.
- Large question banks can increase import time and temporary resource usage.
- Sites using third-party question types should include those types in local
  acceptance testing.

[1.1.0]: https://github.com/jPerpetuo/local_courseqbankcopy/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/jPerpetuo/local_courseqbankcopy/releases/tag/v1.0.0
