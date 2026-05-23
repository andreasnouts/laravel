# CliomuseTours — Architecture & Design Notes

## 1. Repository Pattern

All database interactions are abstracted behind interfaces (`IBookingRepository`, `IGuideRepository`). Controllers and services never interact with Eloquent directly — they depend on the interface, and the concrete Eloquent implementation is resolved by Laravel's Service Container via bindings in `AppServiceProvider`.

This means the persistence layer can be swapped (e.g. from MySQL to an external API) without touching any business logic.

---

## 2. Service Layer as the Single Owner of Business Logic

The `BookingService` is the only place where business rules live:

- Computing the booking total from line items
- Deciding whether the status should be `draft` or `pending_approval` based on the €5,000 threshold
- Dispatching the `ProcessBookingApproval` job
- Enforcing that notes can only be updated on `draft` bookings

Controllers are intentionally thin — they validate input, call the service, and return a response. Repositories are intentionally dumb — they persist and retrieve, nothing more.

---

## 3. Decorator Pattern for Audit Logging

The audit logging system is built as a decorator chain around `IAuditLogger`:

```
DatabaseAuditLogger        (decorator — writes to audit_logs table + delegates)
    └── LaravelLogger      (inner — writes to Laravel's log file)
```

`DatabaseAuditLogger` implements `IAuditLogger` and wraps any other `IAuditLogger` implementation. This means the logging chain is composable — a third logger (e.g. a queue-based logger) can be added by wrapping again without modifying any existing class.

---

## 4. Null Object Pattern via NullAuditLogger

`NullAuditLogger` implements `IAuditLogger` but does nothing. It exists so that in test environments, audit logging can be silenced without conditionals or mocking throughout the codebase. The consumer of `IAuditLogger` never needs to check whether logging is enabled — it just calls `log()` and the null implementation handles the rest silently.

Typical test usage:

```php
// Silence logging entirely
new NullAuditLogger()

// DB audit logs without log file noise
new DatabaseAuditLogger(new NullAuditLogger())
```

---

## 5. Enums over Database Lookup Tables

An early decision was made to use lookup tables (`guide_statuses`, `booking_statuses`) with foreign keys. These were replaced with PHP 8.1 backed enums (`GuideStatus`, `BookingStatus`).

**Reasoning:** status values for guides (`active`, `suspended`) and bookings (`draft`, `pending_approval`, `approved`, `rejected`) are fixed by business logic. Adding or changing a status requires a code change regardless — a DB row edit alone would not be sufficient. Enums make the allowed values explicit, self-documenting, and statically analysable, and eliminate unnecessary joins.

---

## 6. `hash_id` as the Public-Facing Identifier

Auto-increment integer IDs are never exposed in API URIs. Instead, each `Guide` and `Booking` has a `hash_id` (9-character slug generated via the `HasHashID` trait) used in all routes.

**Reasoning:** exposing sequential integer IDs leaks information about record counts and makes enumeration attacks trivial. `hash_id` values are opaque to the consumer while remaining stable and URL-safe.

`getRouteKeyName()` is overridden on both models so Laravel's route model binding resolves by `hash_id` automatically.

---

## 7. Domain Exceptions Mapped to HTTP Responses in the Handler

Business rule violations throw domain exceptions (`GuideNotFoundException`, `BookingNotEditableException`) that extend `RuntimeException`. These are not HTTP-aware — they carry no status codes.

The HTTP meaning is assigned once, in `App\Exceptions\Handler.php`, via `$this->renderable()`. This keeps controllers free of try/catch blocks and ensures consistent error responses across the entire application regardless of where the exception originates.

---

## 8. Validation Rule `exists:` Removed from Form Requests

Laravel's `exists:table,column` validation rule hits the database directly, bypassing the repository entirely. To preserve the repository pattern boundary, guide existence is validated manually in `BookingService` via `IGuideRepository::findByHashId()`. A `GuideNotFoundException` is thrown if the guide does not exist, which the handler maps to a 404 response.

---

## 9. Job Uniqueness via `ShouldBeUnique`

`ProcessBookingApproval` implements `ShouldBeUnique` with `uniqueId()` keyed on `booking->id`. Dispatching the same job twice for the same booking results in only one execution — the second dispatch is silently dropped by the queue system using a cache lock.

This prevents race conditions if the job is dispatched multiple times due to retries or duplicate requests.

---

## 10. `audit_logs` is Append-Only

The `AuditLog` model sets `$timestamps = false` because the table has no `updated_at` column — audit records are immutable by design. `created_at` is written directly via `$fillable` on insert.

Audit logs are never updated or deleted — they are a permanent record of what happened and when.

---

## 11. Repository and Logger Injected into the Job Constructor

`ProcessBookingApproval` receives both `IBookingRepository` and `IAuditLogger` via its constructor. Laravel's queue worker resolves these through the Service Container when picking up the job, so the same bindings registered in `AppServiceProvider` apply.

The `failed()` method — called after all retries are exhausted — has full access to these injected dependencies, resets the booking status to `draft`, and logs the failure via `IAuditLogger`.

---

## 12. Laravel Passport v12 with Client Credentials Grant

Authentication uses OAuth 2.0 `client_credentials` grant via Laravel Passport v12 (v13 requires PHP 8.2, which is above the project's PHP 8.1 constraint).

This grant type is machine-to-machine — there is no user involved. Clients authenticate with a `client_id` and `client_secret` to obtain a Bearer token, which is passed in the `Authorization` header on every API request. As a result, `auth()->user()` is always `null` in this context, which is consistent with the nullable `user_id` on `audit_logs`.

Routes are protected by Passport's `CheckClientCredentials` middleware, registered as the `client` alias in `Kernel.php`.
