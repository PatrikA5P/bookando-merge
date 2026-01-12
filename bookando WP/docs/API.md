# Bookando REST API Documentation

## Base URL
```
/wp-json/bookando/v1/
```

## Authentication
All API endpoints require WordPress authentication via nonce or OAuth tokens.

### Nonce Authentication
Include `X-WP-Nonce` header with WordPress REST API nonce:
```javascript
headers: {
  'X-WP-Nonce': wpApiSettings.nonce
}
```

### Rate Limiting
- **Reads:** 100 requests/minute per user/IP
- **Writes:** 30 requests/minute per user/IP

---

## Core Endpoints

### Users (Customers)

#### List Users
```http
GET /bookando/v1/customers
```

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (max: 100)
- `search` (string): Search by name/email
- `status` (string): Filter by status (active, inactive)
- `tenant_id` (int): Filter by tenant

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "status": "active",
      "created_at": "2024-01-01 10:00:00"
    }
  ],
  "total": 150,
  "page": 1,
  "per_page": 20
}
```

#### Get User
```http
GET /bookando/v1/customers/{id}
```

#### Create User
```http
POST /bookando/v1/customers
```

**Body:**
```json
{
  "email": "user@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "language": "de",
  "phone": "+41 79 123 45 67"
}
```

#### Update User
```http
PUT /bookando/v1/customers/{id}
```

#### Delete User
```http
DELETE /bookando/v1/customers/{id}
```

---

### Appointments

#### List Appointments
```http
GET /bookando/v1/appointments
```

**Query Parameters:**
- `from` (date): Start date (YYYY-MM-DD)
- `to` (date): End date (YYYY-MM-DD)
- `status` (string): confirmed, pending, cancelled
- `employee_id` (int): Filter by employee
- `customer_id` (int): Filter by customer

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Consultation",
      "start_datetime": "2024-11-15 14:00:00",
      "end_datetime": "2024-11-15 15:00:00",
      "status": "confirmed",
      "customer": {
        "id": 5,
        "name": "John Doe"
      },
      "employee": {
        "id": 2,
        "name": "Jane Smith"
      }
    }
  ]
}
```

#### Create Appointment
```http
POST /bookando/v1/appointments
```

**Body:**
```json
{
  "title": "Consultation",
  "start_datetime": "2024-11-15 14:00:00",
  "end_datetime": "2024-11-15 15:00:00",
  "customer_id": 5,
  "employee_id": 2,
  "offer_id": 10,
  "notes": "First consultation"
}
```

---

### Employees

#### List Employees
```http
GET /bookando/v1/employees
```

#### Get Employee Availability
```http
GET /bookando/v1/employees/{id}/availability?from=2024-11-01&to=2024-11-30
```

**Response:**
```json
{
  "employee_id": 2,
  "available_slots": [
    {
      "date": "2024-11-15",
      "slots": [
        "09:00-10:00",
        "10:00-11:00",
        "14:00-15:00"
      ]
    }
  ]
}
```

---

### Offers (Services)

#### List Offers
```http
GET /bookando/v1/offers
```

**Response:**
```json
{
  "data": [
    {
      "id": 10,
      "title": "Haircut",
      "description": "Professional haircut service",
      "duration_minutes": 30,
      "price": 45.00,
      "currency": "CHF",
      "is_active": true
    }
  ]
}
```

---

### Finance

#### List Invoices
```http
GET /bookando/v1/finance/invoices
```

**Query Parameters:**
- `status` (string): draft, sent, paid, overdue
- `from` (date): Start date
- `to` (date): End date

#### Create Invoice
```http
POST /bookando/v1/finance/invoices
```

**Body:**
```json
{
  "customer_id": 5,
  "items": [
    {
      "description": "Consultation",
      "quantity": 1,
      "unit_price": 120.00
    }
  ],
  "due_date": "2024-12-01"
}
```

---

### Settings

#### Get Settings
```http
GET /bookando/v1/settings/{key}
```

**Example:**
```http
GET /bookando/v1/settings/general
```

**Response:**
```json
{
  "lang": "de",
  "timezone": "Europe/Zurich",
  "date_format": "d.m.Y",
  "time_format": "H:i"
}
```

#### Update Settings
```http
PUT /bookando/v1/settings/{key}
```

---

## Webhooks

Bookando can send webhook notifications for various events.

### Supported Events
- `appointment.created`
- `appointment.updated`
- `appointment.cancelled`
- `customer.created`
- `invoice.paid`

### Webhook Payload Example
```json
{
  "event": "appointment.created",
  "timestamp": "2024-11-10T14:30:00Z",
  "tenant_id": 1,
  "data": {
    "id": 123,
    "title": "Consultation",
    "start_datetime": "2024-11-15 14:00:00"
  }
}
```

---

## Error Handling

### Standard Error Response
```json
{
  "code": "invalid_request",
  "message": "Invalid customer_id provided",
  "data": {
    "status": 400
  }
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `429` - Too Many Requests (Rate Limit)
- `500` - Internal Server Error

---

## Capabilities & Permissions

### Required Capabilities by Endpoint
- **Customers:** `manage_bookando_customers`
- **Appointments:** `manage_bookando_appointments`
- **Employees:** `manage_bookando_employees`
- **Finance:** `manage_bookando_finance`
- **Settings:** `manage_options`

### Role Capabilities
- **Administrator:** All capabilities
- **Bookando Manager:** All bookando_* capabilities
- **Bookando Employee:** Read-only access to own appointments

---

## Multi-Tenancy

All requests are automatically scoped to the current tenant based on:
1. User's tenant assignment
2. `X-Bookando-Tenant-ID` header (for cross-tenant access with token)

### Cross-Tenant Access
```http
GET /bookando/v1/customers
Headers:
  X-Bookando-Tenant-ID: 2
  X-Bookando-Share-Token: abc123...
```

---

## Examples

### JavaScript/TypeScript
```typescript
// Using Fetch API
const response = await fetch('/wp-json/bookando/v1/customers', {
  method: 'GET',
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce,
    'Content-Type': 'application/json'
  }
});

const data = await response.json();
```

### PHP (Server-side)
```php
use WP_REST_Request;

$request = new WP_REST_Request('GET', '/bookando/v1/customers');
$request->set_param('per_page', 50);
$response = rest_do_request($request);
$data = $response->get_data();
```

---

## Changelog

### v1.0.0 (2024-11)
- Initial API release
- Core CRUD endpoints for all modules
- Multi-tenancy support
- Rate limiting
- Webhook support

---

## Support & Resources

- **GitHub:** https://github.com/PatrikA5P/bookando
- **Documentation:** `/docs/`
- **Issue Tracker:** https://github.com/PatrikA5P/bookando/issues
