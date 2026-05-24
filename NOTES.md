# CliomuseTours — Architecture & Design Notes

## 1. Repository Pattern

All database interactions are abstracted behind interfaces (`IBookingRepository`, `IGuideRepository`). Controllers and services never interact with Eloquent directly — they depend on the interface, and the concrete Eloquent implementation is resolved by Laravel's Service Container via bindings in `AppServiceProvider`.

This means the persistence layer can be swapped (e.g. from MySQL to an external API) without touching any business logic.

---

## 2. Service Layer Responsible for the Business Logic

The `BookingService` is the only place where business rules live:

- Computing the booking total from line items
- Deciding what the booking status should be based on the €5,000 threshold
- Dispatching the `ProcessBookingApproval` job
- Enforcing that notes can only be updated on `draft` bookings

Controllers are intentionally "thin". 
They validate input (not to the full Laravel potential as this would "disrespect" 
the Service-Repository pattern, more on that below, point 8), call the service, 
and return a response. 
Repositories are intentionally "dumb" — they persist and retrieve data, nothing more.

---

## 3. Decorator Pattern for Audit Logging

The audit logging system is built as a decorator around `IAuditLogger`:

```
DatabaseAuditLogger        (decorator — writes to audit_logs table + delegates)
    |---LaravelLogger      (inner — writes to Laravel's log file)
```

`DatabaseAuditLogger` implements `IAuditLogger` and wraps any other `IAuditLogger` implementation. 
This means the logging chain is composable — a third logger (e.g. a queue-based logger) 
can be added by wrapping again without modifying any existing class.

---

## 4. Null Object Pattern via NullAuditLogger

`NullAuditLogger` implements `IAuditLogger` but does nothing. 
It exists so that in specific environments (e.g. testing) audit logging can be silenced.

---

## 5. Enums over Database Lookup Tables

An early decision was made to use lookup tables (`guide_statuses`, `booking_statuses`) 
with foreign keys. 
However, I decided to replace them with DB enums backed with PHP 8.1 enums (`GuideStatus`, `BookingStatus`).

**Reasoning:** status values for guides (`active`, `suspended`) 
and bookings (`draft`, `pending_approval`, `approved`, `rejected`) are fixed by Business logic. 
Adding or changing a status requires a code change regardless as it would imply change in the
Business logic. 
DB Enums make the allowed values explicit, self-explanatory and eliminate unnecessary joins.

---

## 6. `hash_id` as the Public-Facing Identifier

It is not a good practice to exposed in API URIs Auto-increment integer IDs. 
Instead, each `Guide` and `Booking` has a `hash_id` (9-character slug generated via the `HasHashID` trait) used in all routes.

**Reasoning:** exposing sequential integer IDs leaks information about record counts and 
makes attacks trivial. `hash_id` values are opaque to the consumer while remaining stable and URL-safe.

Due to this decisions, `getRouteKeyName()` is overridden on both models `Guide` and `Booking`, 
so Laravel's route model binding resolves by `hash_id` automatically.

---

## 7. Domain Exceptions Mapped to HTTP Responses in the Handler

Initially generic `\Exceptions` were thrown. Although I know the best approach is to have 
your custom Domain based exception (even layered as in HttpExceptions, BusinessExceptions, DB, Core, etc...).

Business rule violations throw Domain exceptions (`GuideNotFoundException`, `BookingNotEditableException`) that extend `RuntimeException`. 
These are not HTTP-aware — they carry no status codes.

The HTTP meaning is assigned once, in `App\Exceptions\Handler.php`, via `$this->renderable()`. 

This can assist in keeping controllers free of try/catch blocks in most cases. It surely 
ensures consistent error responses across the entire application.

---

## 8. Validation Rule `exists:` Removed from Form Requests

Laravel's `exists:table,column` validation rule hits the database directly, 
bypassing the repository entirely. 
To preserve the repository pattern boundary, guide existence is validated manually 
in `BookingService` via `IGuideRepository::findByHashId()`. 
A `GuideNotFoundException` is thrown if the guide does not exist, which the handler 
maps to a 404 response.

---

## 9. Job Uniqueness via `ShouldBeUnique`

`ProcessBookingApproval` implements `ShouldBeUnique` with `uniqueId()` 
keyed on `booking->id`. 
Dispatching the same job twice for the same booking results in only one execution 
— the second dispatch is silently dropped by the queue system.

---

## 10. `audit_logs` is Append-Only

The `AuditLog` model sets `$timestamps = false` because the table has no `updated_at` column — audit records are immutable by design. `created_at` is written directly via `$fillable` on insert.

Audit logs are never updated or deleted — they are a permanent records of what happened at the
time when it happened.

---

## 11. Repository and Logger Injected into the Job Constructor

`ProcessBookingApproval` receives both `IBookingRepository` and `IAuditLogger` via its 
constructor. Although Laravel's queue worker resolves these through the Service Container 
when picking up the job -using the same bindings registered in `AppServiceProvider`- however
since I do have both concrete instances available at the time when the job is dispatched I chose
to explicitly pass them in the Job constructor.

The `failed()` method — called after all retries are exhausted — has full access to these 
injected dependencies -they are protected member properties of the Job instance-, 
resets the booking status to `draft`, and logs the failure via `IAuditLogger`.

---

## 12. Laravel Passport v12 with Client Credentials Grant

Authentication uses OAuth 2.0 `client_credentials` grant via Laravel Passport v12 
(v13 requires PHP 8.2, which is above my current home PC set up of PHP 8.1).

This grant type is machine-to-machine — there is no user involved. 
Clients authenticate with a `client_id` and `client_secret` to obtain a Bearer token, 
which is passed in the `Authorization` header on every API request. 
As a result, `auth()->user()` is always `null` in this context, 
which is consistent with the nullable `user_id` on `audit_logs`.

Routes are protected by Passport's `CheckClientCredentials` middleware, 
registered as the `client` alias in `Kernel.php`.

---

## * Little Extra Work
There is a listener for handling the `BookingRequiresManualApproval` event. For the time being it 
only Audits the fact the there is a Booking that requires manual Approval, however I did it to show
that the listener could possibly insert the event (eg. through an API call) to an Administrator
system that deals with manual Booking Approvals, or it could even add a message to Slack, etc...

---

## Notes on the Optional Tests
As I've told you in the interview we currently neither do TDD, nor write any random Test whatsoever! 
I have had a brief attempt to see how you set it up at some point with a colleague, 
but it was only brief :(
So I intentionally decided not complete the Optional "Tests" section as it would be a total cheat 
since in order to complete it I would have to use "some" AI help. This will give you a false idea
for myself! I'd rather be honest so a) you know what to expect and b) I don't trick myself if 
we both decide we want to work together!
However, having done TDD development a few years back working with the .net Framework, 
plus my current experience and understanding I do not think that it ll take me more than a few hours 
to understand how to set up the Test Environment in a Laravel/PHP world.

To compensate (just a bit) I've included a postman collection offering some testing to 
confirm the endpoints do what they are supposed to do, plus checking that business rules 
are applied eg. the total amount threshold causing Booking to be queued for approval and then 
the 10.000 threshold causing status to be set to "pending_approval". This is also why I decided 
to write the Listener mentioned above to handle the `BookingRequiresManualApproval` event, so that 
I can at least AuditLog and ensure event was raised and listener "recorded" it...
