# CliomuseTours — Installation Guide

## Prerequisites

- PHP 8.1
- Composer
- MySQL
- Apache with Virtual Host support (XAMPP or equivalent)
- Git

---

## 1. Clone the Repository

```bash
git clone <repository-url> cliomusetours
cd cliomusetours
```

---

## 2. Install Dependencies

```bash
composer install
```

This will install all dependencies including Laravel Passport v12.

---

## 3. Environment Setup

Copy the example environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Update the following values in your `.env`:

```env
APP_NAME=CliomuseTours
APP_URL=http://cliomusetours.io

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cliomusetours
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

QUEUE_CONNECTION=database
```

---

## 4. Apache Virtual Host Setup

Add the following to your Apache `httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName cliomusetours.io
    DocumentRoot "path/to/cliomusetours/public"

    <Directory "path/to/cliomusetours/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add the following to your `hosts` file (`C:\Windows\System32\drivers\etc\hosts` on Windows):

```
127.0.0.1   cliomusetours.io
```

Restart Apache after making these changes.

---

## 5. Database Setup

Create the database, then run all migrations and seed initial data:

```bash
php artisan migrate:fresh --seed
```

This will create all tables and populate them with two guides and sample bookings.

---

## 6. Queue Setup

Jobs related tables are included in the migrations above. 

To process queued jobs locally:

```bash
php artisan queue:work
```

---

## 7. Passport Setup

No need to publish and run Passport migrations, as they are included in the project migrations.

Generate Passport encryption keys:

```bash
php artisan passport:install
```

When asked whether to create personal access and password grant clients, answer **no** — only the client credentials grant is used.

Create a client credentials client:

```bash
php artisan passport:client --client
```

Give it a name when prompted (e.g. `CliomuseTours Client`). 
**Save the outputted `client_id` and `client_secret`** — the secret is only shown once and you 
will need it in step 8 below. If lost you can obtain it from oauth_clients DB table.

---

## 8. Verify Installation

Obtain an access token:

```bash
curl -X POST http://cliomusetours.io/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "your-client-id",
    "client_secret": "your-client-secret",
    "scope": ""
  }'
```

Use the returned token to hit a protected endpoint:

```bash
curl -X GET http://cliomusetours.io/api/v1/guides/{guide_hash_id}/bookings \
  -H "Authorization: Bearer <access_token>"
```

A paginated list of bookings should be returned.

---

## API Endpoints

All endpoints are prefixed with `/api/v1` and require a valid Bearer token.

| Method  | Endpoint                              | Description                        |
|---------|---------------------------------------|------------------------------------|
| GET     | `/api/v1/guides/{hash_id}/bookings`   | List bookings for a guide          |
| POST    | `/api/v1/bookings`                    | Create a booking with line items   |
| PATCH   | `/api/v1/bookings/{hash_id}/notes`    | Update booking notes (draft only)  |

### POST /api/v1/bookings — Sample Request Body

```json
{
    "guide_hash_id": "abc123def",
    "notes": "Morning group, meet at main entrance.",
    "items": [
        {
            "tour_name": "Acropolis",
            "participants": 3,
            "price_per_person": 100.00
        },
        {
            "tour_name": "Mystra",
            "participants": 4,
            "price_per_person": 125.00
        }
    ]
}
```

> If the computed total exceeds €5,000 the booking status will be set to `pending_approval` and a background job will be dispatched automatically.

---

### Testing API Endpoints with Postman
Find a json file in the route directly of the solution named:
`ClioMuseTours.postman_collection.json` and import it to your Postman instance.

Within that collection you should find 5 useful end point Requests.
- vhost-check (just checks vhost is set up OK)
- Get oAuth Token (returns a token to be used in subsequent calls). You should fill client_id and client_secret in the request body.
- Get guide Bookings (You should obtain a valid Guide hash_id from the Database and replace the one currently found in the request body)
- Update Booking Notes (same as above, you should obtain a valid Booking hash_id from the Database, or use the next endpoint to create a Booking which will respond with the new Booking Details, you can then use the hash_id of the new Booking)
- Create New Booking  (Will create a new booking exceeding the €10,000 Threshold so this will cause to it to be sent to the queue for processing 
but then its status will be set (remain) to `Pending Approval` and the event 
`BookingRequiresManualApproval` will be raised.  

---
**An honest confession:** Special thanks to AI tools for helping me **format** this .md file nicely :)
