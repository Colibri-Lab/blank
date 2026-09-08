---
name: 'Component HTML templates'
description: 'Template root element, language-key prefix and text-binding conventions for component HTML files'
applyTo: '**/*.html'
---

See the full rules in [Coding Constitution](../copilot-instructions.md) (sections 5.4, 5.5, 5.6). Key reminders for this file type:

- The root element must declare the component name with `ComponentName="App.Modules...."` (`comp` snippet).
- The second line of every component template must be an HTML comment with the language key prefix, e.g. `<!-- odysseus-components-fire -->`.
- Every key in the component's `.lang` file must start with that same prefix (e.g. `odysseus-components-fire-title`).
- Reference language keys in the template as `#{key}` (`#` snippet); never hard-code user-facing text.
- A child tag resolves, first match wins: as a fully-qualified name as-is, relative to the enclosing component's own full class name (trimming one dot-segment at a time), prefixed with `Colibri.UI.` (built-ins like `<FlexBox>`, `<Icon>`, `<Link>`), or prefixed with `App.Modules.`. An unresolved tag is silently cloned as an inert plain HTML element — no error is raised, so always verify a new tag actually renders.
- Exception: inside `Colibri.UI.PaneGrid`, `Widget`, `Window`, `ModelessWindow`, `Grid`, `Input`, `MapLibre`, `Waterfall`, `Graph` (and any custom component with the same `GenerateChildren(element, ...)` pattern), relative/short child tag names do not resolve — always use the fully-qualified class name for their children.
