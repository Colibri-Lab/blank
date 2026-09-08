---
name: 'JavaScript / Colibri UI'
description: 'Naming, namespace, component and store-binding conventions for JavaScript files'
applyTo: '**/*.js'
---

See the full rules in [Coding Constitution](../copilot-instructions.md) (sections 3, 5, 5.6, 6.1, 7, 8, 8a). Key reminders for this file type:

- Public methods start uppercase (`Save()`); private methods start `_` + lowercase (`_checkOrder()`); protected methods start lowercase (`render()`); event handlers start `__` + lowercase (`__buttonClicked()`).
- Mark static methods with `@static`; mark `async`/`Promise`-returning methods with `@async`.
- A namespace is a folder; root namespace files declare `const Name = class {}`, nested namespaces assign `Parent.Nested = class {}`. Namespace DocBlocks need `@namespace` and `@memberof <parent>`.
- Class files live in their namespace folder and are named `<000>.<ClassName>.js`, where the three-digit index controls load order and the class name must match the declared class exactly.
- A component class extends `Colibri.UI.Component` (directly or via a Colibri UI subclass) and always ships as four files with the same index/name: `.html`, `.js`, `.lang`, `.scss`.
- Implement every component property as a `get`/`set` pair, not a plain field; the setter converts the value with `this._convertProperty('<Type>', value)` before storing it (HTML attributes always arrive as strings) and calls a private `_show<PropertyName>()` renderer (see the `attr` snippet pattern).
- Modules extend `Colibri.Modules.Module` (which extends `Colibri.IO.RpcRequest`); backend calls go through `this.Call('<Controller>', '<Action>', params)` inside a method named after the backend action, returning the `Promise`, marked `@async`.
- Use `Colibri.Storages.Store` (`App.Store` as root) for client state: `Set()`/`Add()` to write, `AddPathLoader()` to register loaders, `Reload()`/`Query()`/`AsyncQuery()` to read, `AddPathHandler()` to observe a path. Store paths use `app.<moduleName>.<property>` format.
- Components bind to a store path via `binding`/`store`; override `__renderBoundedValues(data, path)` for anything beyond assigning a plain string to `value`.
- Never hard-code user-facing text; reference the component's `.lang` key as `#{key}`.
- If a component's constructor accepts an `element` argument and calls `this.GenerateChildren(element, ...)` itself (the `PaneGrid`/`Widget`/`Window`/`Grid`-style pattern), document that its children must be referenced by fully-qualified class name in templates — relative/short names silently fail to resolve for them.
- Every property/method needs a DocBlock with the correct visibility tag (`@public`, `@private`+`@ignore`, `@protected`) and `@param`/`@return`/`@var` types.
