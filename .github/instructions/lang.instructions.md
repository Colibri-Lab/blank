---
name: 'Language files'
description: 'YAML structure and translation conventions for .lang files'
applyTo: '**/*.lang'
---

See the full rules in [Coding Constitution](../copilot-instructions.md) (section 5.5). Key reminders for this file type:

- A `.lang` file is a YAML mapping: top-level keys are text keys, values map language code to translated text. Never put executable code here.
- Language keys must be stable, descriptive, and scoped to the component (prefixed with the component's language-key prefix from its `.html` template, e.g. `odysseus-components-fire-title`).
- Add a translation for every language configured in the Lang module's `lang-langs.yaml` file, not a fixed set of codes.
- Any user-facing text added to a component must be moved here and referenced through its key (`#{key}`), never hard-coded in `.html`/`.js`.
