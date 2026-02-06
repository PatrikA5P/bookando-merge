# Phase 2 — Critical Flows (End-to-End)

## Flow 1: Appointment Booking (Creation)

### Happy Path
```
UI (Vue Calendar View)
  → POST /bookando/v1/appointments
  → RestModuleGuard::for('appointments')
    → TenantManager::resolveFromRequest()
    → Gate::evaluate() [login + nonce + capability]
  → Appointments/RestHandler::appointments() [src/modules/Appointments/RestHandler.php]
    → Validate: customer_id, employee_id, service_id, location_id, starts_at, ends_at
    → Parse datetime with wp_timezone(), convert to UTC
    → Store client_tz for display
    → Appointments/Model::createAppointment() [src/modules/Appointments/Model.php]
      → BaseModel::insert() [src/Core/Model/BaseModel.php:190]
        → TenantManager::currentTenantId() — enforced
        → $wpdb->insert() with auto tenant_id
    → Return appointment with ID + status 'pending'
  → Optional: trigger calendar sync (Google/MS/Apple)
  → Optional: trigger notification (email/SMS)
```

### Edge Cases
1. **Missing tenant context**: `BaseModel::insert()` throws `RuntimeException('Tenant context missing on insert')` at line 193
2. **Overlapping appointments**: NO built-in double-booking prevention in Appointments module — only shift conflict detection exists in `Workday/ShiftService::detectConflicts()`. Appointments rely on external validation only.
3. **Timezone DST transition**: Client submits time during DST change (e.g., 2:30 AM during spring-forward). `DateTimeImmutable` handles this correctly when timezone is specified, but edge case if `client_tz` is wrong.
4. **Max participants exceeded**: Event-based bookings check `current_participants < max_participants` in `Offers/CalendarViewController.php`, but this is a read-time check, not a write-time atomic constraint.
5. **Invalid datetime format**: RestHandler::parseDateTime() may return null/false if format is unexpected
6. **Employee not available on requested date**: No server-side validation against employee working hours in appointment creation flow (checked only in scheduling/roster context)
7. **Service inactive**: `offers.status` check not enforced at booking time
8. **Past date booking**: No explicit validation preventing booking in the past
9. **Concurrent bookings for same slot**: No row-level locking or optimistic concurrency — race condition possible
10. **API key auth without nonce**: API key users skip nonce check but still need write capability

### Failure Modes
- DB insert failure → RuntimeException propagated as 500
- Tenant context missing → RuntimeException before query
- Calendar sync failure → silently logged, appointment still created (calendar is eventual-consistent)
- Payment required but not processed → appointment created without payment linkage (decoupled)

### Must Be Logged/Audited
- Appointment creation with: customer_id, employee_id, tenant_id, timestamps, IP
- Calendar sync attempts and failures
- Any authorization bypass or rate limit hit

### Required Tests
- **Unit**: Model::createAppointment() with valid data
- **Unit**: Tenant enforcement on insert (missing tenant → exception)
- **Integration**: Full REST flow POST /appointments with auth
- **Integration**: Concurrent booking same slot (race condition)
- **E2E**: Book appointment via UI calendar

---

## Flow 2: Availability & Double-Booking Prevention

### Happy Path
```
UI (Calendar Month/Week/Day View)
  → GET /bookando/v1/offers/calendar/{view}
  → Offers/CalendarViewController [src/modules/Offers/CalendarViewController.php]
    → getMonthView/getWeekView/getDateView()
    → Queries offers with: booking_enabled=1, status='active'
    → Checks: current_participants < max_participants
    → Groups by date with availability slots
  → Returns available slots to UI

For Shift Conflicts:
  Workday/ShiftService::detectConflicts() [src/modules/Workday/Services/ShiftService.php]
    → 3 checks:
      1. Overlapping shifts: SQL WHERE with time range overlap logic
      2. Approved absences: date BETWEEN start_date AND end_date
      3. Rest period: 11-hour minimum between consecutive shifts
```

### Edge Cases
1. **TOCTOU race**: Availability read and booking write are not atomic — two users see "1 slot left", both book
2. **DST day with 23 or 25 hours**: Shift duration calculation in DutySchedulerService may miscalculate if using naive time math
3. **Midnight-crossing shifts**: `end_time < start_time` handled by adding 24h in DutySchedulerService::calculateDuration()
4. **Cancelled shift still blocking**: `WHERE status NOT IN ('cancelled', 'draft')` in conflict detection — correct
5. **Recurring pattern on DST boundary**: `recurrence_pattern` JSON may produce wrong instances
6. **Cross-timezone availability**: Employee in Berlin, client in New York — availability shown in whose timezone?
7. **Vacation balance depletion**: VacationRequestService checks overlap but not balance remaining
8. **Max weekly hours boundary**: DutySchedulerService uses `hoursUsed[employeeId] + (duration/60) <= maxWeeklyHours`

### Failure Modes
- SQL errors in complex JOINs → empty results (silent failure)
- TenantManager returns wrong tenant → data from wrong tenant's schedule

### Must Be Logged/Audited
- Conflict detection results for shift assignments
- Availability queries with tenant context

### Required Tests
- **Unit**: detectConflicts() with overlapping, adjacent, non-overlapping shifts
- **Unit**: Rest period calculation (exactly 11h, 10h59m, 11h01m)
- **Unit**: Midnight-crossing shift duration
- **Integration**: CalendarViewController returns correct availability
- **E2E**: Book last available slot concurrently (2 browser sessions)

---

## Flow 3: Payment Creation + Webhook Processing

### Happy Path
```
UI (Checkout)
  → POST /bookando/v1/finance/payment/create
  → Finance/PaymentRestHandler::createPayment() [src/modules/Finance/PaymentRestHandler.php]
    → GatewayManager::getGateway($gatewayId) [src/modules/Finance/Gateways/GatewayManager.php]
    → gateway->createPayment($amount, $currency, $metadata)
      → AbstractGateway::formatAmount() converts float→int cents
      → Stripe: Stripe\Checkout\Session::create()
      → PayPal: Orders API
      → Mollie: Payments API
    → Returns checkout URL / session ID
  → Client redirects to payment provider

  Provider Callback:
  → POST /bookando/v1/webhooks/payments/{gateway}
  → Finance/PaymentWebhookHandler::handle() [src/modules/Finance/PaymentWebhookHandler.php]
    → Read raw php://input
    → gateway->verifyWebhookSignature($payload, $headers)
    → gateway->handleWebhook($data)
    → Normalize to event_type: payment.success / payment.failed / refund.completed
    → handlePaymentSuccess(): update payment record, fire do_action('bookando_payment_success')
    → handlePaymentFailed(): update payment record, fire do_action('bookando_payment_failed')
```

### Edge Cases
1. **Webhook replay**: Same webhook delivered multiple times — NO idempotency key tracking in DB. Payment status could be updated multiple times (idempotent if same status, but side effects like notifications may fire multiple times)
2. **Zero-decimal currencies** (JPY, KRW): `AbstractGateway::formatAmount()` correctly handles via currency whitelist
3. **Minimum amount check**: Stripe enforces 50 cents minimum in StripeGateway
4. **PayPal webhook signature verification**: NOT fully implemented (marked TODO in code)
5. **Mollie/Klarna no signature**: Security relies on endpoint obscurity only
6. **Race condition**: Payment created but webhook arrives before createPayment() response returns
7. **Currency mismatch**: Client sends EUR, gateway configured for CHF
8. **Partial capture**: Supported by Stripe (via PaymentIntent), not all gateways
9. **Gateway timeout**: Network failure during createPayment() — payment may be created at provider but not recorded locally
10. **Tenant context in webhook**: Webhooks are public endpoints — tenant must be resolved from payment metadata, not from auth context

### Failure Modes
- Gateway SDK exception → caught, logged, returned as error response
- Webhook signature failure → 403 response
- DB insert failure → payment exists at provider but not in local DB (reconciliation gap)
- Gateway not enabled for tenant → error before payment creation

### Must Be Logged/Audited
- Every payment creation attempt with amount, currency, gateway, tenant_id
- Every webhook received with event_type, gateway, raw payload hash
- Every refund with original payment reference
- Gateway configuration changes

### Required Tests
- **Unit**: formatAmount()/parseAmount() for all currency types
- **Unit**: Webhook signature verification per gateway
- **Integration**: Full payment flow mock (create → webhook → status update)
- **Integration**: Webhook replay idempotency
- **E2E**: Stripe test mode checkout

---

## Flow 4: Invoice/Accounting Computations

### Happy Path
```
UI (Finance Module)
  → POST /bookando/v1/finance/invoices
  → Finance/RestHandler::saveInvoice() [src/modules/Finance/RestHandler.php]
    → Finance/StateRepository::upsertInvoice() [src/modules/Finance/StateRepository.php]
      → recalculateTotals():
        → For each line item: subtotal += item.quantity * item.unit_price
        → For each line item: taxTotal += item.total * (item.tax_rate / 100)
        → invoice.subtotal = round(subtotal, 2)
        → invoice.tax_total = round(taxTotal, 2)
        → invoice.total = round(subtotal + taxTotal, 2)
      → $wpdb->insert/update with tenant_id
```

### Edge Cases
1. **Float arithmetic precision**: `round($subtotal, 2)` — PHP floats can cause penny rounding errors (e.g., 0.1 + 0.2 ≠ 0.3)
2. **Tax rate with many decimals**: Swiss VAT 8.1% or 2.6% — intermediate rounding per line vs. on total gives different results
3. **Credit note against non-existent invoice**: No foreign key or existence check in code
4. **Discount code stacking**: Multiple discount codes on one invoice — interaction logic unclear
5. **Currency precision for non-decimal currencies**: JPY stored as float with round(x, 2) — should be round(x, 0)
6. **Invoice total doesn't match payment amount**: No automatic reconciliation
7. **Ledger export currency mixing**: Multiple currencies in one export period
8. **Tax rate change mid-invoice-period**: `tax_rate` per line item but no versioning

### Failure Modes
- Float precision errors accumulate across many line items
- DB failure during multi-table insert → partial invoice data
- Export with wrong tenant → data leak

### Must Be Logged/Audited
- Invoice creation/modification with all line items
- Credit note creation with reference to original invoice
- Ledger export execution with date range and tenant

### Required Tests
- **Unit**: recalculateTotals() with known-good expected values
- **Unit**: Float precision edge case (0.1 + 0.2 scenario with many items)
- **Unit**: Zero-decimal currency handling in invoices
- **Integration**: Create invoice → create credit note → verify totals
- **Integration**: Ledger export with tax breakdown

---

## Flow 5: Employee Permissions & Role Checks

### Happy Path
```
UI (Employee Detail)
  → PUT /bookando/v1/employees/{id}
  → RestModuleGuard::for('employees')
    → Gate::evaluate(): requires manage_bookando_employees
  → EmployeeAuthorizationGuard::guardPermissions() [src/modules/Employees/Handlers/EmployeeAuthorizationGuard.php]
    → For non-GET: requires manage_bookando_employees capability
  → EmployeeAuthorizationGuard::canWriteRecord($employeeId, $request)
    → Dev bypass check
    → manage_bookando_employees check
    → Self-access: Gate::isSelf($employeeId) + nonce verification
  → Employees/RestHandler processes update
```

### Edge Cases
1. **Self-edit escalation**: Employee edits own record — can they change their own role/permissions?
2. **Dev bypass in production**: `Gate::devBypass()` correctly checks `WP_ENVIRONMENT_TYPE !== 'production'` but defaults to 'production' if unset
3. **Admin without Bookando cap**: WP admin has `manage_options` but not specific `manage_bookando_employees` — Gate::canManage() allows this via `|| current_user_can('manage_options')`
4. **API key with limited permissions**: API keys have `permissions` JSON field but not enforced in EmployeeAuthorizationGuard
5. **Cross-tenant employee access**: Employee in tenant A tries to read employee in tenant B — BaseModel tenant scoping prevents this
6. **Deleted employee still has WP user**: User sync gap
7. **Concurrent role change**: Admin removes capability while employee is mid-session
8. **Bulk operations**: BulkEmployeeHandler may skip per-record authorization

### Failure Modes
- Missing capability → 403 WP_Error
- Invalid nonce → 401 WP_Error
- Tenant mismatch → empty result (row not found for other tenant)

### Must Be Logged/Audited
- All employee record modifications
- Role/capability changes
- Self-access events
- Dev bypass usage (already logged)

### Required Tests
- **Unit**: EmployeeAuthorizationGuard with various role combinations
- **Unit**: Gate::isSelf() with matching/non-matching external_id
- **Integration**: CRUD as manager, employee (self), unauthorized user
- **Integration**: Cross-tenant employee access blocked

---

## Flow 6: Multi-Tenancy Isolation

### Happy Path
```
Any data operation:
  → TenantManager::currentTenantId() [src/Core/Tenant/TenantManager.php:15]
    → Returns cached $cachedTenantId or resolves from request
  → BaseModel::fetchAll/fetchOne/paginate [src/Core/Model/BaseModel.php]
    → MultiTenantTrait::applyTenant() [src/Core/Model/Traits/MultiTenantTrait.php:9]
      → Wraps SQL: SELECT * FROM ($original_sql) as t WHERE t.tenant_id = %d
  → BaseModel::insert() [line 190]: auto-sets tenant_id if missing
  → BaseModel::update() [line 215]: WHERE id=%d AND tenant_id=%d
  → BaseModel::delete() [line 245]: WHERE id=%d AND tenant_id=%d
```

### Edge Cases
1. **Background job context**: QueueManager processes jobs via WP-Cron. `TenantManager::currentTenantId()` may resolve to fallback tenant 1 in cron context if job payload doesn't carry tenant_id
2. **Export/Import**: Module exports may call fetchAll which is scoped, but if export is called cross-tenant (admin), runAsTenant() could leak data if not carefully bounded
3. **Caching**: WordPress object cache or transient cache is NOT tenant-scoped — key collisions possible if same key used for different tenants
4. **fetchOneUnsafeNoScope()**: Explicitly bypasses tenant scoping — intended for ShareService/diagnostics but dangerous if misused
5. **Shared tenants option**: `bookando_shared_tenants` array allows cross-tenant reads — if misconfigured, enables data leakage
6. **Subdomain mapping manipulation**: If attacker controls DNS, could map subdomain to wrong tenant
7. **Static memoization reset**: `TenantManager::reset()` exists for tests but if called in production, subsequent queries use wrong tenant
8. **Aggregation queries**: `SELECT COUNT(*) FROM ...` wrapped in subquery adds overhead but maintains isolation
9. **Direct $wpdb usage**: Any code bypassing BaseModel (raw $wpdb queries) has NO automatic tenant scoping

### Failure Modes
- Missing tenant → RuntimeException (hard fail — safe)
- Wrong tenant (silent) → data from wrong tenant returned (dangerous)
- Null tenant in cron → falls back to tenant 1 (data pollution)

### Must Be Logged/Audited
- Every tenant context switch (runAsTenant)
- Every fetchOneUnsafeNoScope usage
- Tenant fallback usage (already logged in TenantManager::logFallbackUsage)
- Cross-tenant share ACL creation/resolution

### Required Tests
- **Unit**: applyTenant() wraps SQL correctly
- **Unit**: insert() throws on null tenant
- **Unit**: update()/delete() include tenant_id in WHERE
- **Integration**: Two tenants, verify data isolation
- **Integration**: QueueManager job runs with correct tenant
- **Integration**: fetchOneUnsafeNoScope only works after ACL check
- **E2E**: Login as tenant A, verify no tenant B data visible

---

## Flow 7: Calendar Sync (Google Calendar)

### Happy Path
```
Employee initiates sync:
  → POST /bookando/v1/integrations/oauth/start
  → RestDispatcher::oauthStart() [src/Core/Dispatcher/RestDispatcher.php]
    → Validate provider (google), employee_id, mode (ro/rw)
    → Create state transient (15 min TTL) with CSRF token
    → GoogleCalendarSync::getAuthUrl() [src/Core/Integrations/Calendar/GoogleCalendarSync.php]
    → Return auth_url to client

  Client redirects to Google OAuth:
  → GET /bookando/v1/integrations/oauth/callback?code=X&state=Y
  → RestDispatcher::oauthCallback()
    → Verify state transient matches
    → GoogleCalendarSync::exchangeCode() → access_token + refresh_token
    → OAuthTokenStorage::persist() [src/Core/Service/OAuthTokenStorage.php]
      → AES-256-GCM encryption with employee-specific key
      → Store in calendar_connections table

  Ongoing sync (appointment created):
  → GoogleCalendarSync::createEvent($calendarId, $eventData)
    → authenticate() → refresh token if needed
    → POST to Google Calendar API
    → Store bookando_appointment_id in extendedProperties
```

### Edge Cases
1. **Token refresh failure**: Refresh token expired (Google revokes after 6 months inactivity) → need re-authorization flow
2. **Rate limiting by Google**: 10 requests/second quota → no retry/backoff logic visible
3. **Concurrent sync**: Two syncs for same employee start simultaneously
4. **Encryption key derivation**: Uses employee-specific key — if employee record changes, existing tokens may be undecryptable
5. **Calendar deleted at Google**: Event creation fails with 404 → error logged but no retry
6. **Timezone mismatch**: Bookando stores UTC, Google expects timezone-aware events — conversion in createEvent()
7. **OAuth state CSRF**: State transient TTL is 15 minutes — sufficient for normal flow
8. **Callback origin trust**: `oauthCallbackPermission()` checks referer origin trust
9. **Multiple calendars**: Employee may have multiple Google calendars — sync targets selected calendar only
10. **Bi-directional sync gap**: Changes in Google Calendar are NOT synced back to Bookando (one-way push only)

### Failure Modes
- OAuth flow interrupted → stale state transient (auto-expires in 15 min)
- Token decryption failure → calendar sync disabled for that employee
- Google API outage → events not synced (no queue/retry)
- Network timeout → `wp_remote_post` timeout (default 5s in WP)

### Must Be Logged/Audited
- OAuth flow initiation and completion
- Token refresh events
- Event creation/update/deletion at provider
- Sync failures with error details

### Required Tests
- **Unit**: OAuthTokenStorage encryption roundtrip
- **Unit**: Google FreeBusy parsing
- **Integration**: Full OAuth mock flow
- **Integration**: Calendar event creation with timezone conversion
- **E2E**: Connect Google Calendar, create appointment, verify sync

---

## Flow 8: Cross-Tenant Data Sharing

### Happy Path
```
Admin creates share:
  → POST /bookando/v1/share/create
  → RestDispatcher::shareCreate() [src/Core/Dispatcher/RestDispatcher.php]
    → LicenseManager::isFeatureEnabled('cross_tenant_share')
    → ShareService::createShare() [src/Core/Sharing/ShareService.php]
      → UPSERT into bookando_share_acl (resource_type, resource_id, owner_tenant, grantee_tenant, scope, expires_at)
      → Sign payload with HMAC-SHA256 (uses WP salt constants)
      → Return signed token

Grantee resolves share:
  → GET /bookando/v1/share/resolve?token=X
  → RestDispatcher::shareResolve()
    → ShareService::resolveToken()
      → Verify HMAC signature (hash_equals for timing-safe comparison)
      → Check token not expired
      → Verify ACL entry exists
      → Return resource access
    → TenantManager::canAccessShared() checks ACL
```

### Edge Cases
1. **Token leakage**: Signed token in URL → visible in server logs, browser history
2. **ACL expiry race**: Token valid but ACL expired between signature check and data access
3. **Scope escalation**: Share grants 'read' scope but grantee attempts write
4. **Revocation**: No explicit revocation mechanism — must delete ACL entry and wait for token expiry
5. **HMAC key rotation**: Uses WP salt constants — if rotated, all existing tokens become invalid
6. **Cross-module sharing**: resource_type is free-form string — no validation against actual module/entity types
7. **Expired but cached**: If shared resource is cached, expired share may still serve cached data
8. **Batch sharing**: No batch API for creating multiple shares

### Must Be Logged/Audited
- Share creation with full ACL details
- Share resolution attempts (success and failure)
- Token verification failures

### Required Tests
- **Unit**: HMAC sign/verify roundtrip
- **Unit**: Expired token rejection
- **Unit**: ACL lookup with valid/invalid grantee
- **Integration**: Full share → resolve → access flow
- **Integration**: Cross-tenant data access with valid share

---

## Flow 9: Refund Processing

### Happy Path
```
Admin initiates refund:
  → POST /bookando/v1/finance/payment/{payment_id}/refund
  → Finance/PaymentRestHandler::refundPayment() [src/modules/Finance/PaymentRestHandler.php]
    → GatewayManager::getGateway()
    → gateway->refundPayment($paymentId, $amount, $reason)
      → Stripe: Refund::create() with amount + reason mapping
      → PayPal: Captures API → refund on capture ID (not order ID)
      → Mollie: payment->refund()
    → Update local payment record
    → Fire do_action('bookando_refund_completed')

  Webhook confirmation:
  → charge.refunded (Stripe) / PAYMENT.CAPTURE.REFUNDED (PayPal) / refunded (Mollie)
  → PaymentWebhookHandler → handleRefundCompleted()
```

### Edge Cases
1. **Partial refund**: Amount parameter controls partial vs full (amount=0 means full refund)
2. **Klarna refund stub**: NOT fully implemented — returns `['refund_id' => uniqid(), 'status' => 'pending']`
3. **Double refund**: No idempotency check — same refund can be attempted twice
4. **Refund exceeds original amount**: Gateway-side validation (Stripe returns error), no pre-check in code
5. **Refund on pending payment**: Payment not yet captured — refund should fail
6. **Currency mismatch in refund**: Refund currency assumed same as payment — not validated
7. **Webhook arrives before refund API returns**: Race condition on payment status
8. **Refund reason mapping**: Only Stripe maps reasons (duplicate, fraudulent, requested_by_customer)

### Must Be Logged/Audited
- Refund initiation with amount, reason, gateway, payment_id
- Refund webhook receipt
- Failed refund attempts

### Required Tests
- **Unit**: Refund amount calculation for partial/full
- **Unit**: Reason mapping per gateway
- **Integration**: Full refund flow with mock gateway
- **Integration**: Double-refund prevention

---

## Flow 10: Module Lifecycle (Discovery → Activation → Boot)

### Happy Path
```
Plugin loads:
  → Loader::initModules() [src/Core/Loader.php:142]
    → Loader::ensureModulesActivated()
      → Reads bookando_active_modules option
      → If empty (first run): scans src/modules/*/module.json, activates all
    → ModuleManager::instance()->loadModules() [src/Core/Manager/ModuleManager.php]
      → Reads config/modules.php: maps slug → class path + manifest
      → For each active module:
        → Checks isLegacySlug()
        → Instantiates Module class (e.g., \Bookando\Modules\Customers\Module)
        → Calls Module::boot() [src/Core/Base/BaseModule.php:29]
          → boot() calls register()
          → register() hooks admin menu, REST routes, capabilities
```

### Edge Cases
1. **Module class not found**: Autoloader fails → PHP fatal error (not caught)
2. **module.json malformed**: `json_decode()` returns null → module skipped
3. **Legacy slug handling**: ModuleManager::isLegacySlug() filters deprecated modules
4. **Circular module dependency**: No dependency resolution — modules load in config order
5. **Module installer failure**: DB table creation via dbDelta() may fail silently
6. **License prevents module**: LicenseManager::isModuleAllowed() checked in UI (buildModuleVars) but NOT in boot — module code still loads even if not licensed
7. **Module deactivation cleanup**: No uninstall/deactivate hook per module
8. **Concurrent module activation**: Race condition on bookando_active_modules option

### Must Be Logged/Audited
- Module activation/deactivation
- Auto-activation on first setup
- Module boot failures

### Required Tests
- **Unit**: ModuleManager with valid/invalid module configs
- **Unit**: BaseModule::getSlug() namespace extraction
- **Integration**: Full module lifecycle (activate → boot → routes registered)
- **Integration**: Module with missing manifest.json
