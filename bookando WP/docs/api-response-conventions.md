# API Response Conventions

All REST modules should use `Bookando\Core\Api\Response` to generate payloads. The helper enforces
a consistent envelope so that clients can reliably consume backend responses.

## Success payloads

Successful responses always contain a `data` property with the requested payload and a `meta`
section that at least exposes `success: true`. Additional metadata (for pagination, feature flags,
etc.) can be appended as associative array entries.

```json
{
  "data": { "items": [] },
  "meta": { "success": true, "page": 1, "per_page": 25 }
}
```

Use the convenience helpers to express the desired HTTP semantics:

- `Response::ok($data, $meta = [], $status = 200)` for generic success payloads.
- `Response::created($data, $meta = [])` for HTTP 201 responses.
- `Response::updated($extra = [], $meta = [])` to acknowledge updates (adds `{ "updated": true }`).
- `Response::deleted($hard, $extra = [], $meta = [])` to acknowledge deletions (adds
  `{ "deleted": true, "hard": <bool> }`).
- `Response::noContent($meta = [])` for HTTP 204 responses without a body payload.

## Error payloads

Errors are wrapped in a dedicated structure that keeps the response envelope stable. The helper
accepts WordPress `WP_Error` instances, associative arrays, or plain strings.

```json
{
  "data": null,
  "error": {
    "code": "invalid_payload",
    "message": "Ungültige Ressourcendaten.",
    "details": { "status": 422, "reason": "format" }
  },
  "meta": {
    "success": false,
    "status": 422,
    "request_id": "abc-123"
  }
}
```

The HTTP status code is mirrored in `meta.status` and the `success` flag is always set to `false`.
You can pass additional metadata as the third argument to `Response::error()` or within the
`meta` key when using the array syntax. Any information returned by `WP_Error::get_error_data()` is
exposed as `error.details`.

### Validation & guard failures

- Validation errors should use the dedicated module validator (see
  `Bookando\Modules\customers\CustomerValidator`) and map to HTTP 422 with translated messages like
  `__('Pflichtfelder fehlen.', 'bookando')`. The integration test
  `tests/Integration/Rest/CustomersRoutesTest.php` documents the expected payloads.
- Guard failures originating from `RestModuleGuard` (`module_not_allowed`, `rest_license_*`, etc.)
  surface as localized strings via `_x()`/`__()` and **must not** expose raw internal identifiers.
  Refer to `tests/Integration/Rest/ResourcesPermissionsTest.php` for the canonical behaviour.
- When returning validation hints for the frontend, include machine-readable metadata in
  `error.details` (e.g. `{ "fields": ["email"] }`) so clients can map the response to i18n'd UI
  messages. Update the module’s `i18n.local.ts` whenever new detail keys appear.

## Testing

`tests/Unit/Core/Api/ResponseTest.php` covers the canonical success and error flows. When new helper
methods are introduced, extend those tests to document the expected structure.
