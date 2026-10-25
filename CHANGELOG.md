# Changelog

This changelog documents **only the two most recent published GitHub releases** of `local_outcomemapper`, based on the exact Git differences between each published release tag and its immediately preceding published tag. Changes made to `master` after these tags are excluded.

## [1.1.5] - 2026-10-15

Published: 2026-10-15 20:37:26 UTC.

Comparison: [1.1.4...1.1.5](https://github.com/EduardoKrausME/moodle-local_outcomemapper/compare/1.1.4...1.1.5)

### Changed

- Updated the plugin release metadata in `version.php`: `$plugin->release` changed from `1.1.4` to `1.1.5`, and `$plugin->version` changed from `2026101500` to `2026101501`.

### Scope

The comparison contains only commit `697cc8208ca076081164a7bc9c3ff9a209fe4192` and modifies only `version.php`. The minimum supported Moodle build (`2024100700`) was not changed. No functional code, tests, translations, workflows or user-visible features changed between these release tags.

## [1.1.4] - 2026-10-15

Published: 2026-10-15 18:30:11 UTC.

Comparison: [1.1.3...1.1.4](https://github.com/EduardoKrausME/moodle-local_outcomemapper/compare/1.1.3...1.1.4)

### Documentation and maintenance

- Added or adjusted short PHPDoc description lines in the Moodle backup/restore integration (`backup/moodle2/restore_local_outcomemapper_plugin.class.php`), objective/target key remapping helpers (`classes/restore_key_mapper.php`), and existing PHPUnit test classes (`tests/ai_mapper_test.php`, `tests/course_deletion_test.php` and `tests/restore_key_mapper_test.php`).
- Moved the existing `analysislimitexceeded` language string to its alphabetically appropriate position in both `lang/en/local_outcomemapper.php` and `lang/pt_br/local_outcomemapper.php`. The English and Brazilian Portuguese message text did **not** change.
- Updated `version.php` metadata: `$plugin->release` changed from `1.1.3` to `1.1.4` and `$plugin->version` changed from `2026101300` to `2026101500`.

### Scope

The comparison spans six commits and eight net-modified files: the restore integration, the key mapper, both language files, three test files and `version.php`. The package workflow was deleted and subsequently restored within the intervening commits, so it has **no net change** between these release tags. Changes to PHPDoc and language-key ordering do not establish a new feature or functional behavior change. The minimum supported Moodle build (`2024100700`) did not change.

---

These notes reflect verified published-tag diffs and make no claims about changes made after release `1.1.5`, deployment results or tests executed.
