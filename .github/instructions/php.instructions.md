---
name: 'PHP'
description: 'Naming, documentation and controller/action conventions for PHP files'
applyTo: '**/*.php'
---

See the full rules in [Coding Constitution](../copilot-instructions.md) (sections 2, 3, 4, 6.2). Key reminders for this file type:

- Follow PSR-2 except for the naming/documentation rules in the constitution.
- Public methods start uppercase (`Save()`); private methods start `_` + lowercase (`_checkOrder()`); protected methods start lowercase (`render()`); event handlers start `__` + lowercase. Mark static methods with `@static`.
- Every class DocBlock needs `@class`, `@memberof <namespace>`, and `@extends <parent class>` (use `Object` when there is no explicit parent).
- Document every `__get`/`__set` implementation and list the dynamic properties they expose on the class DocBlock with `@property`, `@property-read` or `@property-write`, including type and English description; mark the magic methods themselves with `@magic`.
- Every property and method needs a DocBlock with the correct visibility tag (`@public`, `@private`+`@ignore`, `@protected`) and typed `@param`/`@return`/`@var`. All descriptions must be in English.
- Backend controllers live in the module's `Controllers` directory and extend `Colibri\Web\Controller` (or the module's established base controller).
- Controller actions are public methods named exactly like the action the client calls via `Call('<Controller>', '<Action>', params)`; keep both sides of the contract in sync.
- Preserve the existing public API unless the task explicitly requires a breaking change; keep changes focused, no unrelated refactors.
