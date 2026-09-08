# Coding Constitution

## 1. Technology Stack

- PHP 8.3 or newer.
- Composer for PHP dependency management.
- PSR-4 autoloading with the `App\\` namespace mapped to `App/`.
- Colibri Core, UI, Security, Mainframe, Manage, Lang, Sites, Tools and related internal packages.
- ReactPHP HTTP and socket components for asynchronous PHP workflows.
- JavaScript and AssemblyScript for client-side and low-level code.
- SCSS, compiled through the project tooling.
- YAML configuration files.

Use the existing project libraries, framework APIs and local patterns before introducing new abstractions or dependencies.

When creating or updating a project `jsconfig.json`, follow the existing project format. Preserve the current `include` and `exclude` entries and include all JavaScript files from every vendor `.Bundle` directory recursively with the glob `vendor/**/.Bundle/**/*.js`. Keep the existing Colibri UI source inclusion `vendor/colibri/ui/src/**/*.js` when it is present. Do not manually enumerate individual bundle files; the configuration must cover newly added bundles automatically.

When the user asks to create a Colibri UI component, use the Colibri UI VS Code extension command `colibri-ui.create-component` and its standard workflow. Do not manually create the component bundle when the command is available.

When the user asks to create a new Colibri blank project or local booth, follow the procedure documented in the [Colibri local booth wiki](https://gitlab.colibrilab.pro/colibrilab/blank/-/wikis/How-to-set-up-a-local-booth). Use the VS Code extension command `colibri-ui.create-project`, which runs the configured blank-project Composer command, instead of manually creating the project.

The blank project setup is based on:

```sh
export COLIBRI_MODE="local" && composer create-project colibri/blank-project:dev-master <project-directory> --repository="{\"url\": \"https://gitlab.colibrilab.pro/api/v4/group/7/-/packages/composer/packages.json\", \"type\": \"composer\"}" --stability=dev --remove-vcs
```

Use the repository URL and command configured by the extension or specified by the wiki. The required Colibri mode (`local`, `dev`, `test` or `prod`) must be selected explicitly or supplied through `COLIBRI_MODE`; `local` is the default for local development. The Composer/GitLab access prerequisites must be available before starting installation.

According to the wiki, distinguish the following concepts:

- A local booth/stand is a copy of a project in the web-server directory.
- A project is a module developed separately from the booth and connected to it during development.
- After creating the blank project, add the required modules through Composer.
- For a module under active development, configure a Composer `path` repository and require the module from that local path according to the wiki workflow.

When creating a new blank project, ask the user which standard modules should be added before running module installation. The standard module choices and their local development paths are:

| Module | Local path |
| --- | --- |
| `colibri/security` | `/home/colibrilab/security` |
| `colibri/mainframe` | `/home/colibrilab/mainframe` |
| `colibri/manage` | `/home/colibrilab/manage` |
| `colibri/lang` | `/home/colibrilab/lang` |
| `colibri/sites` | `/home/colibrilab/sites` |
| `colibri/tools` | `/home/colibrilab/tools` |

For every module selected by the user:

1. Add its local path to the new project's Composer `repositories` with repository type `path`.
2. Add the package to `require` using the `@dev` constraint.
3. Preserve the existing Composer configuration and do not add unselected standard modules.

The module setup must use the actual package name from the module's `composer.json` and verify that the configured local path exists before running Composer.

After creating a new project, set directory permissions as part of the standard setup:

```sh
chmod 777 <project-directory>/runtime <project-directory>/web/cache
```

Verify that both directories exist before changing their permissions. Apply this rule only to the new project's `runtime` and `web/cache` directories.

The following Colibri UI VS Code extension commands are available. When the user asks for one of these operations, use the corresponding command by its exact identifier:

- `colibri-ui.create-component` — create a Colibri UI component bundle.
- `colibri-ui.create-namespace` — create a JavaScript namespace.
- `colibri-ui.create-project` — create a blank Colibri project.
- `colibri-ui.create-module` — create and attach a new Colibri module.
- `colibri-ui.download-module` — download or add a Colibri module.
- `colibri-ui.open-component` — open a component or one of its bundle files.
- `colibri-ui.refresh-tree` — refresh the Colibri UI components tree.
- `colibri-ui.create-controller` — create a PHP controller.
- `colibri-ui.create-controller-action` — create a PHP controller action.
- `colibri-ui.open-phpclass` — open a PHP class.
- `colibri-ui.refresh-php-tree` — refresh the PHP backend tree.
- `colibri-ui.migrate` — run the configured project migration command.
- `colibri-ui.models-generate` — generate models for a selected storage.
- `colibri-ui.export-texts` — export language texts.
- `colibri-ui.import-texts` — import language texts.
- `colibri-ui.find-not-translated` — find texts without translations.
- `colibri-ui.import-storages-texts` — import storage language texts.
- `colibri-ui.export-storages-texts` — export storage language texts.
- `colibri-ui.translate-byopenai` — translate texts using the configured AI integration.
- `colibri-ui.translate-byopenai.file` — translate the current file using the configured AI integration.

Every command below that takes arguments expects an absolute filesystem path as its first argument (the VS Code context-menu `e` parameter) — never the class/namespace/controller name. Putting the name in that slot instead of a path is the most common mistake and makes the command fail (synchronous path errors) or silently do nothing (unhandled async rejection). Verify the target folder exists before calling the command, and verify the expected files were actually created afterward — a "command finished" result does not guarantee anything was written.

For `colibri-ui.create-component`, pass, in this exact order:

1. The absolute path to the namespace folder where the component's four files must be created. This path must contain `.Bundle/` or `UI/` (for example `.../src/<Module>/.Bundle/<Namespace>`).
2. The complete class name including its namespace.
3. The complete parent class name including its namespace.
4. The component description.

Example: `["/abs/path/.../.Bundle/Components", "App.Modules.LocalizationAW.Components.Test", "Colibri.UI.Component", "Test component"]`.

For `colibri-ui.create-namespace`, pass, in this exact order:

1. The absolute path to the PARENT namespace folder — the folder that already contains the current namespace's `.js` root file (to add a namespace directly under a module, pass the module's `.Bundle` folder).
2. The new namespace name, without dots and without the parent namespace prefix (for example `Components`, not `App.Modules.X.Components`).
3. The namespace description.

Example: `["/abs/path/.../.Bundle", "Components", "Storage for reusable components"]`. The command creates `<path>/<name>/.js` containing `<CurrentNamespace>.<name> = class {};`.

For `colibri-ui.create-controller`, pass, in this exact order:

1. The absolute path to the module's `Controllers` folder. This path must contain both `/vendor/` and `/src/` segments (the module name is parsed from between them); this command cannot create a controller in the main application's `App/Controllers` folder.
2. The controller name, without the `Controller` suffix/word.
3. The controller description.

For `colibri-ui.create-controller-action`, pass, in this exact order:

1. The absolute path to a file/folder inside the same module (used only to verify it is a module under `/vendor/`).
2. The action name.
3. The action description.

This command always inserts the generated action into the file open in the currently active editor tab, regardless of the path passed as the first argument — open the target controller file and make it the focused editor before running this command.

For `colibri-ui.models-generate`, pass the selected storage as one string parameter in the format `<Module>: <storage-name>` (colon followed by a space, matching the extension's own quick-pick items), for example `LocalizationAW: results`. Do not use a dot as the separator.

For `colibri-ui.create-module`, pass a single argument that is a JSON string — the extension runs `JSON.parse()` on it, so call `JSON.stringify()` on the parameters object yourself and pass the resulting string, not a raw object. The JSON contains the module parameters and an optional nested `params` object for the additional template variables.

Parameter rules for `colibri-ui.create-module`:

- `moduleRepo` is the full Git repository path used for cloning, for example `git@gitlab.colibrilab.pro:atg/example-module.git`.
- `moduleDescription` is the full module description.
- `moduleClassName` is the module class name without a namespace.
- `projectStartsUp` is a boolean indicating whether the module is a startup website/application module.
- If `projectStartsUp` is `true`, also provide the local, production and test domains.
- `modulePath` is the local directory where the repository is cloned and the module template is copied. It is mandatory — if omitted, the command opens a folder-picker dialog and blocks waiting for interactive input.
- `continueToVars` is a boolean indicating whether to continue with the additional template variables after the module is created and attached.
- `commitChanges` is a boolean indicating whether to commit the changes after module creation.
- `projectProdDomain` is the actual field name the command reads for the production domain (not `projectProductionDomain`).
- Do not ask the user for a Composer package name. Derive the Composer package name automatically from `moduleRepo` by extracting the repository vendor and module name and removing the `.git` suffix.
- Require all mandatory parameters in the prompt before executing the command. If the module is a startup application, also require all three domain parameters.
- When the user asks to create a module without providing all required values, ask for the parameters interactively in the documented order. Do not execute the command until all required values are collected.
- After collecting the values, show the complete parameter summary and ask the user to confirm it before executing `colibri-ui.create-module`.
- If `projectStartsUp` is `false`, use `null` for the three domain parameters and do not ask for them.

Known extension limitation: the extension reads `projectStartsUp`, `continueToVars` and `commitChanges` with `value || await showQuickPick(...)`. Because `false` is falsy in JavaScript, passing `false` for any of these three fields does NOT skip its interactive Yes/No prompt — only `true` skips it. There is currently no way to fully automate a `false`/"No" answer for these three fields; warn the user that VS Code will still show that one prompt for each `false` value and they must answer it themselves.

Boolean parameters (`projectStartsUp`, `use-vault`, `use-s3-fs`, `continueToVars`, `commitChanges`) must be passed as actual boolean values (`true` or `false`) inside the JSON, never as string values. Passing the string `"false"` is truthy in JavaScript and will be misinterpreted as `true`. This does not remove the `false`-prompt limitation described above for the three top-level flags.

After the module is created and attached, the command asks whether to continue with additional variables (`ContinueToVars`). If the user confirms, collect the following variables in this exact order:

1. `{database-domain}` — local database domain; default `localhost`.
2. `{database-name}` — local database name; default is the module name.
3. `{database-user}` — local database user; default `root`.
4. `{database-password}` — local database password; default is empty.
5. `{use-vault}` — whether to use Colibri Vault for passwords.
6. `{prod-vault-database-password}` — Vault key for the production database password; ask only when `{use-vault}` is `true`.
7. `{test-vault-database-password}` — Vault key for the test database password; ask only when `{use-vault}` is `true`.
8. `{prod-database-password}` — production database password; ask only when `{use-vault}` is `false`.
9. `{test-database-password}` — test database password; ask only when `{use-vault}` is `false`.
10. `{use-s3-fs}` — whether to use a custom S3 file storage.
11. `{s3-fs-test-domain}` — S3 server domain for the test stage; ask only when `{use-s3-fs}` is `true`.
12. `{s3-fs-prod-domain}` — S3 server domain for the production stage; ask only when `{use-s3-fs}` is `true`.
13. `{comet-server-address}` — Colibri Comet server address; default `comet.colibrilab.pro`.
14. `{comet-server-port}` — Colibri Comet server port; default `3005`.

Pass these fourteen values as a single `params` object argument (the final `colibri-ui.create-module` parameter) instead of asking one question per variable. Use the variable name without braces as the object key, for example `{ "database-domain": "localhost", "database-name": "example", "database-user": "root", "database-password": "", "use-vault": false, "prod-database-password": "secret", "test-database-password": "secret", "use-s3-fs": false, "comet-server-address": "comet.colibrilab.pro", "comet-server-port": "3005" }`. Only include the keys that apply given `use-vault` and `use-s3-fs`; omit the Vault key fields when `use-vault` is `false`, and omit the S3 domain fields when `use-s3-fs` is `false`.

Use the collected values to replace the corresponding variables in the module files. Skipped conditional text variables must be set to an empty string, and skipped conditional boolean variables must be set to `false`. Ask about commit only after this variable stage is complete.

Example for a regular module:

```json
{
    "moduleRepo": "git@gitlab.colibrilab.pro:atg/example-module.git",
    "moduleDescription": "Example module",
    "moduleClassName": "Example",
    "projectStartsUp": false,
    "projectLocalDomain": null,
    "projectProdDomain": null,
    "projectTestDomain": null,
    "modulePath": "/home/atg/example-module",
    "continueToVars": true,
    "commitChanges": false,
    "params": {
        "database-domain": "localhost",
        "database-name": "example-module",
        "database-user": "root",
        "database-password": "",
        "use-vault": false,
        "prod-database-password": "secret",
        "test-database-password": "secret",
        "use-s3-fs": false,
        "comet-server-address": "comet.colibrilab.pro",
        "comet-server-port": "3005"
    }
}
```

Example for a startup module:

```json
{
    "moduleRepo": "git@gitlab.colibrilab.pro:atg/example-app.git",
    "moduleDescription": "Example application",
    "moduleClassName": "ExampleApp",
    "projectStartsUp": true,
    "projectLocalDomain": "example.local",
    "projectProdDomain": "example.com",
    "projectTestDomain": "test.example.com",
    "modulePath": "/home/atg/example-app",
    "continueToVars": true,
    "commitChanges": false,
    "params": {
        "database-domain": "localhost",
        "database-name": "example-app",
        "database-user": "root",
        "database-password": "",
        "use-vault": true,
        "prod-vault-database-password": "vault-key-prod",
        "test-vault-database-password": "vault-key-test",
        "use-s3-fs": true,
        "s3-fs-test-domain": "s3-test.example.com",
        "s3-fs-prod-domain": "s3.example.com",
        "comet-server-address": "comet.colibrilab.pro",
        "comet-server-port": "3005"
    }
}
```

All extension commands except `colibri-ui.create-project` may be executed in any project. Use `colibri-ui.create-project` only when the user asks to create a new blank Colibri project.

The following commands are always fully interactive: they ignore any arguments passed to them and always show their own `showQuickPick`/`showInputBox`/`showOpenDialog` prompts, so they cannot be driven with pre-filled parameters. Tell the user to run these from the Command Palette themselves instead of attempting to pass arguments:

- `colibri-ui.create-project` — always opens a folder-picker dialog regardless of arguments.
- `colibri-ui.download-module` — always shows an interactive module picklist.
- `colibri-ui.export-texts`, `colibri-ui.export-storages-texts`, `colibri-ui.import-texts`, `colibri-ui.import-storages-texts` — chained interactive language/module/file pickers.
- `colibri-ui.find-not-translated` — interactive language picker.
- `colibri-ui.translate-byopenai` — interactive language pickers.
- `colibri-ui.translate-byopenai.file` — interactive language pickers; additionally requires the target `.lang` file to already be the active editor.
- `colibri-ui.open-component`, `colibri-ui.open-phpclass` — expect a components/PHP-tree node object, not a path; only meaningful from the tree view context menu.

`colibri-ui.migrate`, `colibri-ui.refresh-tree` and `colibri-ui.refresh-php-tree` need no parameters and can always be executed directly.

Before executing a command, collect only the parameters required by its standard workflow. Always pass an absolute path as the first argument for commands that require one. If the command is unavailable in the current VS Code session, report that limitation instead of silently replacing it with a manual implementation.

## 2. General Rules

- The coding standard is based on PSR-2, except for the naming and documentation rules defined in this document.
- All code descriptions, DocBlocks and code comments must be written in English.
- Preserve the existing public API unless the task explicitly requires a breaking change.
- Keep changes focused. Do not perform unrelated refactoring or formatting.
- Before changing code, identify and use the nearest existing extension point and local implementation pattern.
- Every property and method must have a DocBlock.
- Use explicit types wherever the language and existing API allow it.

## 3. Method Naming

### PHP and JavaScript

- Public methods must start with an uppercase letter: `Save()`, `Load()`.
- Private methods must start with `_`, followed by a lowercase first word: `_checkOrder()`.
- Protected methods must start with a lowercase letter: `render()`.
- Event handler methods must start with `__`, followed by a lowercase first word: `__buttonClicked()`.
- Static methods must be marked with `@static` in their DocBlock.
- In JavaScript, methods declared with `async` or returning a `Promise` must be marked with `@async`.

## 4. Documentation Rules

### 4.1 Visibility and method markers

Use the following tags in every relevant DocBlock:

- Public members: `@public`.
- Private members: both `@private` and `@ignore`.
- Protected members: `@protected`.
- Static methods: `@static`.
- Asynchronous JavaScript methods: `@async`.
- PHP magic methods: `@magic`.
- Classes: `@class`.

### 4.2 Types and properties

- Constants must be marked as `@const <type>`.
- Variables and properties must be marked as `@var <type>`.
- Method parameters must use `@param <type> <name>`.
- Return values must use `@return <type>`.
- Descriptions for all tags must be in English.

### 4.3 Class DocBlocks

Every class DocBlock must contain:

- `@class`.
- `@memberof <namespace name>`.
- `@extends <parent class>` in every class DocBlock. Use the actual parent class, or `Object` when there is no explicit parent.

Example:

```php
/**
 * Represents a site page.
 *
 * @class
 * @memberof App\\Modules\\Sites\\Models
 * @extends BaseModelDataRow
 */
class Page extends BaseModelDataRow
{
}
```

### 4.4 Dynamic PHP properties

Analyze every implementation of `__get` and `__set` and document the dynamic properties exposed by the class in the class DocBlock:

- `@property` for readable and writable properties.
- `@property-read` for read-only properties.
- `@property-write` for write-only properties.

Each property annotation must include its type, name and an English description. The `__get` and `__set` methods themselves must also have DocBlocks and be marked with `@magic`.

Example:

```php
/**
 * Represents a site page.
 *
 * @class
 * @memberof App\\Modules\\Sites\\Models
 * @extends BaseModelDataRow
 * @property string $name Page name.
 * @property-read string $path Full page path.
 */
class Page extends BaseModelDataRow
{
}
```

## 5. JavaScript Namespaces

A JavaScript namespace is defined by the folder containing the `.js` file.

### 5.1 Root namespace

A root namespace file must define the namespace as a class:

```js
/**
 * Sites module namespace.
 *
 * @namespace
 * @memberof App.Modules
 * @extends Object
 */
const Sites = class {};
```

### 5.2 Nested namespace

A nested namespace must be assigned to its parent namespace as a class:

```js
/**
 * Publications namespace.
 *
 * @namespace
 * @memberof App.Modules.Sites
 * @extends Object
 */
App.Modules.Sites.Publications = class {};
```

The namespace DocBlock must contain `@namespace` and `@memberof <parent namespace>`.

JavaScript classes, methods, properties and constants follow the same documentation and naming rules as PHP, with JavaScript-specific tags such as `@async` where applicable.

### 5.3 Class file structure

- Every JavaScript class must be located in the folder that represents its namespace.
- The file name must contain a three-digit file index and the class name in the following format: `<000>.<ClassName>.js`.
- The class name in the file name must exactly match the class name declared in the file.
- The file index controls the order of files within the namespace and must be unique in that namespace.

Example:

```text
App/Modules/Sites/Publications/02.PublicationsGrid.js
```

```js
/**
 * Publications grid component.
 *
 * @class
 * @memberof App.Modules.Sites.Publications
 * @extends Colibri.UI.Grid
 */
App.Modules.Sites.Publications.PublicationsGrid = class extends Colibri.UI.Grid {
}
```

### 5.4 JavaScript class types

JavaScript classes are divided into two types:

- Plain classes. A plain class is defined in its namespace folder and consists of its `.js` file.
- Component classes. A component class extends `Colibri.UI.Component` directly or through a Colibri UI subclass. A component consists of exactly four files with the same index and class name:
	- `<000>.<ClassName>.html` — the component template. The root element must declare the component name with `ComponentName`.
	- `<000>.<ClassName>.js` — the component class implementation.
	- `<000>.<ClassName>.lang` — the component language data.
	- `<000>.<ClassName>.scss` — the component styles.

All four component files must be located in the component namespace folder and must use the same three-digit index and class name.

### 5.5 Language files

The `.lang` file is a YAML mapping. Each top-level key is a language text key, and its value is a mapping from language code to translated text. Use the language codes configured by the project.

Example:

```yaml
component-title:
	ru: Заголовок компонента
	en: Component title
```

Language keys must be stable, descriptive and scoped to the component when necessary. Do not place executable code in `.lang` files.

All user-facing text added to a component must be moved to that component's `.lang` file and referenced through its language key. Do not add hard-coded user-facing text to component `.html` or `.js` files. Create a translation for every language configured in the Lang module's `lang-langs.yaml` file; use the current language list rather than a fixed set of language codes.

The second line of every component `.html` template must contain an HTML comment with the language key prefix, for example `<!-- odysseus-components-fire -->`. Every language key in the component's `.lang` file must start with this prefix followed by a descriptive suffix, for example `odysseus-components-fire-title`. Reference the key in the component template or JavaScript as `#{odysseus-components-fire-title}`.

### 5.6 Component resolution in HTML templates

Tag-to-class resolution for template children happens in `Colibri.UI.Component.prototype.CreateComponentClass()` (`vendor/colibri/ui/src/06.UI/02.Common/01.Component.js`). For a normal component template, a child tag is resolved in this order, first match wins:

1. As a fully-qualified name as-is (`<App.Modules.X.Y.Z>`).
2. Relative to the enclosing component's own full class name (the `namespace` attribute, which the backend template compiler writes by replacing `ComponentName="Full.Class.Name"` with `namespace="Full.Class.Name"`): the namespace is trimmed one dot-segment at a time from the end and combined with the tag name until a match is found. This is why a short tag like `<Components.Header>` resolves inside another namespace of the same module.
3. Prefixed with `Colibri.UI.` (built-in tags: `<FlexBox>`, `<Icon>`, `<Link>`, `<H1>`, etc.).
4. Prefixed with `App.Modules.` (lets you drop that prefix for a project component).

If none of the four attempts resolves to an actual `Colibri.UI.Component` subclass, no error is raised: the tag is silently cloned as an inert plain HTML element. Always verify a new tag actually renders as the intended component.

**Exception — relative/short names do not work for children generated via a manual `GenerateChildren()` call.** Some components accept an `element` argument in their own constructor and call `this.GenerateChildren(element, ...)` themselves (after `super()`) to turn markup nested inside their own tag into children, instead of relying only on the base `Colibri.UI.Component` constructor's automatic template processing. For these components, always use the fully-qualified class name for every child tag — do not rely on step 2, 3 or 4 above. `Colibri.UI.PaneGrid` documents this explicitly in its own source comment (`<!-- you need to use component full name as child of PaneGrid always -->`).

Known built-in components affected (identify others by the same pattern — a constructor parameter named `element` passed into `this.GenerateChildren(...)`):

- `Colibri.UI.PaneGrid`
- `Colibri.UI.Widget`
- `Colibri.UI.Window`
- `Colibri.UI.ModelessWindow`
- `Colibri.UI.Grid`
- `Colibri.UI.Input`
- `Colibri.UI.MapLibre`
- `Colibri.UI.Waterfall`
- `Colibri.UI.Graph`

Example — correct vs incorrect:

```html
<!-- correct -->
<PaneGrid name="grid" rows="1fr 2fr" columns="1fr 1fr">
    <Colibri.UI.Pane name="pane1" />
    <Colibri.UI.Pane name="pane2" />
</PaneGrid>

<!-- wrong: short/relative names silently fail to resolve here -->
<PaneGrid name="grid" rows="1fr 2fr" columns="1fr 1fr">
    <Pane name="pane1" />
    <Pane name="pane2" />
</PaneGrid>
```

## 8. Validation

After every code change:

- Run the narrowest relevant test, linter or type checker available.
- Verify that new and modified public APIs have complete DocBlocks.
- Check that method names match their visibility rules.
- Do not consider a change complete if the focused validation fails.

## 6. Client-Backend Interaction

### 6.1 Client-side RPC calls

- Client modules must extend `Colibri.Modules.Module`. `Colibri.Modules.Module` extends `Colibri.IO.RpcRequest`.
- Use the inherited `Call()` method for asynchronous requests to backend controllers and actions.
- The request URL is generated automatically from the module entry, controller name and action name. Do not construct the endpoint URL manually unless the framework requires a custom URL resolver.
- When the user asks to call a specific controller action, add a public method to the module class with the same name as the backend action.
- The module method must call `this.Call('<ControllerName>', '<ActionName>', params)` and return the resulting `Promise`.
- The module method must be marked with `@async` in its DocBlock because it returns a `Promise`.
- Use the exact backend controller and action names. Do not rename either side of the contract without updating both sides.

Example:

```js
/**
 * Loads the current settings.
 *
 * @public
 * @async
 * @param {Object|null} params Request parameters.
 * @returns {Promise} Backend response.
 */
Settings(params = null) {
	return new Promise((resolve, reject) => {
		this.Call('Settings', 'Load', params).then(response => {
			resolve(response.result);
		}).catch(error => {
			reject(error);
		});
	});
}
```

### 6.2 Backend controllers and actions

- A module backend controller must be located in the module's `Controllers` directory.
- A backend controller must extend `Colibri\\Web\\Controller`, directly or through the module's established controller base class.
- Controller actions are public methods and must follow the public method naming rule: they start with an uppercase letter.
- The action name used by the client `Call()` must match the backend public method name exactly.
- Controller actions should accept the framework request collections and payload according to the existing local controller pattern and return the framework response object.
- Document every controller and action with a complete English DocBlock, including visibility, parameters and return type.

## 7. Client-Side Storage

- Use `Colibri.Storages.Store` for client-side application data.
- `App.Store` is the root application store. Each module must create its own child store inside `App.Store` during module initialization.
- Store paths must use the application namespace and property path format: `app.<moduleName>.<property>.<property>` and deeper paths when necessary. Module-local paths commonly use the module name, such as `security.settings` or `sites.pages`.
- Use `Set()` to write or replace data at a path and `Add()` when adding an item to a collection according to the store API.
- Register a path loader with `AddPathLoader()`. A loader must be a method or function that returns a `Promise` resolving to the backend response; the response data is written to the configured store path by `Reload()`.
- A path loader may also be declared as a string in the format `Module:Controller.Action`; the store resolves it to a module `Call()`.
- Use `Reload()` to execute a registered loader and refresh the data at its path.
- Use `Query()` to read data synchronously from a path.
- Use `AsyncQuery()` to read data asynchronously. It returns existing data when available and otherwise loads it through the path loader; use its reload option when fresh data is required.
- Use `AddPathHandler()` to observe a path. The handler is called whenever data at the registered path or a related parent/child path changes, and receives the current data and changed path.
- Keep store paths stable and descriptive because components, loaders and handlers use them as shared data contracts.

## 8a. Component Properties and Snippets

- Every JavaScript component property must be implemented with a `get`/`set` pair, not a plain public field. Follow the Colibri UI extension `attr` snippet pattern: the setter converts the incoming value with `this._convertProperty('<Type>', value)`, stores the result, and delegates rendering to a private `_show<PropertyName>()` method.
- An HTML template attribute is equivalent to `object.property = "value"` — the value always arrives as a plain string. Always pass it through `this._convertProperty('<Type>', value)` in the setter so it is coerced to the declared type; never store or branch on the raw string yourself.

Example:

```js
/**
 * Description of the property.
 * @type {String}
 */
get title() {
    return this._title;
}
/**
 * Description of the property.
 * @type {String}
 */
set title(value) {
    this._title = this._convertProperty('String', value);
    this._showTitle();
}
/**
 * @ignore
 * @private
 */
_showTitle() {
    // render the new value
}
```

### `_convertProperty(type, value)` type coercion and callback support

`_convertProperty()` (`vendor/colibri/ui/src/06.UI/02.Common/01.Component.js`) is the single place that normalizes a property value to its declared `type`. Use it in every property setter, passing the exact type string:

- `'Boolean'` — the literal strings `'true'`/`'false'` (as they arrive from an HTML attribute) become real booleans.
- `'Number'` — a numeric string is parsed with `parseFloat`.
- `'String'` — the value is returned unchanged.
- `'Date'` — a string is converted with `.toDate()`.
- `'Object'` / `'Array'` — a literal string (for example `attr="{a: 1}"` or `attr="[1, 2]"`) is evaluated as a JS literal.
- `'Function'` — a function-like string is converted to a real function and kept as-is (not called), for callback-style attributes such as `onClick`.

Callback support (works for `'Number'`, `'Object'`, `'Array'` and for any type when a real JS function is assigned):

- If the incoming `value` is a string that looks like a function body, it is converted to a real function first; for `'Number'`, `'Object'` and `'Array'` it is then called immediately (`fn(this)`, or `fn(value, this)` for `'Number'`) so the attribute can compute its value from a callback written directly in the HTML template, for example `<Component count="(self) => self.parent.Children('list').length" />`.
- If `value` is already a function (assigned from JavaScript, not from a template string) and `type` is anything other than `'Function'`, it is invoked immediately as `value(value, this)` — so a component property can also be set to a computed callback directly in code, not only through a template string.
- If `value` is a plain object and `type !== 'String'`, it is treated as a multi-language value (`{ ru: '...', en: '...' }`) and passed through `Lang.Translate(value)`.

- The Colibri UI VS Code extension provides snippets for common component patterns. When the user asks to add one of these elements, use the matching snippet pattern below instead of inventing a different structure.

| Ask the user makes | Snippet prefix | File type | Produces |
| --- | --- | --- | --- |
| Add a private child reference | `child` | JavaScript | `this._name = this.Children('path');` |
| Add an event handler binding to a child | `event` | JavaScript | `this._x.AddHandler('Event', this.__xEvent, false, this);` |
| Add an event handler binding to `this` | `eventthis` | JavaScript | `this.AddHandler('Event', this.__thisEvent);` |
| Add an event handler method | `handler` | JavaScript | Documented `__eventName(event, args)` method |
| Add a read-only attribute (getter only) | `getattr` | JavaScript | Documented `get` accessor |
| Add an attribute (property) | `attr` | JavaScript | `get`/`set` pair plus `_show<Property>()` |
| Add the `value` property | `attrval` | JavaScript | `get`/`set value` pair plus `_showValue()` |
| Add store-binding rendering | `binding` | JavaScript | `__renderBoundedValues(data, path)` method |
| Add router match handling | `match` | JavaScript | `__processChangeOnRouteSwitch(match)` method |
| Add a language key reference | `#` | JavaScript, HTML | `#{key}` |
| Register a component event | `regm` | JavaScript | `_registerEvents()` override with `RegisterEvent(...)` |
| Add a component root template | `comp` | HTML | `<div ComponentName="App.Modules....">` |
| Add a `[data-object-name]` style block | `dom` | SCSS | `[data-object-name="name"] { }` |
| Add a shown-state style block | `shown` | SCSS | `&.app-component-shown { }` |
| Add a theme-conditional style block | `theme` | SCSS | `@if variable-exists(...) { }` |

### Targeting components in SCSS: by instance name vs. by type class

Every component's root element automatically carries two kinds of CSS hooks, set by the base `Colibri.UI.Component` (`vendor/colibri/ui/src/06.UI/02.Common/01.Component.js`):

- `data-object-name="<name>"` — set from the component's own instance `name` (`this._element.data('objectName', this._name)`, the same `name` used in the HTML `name="..."` attribute and in `Children('name')`). Use `[data-object-name="name"] { }` (the `dom` snippet) to style **one specific, uniquely-named instance**.
- `app-component-<type>` — added by every base Colibri UI class in its own constructor via `this.AddClass('app-component-<type>')` (for example `Colibri.UI.Icon` adds `app-component-icon`, `Colibri.UI.Link` adds `app-component-link`, `Colibri.UI.FlexBox` adds `app-component-flexbox`, `Colibri.UI.Button` adds `app-component-button`, form fields add `app-component-<x>-field`). A project component additionally carries its own full-name class (`app-modules-<module>-<namespace>-<component>`, see section 5.4). Use the `app-component-<type>` class to style **every instance of that type** without naming each one — this is the standard approach when a container has many same-type children (for example several menu links or list icons).

Both hooks are combined constantly throughout the codebase; pick based on cardinality:

- One specific, named child → `[data-object-name="menu"] { }`.
- Every child of a given type inside a named container, regardless of its `name` → nest the type class inside the `[data-object-name]` block: `[data-object-name="menu"] { .app-component-link { } }`.
- Only direct children of that type (not ones nested inside child sub-components) → prefix with `>`: `[data-object-name="menu"] { > .app-component-link { } }`.
- A specific instance where you also want to lock in its expected type (defensive/high-specificity styling) → combine both on the same selector: `[data-object-name="add-new"].app-component-link { }`.

## 8. Component Data Binding

- Every component has `binding` and `store` properties for connecting component data to a `Colibri.Storages.Store` path.
- Set `store` to the store that owns the binding path. If no store is specified, the component uses `App.Store`.
- Set `binding` to the store path whose data the component must observe. Multiple paths may be specified as a semicolon-separated list.
- When binding is assigned, the component loads the data with `AsyncQuery()` and registers an `AddPathHandler()` for the path. The component is updated whenever data at the path or its related parent/child path changes.
- Binding updates are rendered through `__renderBoundedValues(data, path)`.
- If a component does not override `__renderBoundedValues`, the standard component behavior assigns string data to the component's `value` property.
- Override `__renderBoundedValues(data, path)` when the component must transform, distribute or otherwise render structured data instead of assigning it directly to `value`.
- Keep binding handlers private and name them with the event-handler convention: `__` followed by a lowercase first word.
- Use `ReloadBinding()` when the component must explicitly refresh its current binding.

## 9. Validation

After every code change:

- Run the narrowest relevant test, linter or type checker available.
- Verify that new and modified public APIs have complete DocBlocks.
- Check that method names match their visibility rules.
- Do not consider a change complete if the focused validation fails.
