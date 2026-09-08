---
name: 'SCSS'
description: 'Component styling conventions for SCSS files'
applyTo: '**/*.scss'
---

See the full rules in [Coding Constitution](../copilot-instructions.md) (sections 5.4, 8a). Key reminders for this file type:

- A component's `.scss` file shares the same three-digit index and class name as its `.html`/`.js`/`.lang` files and lives in the component's namespace folder.
- Scope component styles with a `[data-object-name="name"] { }` block (`dom` snippet) to target one specific, uniquely-named child.
- To style every child of a given type regardless of its name, nest that type's shared class (`app-component-icon`, `app-component-link`, `app-component-flexbox`, `app-component-button`, etc., added automatically by the base Colibri UI classes) inside the parent's `[data-object-name]` block, e.g. `[data-object-name="menu"] { .app-component-link { } }`; add `>` to restrict to direct children only.
- Use `&.app-component-shown { }` for the shown-state block (`shown` snippet).
- Use `@if variable-exists(...) { }` for theme-conditional blocks (`theme` snippet).
- Do not hard-code text in SCSS; user-facing strings belong in the component's `.lang` file.
