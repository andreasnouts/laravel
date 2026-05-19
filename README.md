# **Coding Exercise**

## **Overview**

You will build an example on a Booking API from scratch. The exercise is designed to surface how you handle Laravel internals, architectural patterns, performance, and security.

## **The Scenario**

You are joining the backend team at **Clio Muse Tours**, a multilingual audio tour platform. Tour operators publish tours. Visitors book tours. Large group bookings trigger a manual review workflow that runs asynchronously. The system must be observable, secure, and able to handle booking volume during peaks.

## **Data Model & Migrations**

Create migrations and Eloquent models for the following schema. You decide the column types and constraints.

**Tables required:**

* `guides` name, email, status (`active` / `suspended`)  
* `bookings` belongs to a guide, has a status (`draft`, `pending_approval`, `approved`, `rejected`), a `total`, and a `notes` field 
* `booking_items`, belongs to a booking, has `tour_name`, `participants`, `price_per_person`
* `audit_logs`, records any model event with `event`, `payload`, `created_at`

## **Service Layer**

The following structure is provided as a starting point. You are free to adapt it, rename classes, introduce additional layers, or reorganize namespaces.

```
App\Contracts\BookingRepositoryInterface
App\Repositories\EloquentBookingRepository
App\Contracts\AuditLoggerInterface
App\Services\DatabaseAuditLogger
App\Services\NullAuditLogger
App\Services\BookingService
```

**Requirements:**

* `DatabaseAuditLogger` must be a Decorator around a core logging service, it should wrap an inner `LoggerInterface` (e.g. wrapping Laravel's `Log` facade behind an interface) and log to both the `audit_logs` table and the inner logger.

## **API Endpoints**

Build the following RESTful endpoints under the `/api/v1` prefix. Use `client_credentials` for authentication.
- List bookings for a guide (paginated)
- Create a booking with tour line items
- Update booking notes (only if status is draft)

**Requirements:**
* The `POST /bookings` endpoint must accept a nested array of `items`.
* Route model binding must resolve `Guide` and `Booking`, but the global scope on `Guide` must be bypassed for the admin `DELETE` route so suspended guides' bookings can still be managed  
* On `POST /bookings`, if the computed total exceeds **5 000 €**, the status must be set to `pending_approval` instead of `draft`, and a job must be dispatched  
* Update booking endpoint must return HTTP `409 Conflict` if the booking is not in `draft` status

## **The Approval Queue**

Create a job `App\Jobs\ProcessBookingApproval`.

**Requirements:**

* The job must be **unique**, dispatching it twice for the same booking ID must result in only one execution  
* The `handle()` method should simulate approval logic: if the booking total is under **10 000 €**, auto-approve it; otherwise set it to `pending_approval` and fire a `BookingRequiresManualApproval` event  
* Implement the `failed()` method, on final failure, set the booking status back to `draft` and log the exception via `AuditLoggerInterface`  

## **Optional: Tests**

Write the following tests using PHPUnit or PestPHP:

| Test |
| ----- |
| Creating a booking with total \> 5 000 € dispatches `ProcessBookingApproval` and sets status to `pending_approval` |
| `ProcessBookingApproval` is unique, dispatching it twice does not queue a duplicate |
| `PATCH /bookings/{booking}` returns 409 when booking is not in `draft` status |
| `NullAuditLogger` is resolved from the container in the `testing` environment |
| The booking total is correctly computed from its line items |

## **Documentation**

* A brief `INSTALL.md` with installation notes  
* A brief `NOTES.md` explaining any architectural decisions or trade-offs you chose
