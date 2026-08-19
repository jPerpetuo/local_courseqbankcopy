# Moodle Plugins directory listing

## Plugin name

Independent question bank copy

## Component

`local_courseqbankcopy`

## Short description

Creates independent question bank copies during native course import and
repoints fixed and random quiz questions to the destination course.

## Full description

Independent question bank copy changes Moodle's native course reuse import so
question banks can be copied into the destination course instead of remaining
shared with the source course.

The plugin copies complete question bank category hierarchies and reconciles
both fixed quiz questions and random question references. The resulting course
can continue using its banks and quizzes after the source course is archived or
deleted.

The integration uses Moodle extension points and does not require changes to
core files. Administrators control the default under Moodle's general import
defaults. Users without the override capability follow that default without
seeing an additional selector.

A read-only administrative diagnostic report identifies independent, external,
and invalid fixed and random question references. It also reports copied banks,
categories, main questions, internal Cloze subquestions, and operation metadata.

## Key features

- Native `Course reuse > Import` user experience.
- Complete, independent question bank copies.
- Fixed and random quiz reference reconciliation.
- Administrative default and lock control.
- Capability-controlled reuse mode.
- Read-only technical diagnostics.
- No Moodle core modifications.
- Automated testing against Moodle 5.1 and 5.2.

## Requirements

- Moodle 5.1.x or 5.2.x.
- A PHP and database version supported by the installed Moodle release.
- Moodle cron configured and running regularly.

## Installation

Install the release ZIP through Moodle's plugin installer, or place the
`courseqbankcopy` directory in `local/`, then complete the Moodle upgrade and
purge caches.

## Privacy

The plugin does not store personal user data. Its database tables contain only
technical course import operation identifiers, statuses, and source-to-target
mappings. It implements Moodle's Privacy API as a null provider.

## Support and source

- Source code: https://github.com/jPerpetuo/local_courseqbankcopy
- Bug tracker: https://github.com/jPerpetuo/local_courseqbankcopy/issues
- Release notes: https://github.com/jPerpetuo/local_courseqbankcopy/blob/main/CHANGES.md

## Suggested tags

`question bank`, `course import`, `quiz`, `course reuse`, `backup`, `restore`

## Version 1.0.0 release notes

First stable release. It provides complete independent question bank copies,
fixed and random quiz reference reconciliation, administrative defaults,
diagnostics, scheduled metadata cleanup, and automated compatibility checks for
Moodle 5.1 and 5.2.

## Screenshots

1. **Default import setting** — Shows the independent question bank copy option
   alongside Moodle's native initial import settings.
2. **Administrative diagnostics** — Should show the summary counts and the
   fixed/random reference table from a sanitised test course.

The first screenshot is stored in `docs/screenshots/`. Capture the second from
the final Moodle 5.2.1 validation environment after replacing institutional and
course-identifying data with test values.
