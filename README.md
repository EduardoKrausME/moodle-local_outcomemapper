# Moodle local_outcomemapper

`local_outcomemapper` is a course-level mapping and review tool for Moodle 4.5+. It compares learning objectives with
the course evidence that may teach or assess them, while keeping the authoritative Moodle data and the AI interpretation
clearly separated.

The plugin can use:

- Moodle competencies already attached to the course;
- grade outcomes used by the course;
- additional text objectives entered only for the current analysis;
- section summaries;
- selected activities and resources;
- selected assessments;
- quiz questions, when the current user can inspect them.

It produces an objective-by-evidence matrix with the relations `strong`, `probable`, `weak` and `none`, plus confidence,
evidence and explanation. These values are AI suggestions, not facts. A teacher can confirm or reject positive
suggestions, and confirmed mappings are stored separately from the AI suggestions. The plugin never changes Moodle
competencies or grade outcomes automatically.

## Requirements

- Moodle 4.5 or newer.
- PHP version supported by the installed Moodle release.
- `local_ai_bridge` version `2026093001` or newer:
  https://github.com/EduardoKrausME/moodle-local_ai_bridge/

The dependency is declared in `version.php`:

```php
$plugin->dependencies = [
    'local_ai_bridge' => 2026093001,
];
```

No provider, API key, endpoint or model is configured by this plugin. All AI traffic goes exclusively through:

```php
\local_ai_bridge\api::generate('outcomemapper-map', $messages);
```

## AI Bridge purpose

Every tenant that should use Outcome Mapper must have an enabled AI Bridge purpose with this exact idnumber:

`outcomemapper-map`

The route/model/credit configuration belongs to `local_ai_bridge`, not to this plugin.

The plugin sends a constrained JSON dataset containing local objective and target IDs plus only the normalized text
necessary to compare them. It asks the model for JSON only and validates the complete result before anything is stored.
A response is rejected when it contains unknown IDs, duplicate IDs, unsupported relation values or an incomplete
objective/target matrix.

The expected response shape is:

```json
{
  "targets": [
    {
      "target_id": "cm:123",
      "relations": [
        {
          "objective_id": "competency:9",
          "relation": "strong",
          "confidence": 92,
          "evidence": "Concrete evidence from the supplied target text.",
          "explanation": "Why the evidence supports this relation."
        }
      ],
      "none": {
        "relation": "none",
        "objective_ids": ["custom:abc123"],
        "confidence": 88,
        "evidence": "No relevant evidence appears in the supplied text.",
        "explanation": "Why no apparent relationship was found."
      }
    }
  ]
}
```

`none` relations are grouped in the model response to avoid wasting output tokens, then expanded into normal matrix rows
by deterministic PHP validation. Every objective must still be classified exactly once for every target.

## Deterministic collection first

The Moodle side is authoritative. PHP collects the actual course structure and identifies each object before AI is
involved. AI does not discover course modules, competencies, outcomes or quiz questions by itself.

The first version collects module definitions and teacher-authored course content only. It deliberately does not collect
student names, submissions, forum posts, quiz attempts, grades or individual performance. Quiz questions are included
only when requested and when the current user has an appropriate quiz capability.

Text is normalized before it reaches the bridge: executable markup is removed, HTML is converted to plain text,
whitespace is normalized and configurable length limits are applied. Empty textual content is reported as a warning
instead of being silently invented.

## Dashboard and findings

The course dashboard includes an accessible HTML table and does not depend on a chart to communicate the result. It
shows coverage across:

- content;
- activities;
- assessments;
- quiz questions.

It also derives deterministic review prompts from the validated matrix, including:

- objectives that appear to be taught but not assessed;
- assessments without apparent preparatory content;
- activities with no apparent objective relationship;
- assessments with no apparent objective relationship;
- competencies with little confirmed/suggested evidence;
- potentially overrepresented objectives using a documented count heuristic.

These are review signals, not pedagogical verdicts.

## Confirmation model

AI suggestions are written to `local_outcomemapper_suggest`. A teacher with `local/outcomemapper:analyse` can confirm or
reject a positive suggestion.

Confirmed mappings are copied/upserted into `local_outcomemapper_map`, which is intentionally separate from suggestions.
Re-running the AI analysis therefore does not automatically rewrite a teacher-confirmed mapping. Confirmed mappings
override AI suggestions for the same objective/target pair in coverage calculations, while rejecting a suggestion does
not create a confirmed mapping.

No code path modifies official Moodle competency or outcome records.

## Capability and context

The plugin defines:

`local/outcomemapper:analyse`

The capability is course-context based and is granted to editing teachers and managers by default. The dashboard checks
the course context, hidden/available course modules are filtered using Moodle visibility/access information, and
quiz-question extraction performs an additional module-context capability check.

## Privacy

No student identity or individual performance is required by version 1.

The plugin stores user IDs only for accountability of teacher/admin actions:

- who ran an analysis;
- who confirmed/rejected a suggestion;
- who confirmed a persistent mapping.

The Moodle Privacy API provider implements metadata declaration, export, deletion and user-list deletion. Deleting a
user's data anonymizes those accountability references while retaining shared course mappings.

AI Bridge has its own privacy behavior and usage accounting. This plugin does not persist the raw AI response after
validation.

## Installation

Install `local_ai_bridge` first, then place this plugin in:

`local/outcomemapper`

Run the normal Moodle upgrade process and configure the `outcomemapper-map` purpose/routes in AI Bridge. There are no
API-key or provider settings under Outcome Mapper.

## Configuration

Site administration exposes only data-volume controls:

- maximum normalized characters per collected object;
- number of targets per AI batch;
- number of objectives per AI batch.

The batching controls keep requests bounded for large courses. Every batch must still return a complete matrix for the
objects included in that batch.

## Tests

The PHPUnit suite covers:

- content normalization and bounds;
- strict JSON parsing and complete-matrix validation;
- course-module collection;
- Moodle competency collection;
- quiz-question collection;
- absent content warnings;
- default capability behavior and module-specific view denial;
- suggestion confirmation;
- suggestion rejection and separation from confirmed mappings.

The included GitHub Actions workflow installs `local_ai_bridge` as a test dependency, runs PHP lint, Moodle validation,
PHPUnit and `EduardoKrausME/moodle-plugin-validate`.

## License

GNU GPL v3 or later.
