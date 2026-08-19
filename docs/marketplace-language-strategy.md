# Language packaging strategy

## Decision for version 1.0.0

The repository and the university's internal deployment copy retain the
Brazilian Portuguese language pack because it is required by the production
environment and has already been validated in UTF-8.

The official Marketplace ZIP is generated with `git archive`. The repository's
`.gitattributes` excludes `lang/pt_br` from that distributable package, leaving
English as the bundled source language while preserving the institutional
translation in version control.

## Marketplace follow-up

After the plugin is approved in the Moodle Plugins directory, submit and
maintain Brazilian Portuguese strings through AMOS, Moodle's translation
platform. Once the AMOS language pack is available and deployed, reassess
whether the repository copy of `lang/pt_br` is still needed.

## Operational safeguard

Do not replace the university's internal plugin directory with the
Marketplace-only ZIP until the Brazilian Portuguese strings are available from
AMOS. The internal backup at `E:\Moodle03dev\courseqbankcopy` remains the
authoritative package for the current production rollout.
