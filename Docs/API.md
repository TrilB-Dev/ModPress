# REST API

Add REST routes only when the plugin needs an HTTP interface. Register them on `rest_api_init` from a plugin-owned route class or from an implementation of `RestRouteProviderInterface`.

Use a unique namespace made from the plugin slug and a version, for example:

```text
/wp-json/modpress/v1
```

## Permissions

Every route must define a `permission_callback`. Public read routes may use `__return_true` only when their data is intentionally public. Protected routes should check the capability appropriate to the feature through `PermissionHelper::can()` or a domain-specific permission callback. Do not rely on obscurity of the route.

State-changing routes must validate the current user, capabilities, request values, and any object-level permissions before changing data.

## Example Endpoints

| Method | Route | Purpose |
| --- | --- | --- |
| GET | `/status` | Return plugin or integration health information |
| GET | `/items` | List public plugin resources |
| POST | `/items` | Create a resource for an authorized user |
| GET | `/items/{id}` | Retrieve one resource |
| PATCH | `/items/{id}` | Update one resource |
| DELETE | `/items/{id}` | Delete one resource |

These are examples only. Replace `items` with the resource name owned by the plugin and remove routes that do not apply.

Collection routes commonly accept:

- `per_page`: integer from 1 to 100, default 20
- `page`: integer from 1, default 1
- `search`: an optional text search value
- feature-specific filters documented by the owning route

Validate pagination bounds and filters with `RequestHelper` before passing them to a query. Use `SanitizationHelper` for payload values and WordPress query APIs for persistence.

## Payload Example

```json
{
  "name": "Example resource",
  "enabled": true
}
```

Document required fields, accepted types, defaults, validation rules, and authorization requirements for each real payload. Never trust request data simply because it came through the REST API.

## Response Shape

Successful responses may use the shared `Response::success()` envelope when the plugin exposes that helper:

```json
{
  "success": true,
  "data": {
    "id": 42,
    "name": "Example resource"
  }
}
```

Use HTTP 201 for successful creation, 200 for reads and updates, and 204 when a delete has no response body. Errors should be `WP_Error` instances with a stable machine-readable code and an appropriate status code, for example:

```json
{
  "code": "not_found",
  "message": "Resource not found.",
  "data": { "status": 404 }
}
```

## Programmatic API

Keep domain operations in services that can be called by both REST callbacks and other plugin code. Do not make internal code issue HTTP requests to its own routes:

```php
use ModPress\API\ResourceService;

$items = ResourceService::list([
    'posts_per_page' => 10,
    'paged' => 1,
    's' => 'example',
]);

  $item = ResourceService::get(42);
```

The service name and methods are examples. Keep response formatting in a reusable formatter when multiple callers need the same representation.

## Extension Hooks

Define narrowly scoped, documented hooks when another plugin needs to change a payload or observe a domain event. Use the plugin slug as the prefix, for example `modpress_resource_payload` or `modpress_resource_saved`. Apply authorization before exposing data, and document whether hooks run before or after persistence.

## Adding Routes

An internal or external extension should implement `RestRouteProviderInterface` and register routes from its `register_rest_routes()` method. Keep route callbacks thin: validate request values, call a domain service, and return `Response::success()` or a `WP_Error`.

Use schema definitions for request arguments where the plugin provides them. Validation helpers should return a consistent result containing validity, errors, and sanitized values, or return `WP_Error` directly when that matches the surrounding API.
