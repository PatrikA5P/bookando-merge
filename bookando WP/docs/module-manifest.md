# Module Manifest (`module.json`)

Every module in `src/modules/<slug>` defines its capabilities via a `module.json` manifest. The schema in [`docs/module-schema.json`](./module-schema.json) keeps the structure aligned across modules and is enforced in CI through `npm run validate:modules`.

## Required fields

The following table highlights the minimum attributes that must be present. All keys are validated by the JSON Schema; the table focuses on attributes that repeatedly caused drift in the past.

| Field | Type | Notes |
| --- | --- | --- |
| `slug` | string (kebab-case) | Directory name; used in REST routes and capability derivation. |
| `plan` | enum | `starter`, `pro`, `academy`, `enterprise`. |
| `group` | enum | Use one of: `core`, `operations`, `crm`, `offers`. Controls navigation grouping. |
| `menu_icon` | string | Dashicons identifier. |
| `menu_position` | integer | WordPress menu order. Use plain integers (no strings). |
| `name`, `description` | object | Must contain `default`, `de`, `en`. Additional locales allowed. |
| `alias` | object | Must contain `de` and `en`; optional `default`. |
| `dependencies`, `tabs` | array | Empty array when unused to simplify validation. |
| `is_submodule`, `parent_module` | boolean/null|string | `parent_module` stays `null` for top-level modules. |

See the schema for the full list of required boolean capability flags (`tenant_required`, `supports_offline`, …).

## Menu groups

Menu grouping is now centralised. Pick the value that matches the module domain:

- `core` – shared administration & platform setup
- `operations` – scheduling, finance and daily operations
- `crm` – customer data and communication
- `offers` – catalogues, products and sales enablement

Additions require updating the schema and generator to keep validation green.

## CI validation

```bash
npm run validate:modules
```

The command runs as part of `npm test` and validates each manifest against `docs/module-schema.json`. Schema violations block the pipeline with a detailed error per module.
