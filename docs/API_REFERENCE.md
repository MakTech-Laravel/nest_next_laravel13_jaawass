# Jaawaas API Reference (v1)

**Project:** `jaawaas_nest_next_laravel_13_api`  
**Base URL:** `{APP_URL}/api/v1`  
**Generated:** June 2026  
**Format:** Markdown — open in VS Code, Typora, or any Markdown viewer and **Export as PDF / Word** (File → Print → Save as PDF, or use Pandoc).

---

## Table of Contents

1. [Global Conventions](#1-global-conventions)
2. [Public APIs (no auth)](#2-public-apis-no-auth)
3. [Common Authenticated APIs (all roles)](#3-common-authenticated-apis-all-roles)
4. [Buyer APIs](#4-buyer-apis)
5. [Manufacturer APIs](#5-manufacturer-apis)
6. [Admin APIs](#6-admin-apis)
7. [Shared Resource Schemas](#7-shared-resource-schemas)

---

## 1. Global Conventions

### 1.1 Standard success envelope

All JSON responses use `sendResponse()`:

```json
{
  "success": true,
  "message": "Human-readable message",
  "data": {}
}
```

### 1.2 Paginated list envelope

When `data` is a Laravel paginated resource collection:

```json
{
  "success": true,
  "message": "...",
  "data": [],
  "links": {
    "first": "https://example.com/api/v1/products?page=1",
    "last": "https://example.com/api/v1/products?page=10",
    "prev": null,
    "next": "https://example.com/api/v1/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "path": "https://example.com/api/v1/products",
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

### 1.3 Error envelope

```json
{
  "success": false,
  "message": "Error description",
  "data": null
}
```

| HTTP | Typical cause |
|------|----------------|
| 401 | Missing/invalid Bearer token |
| 403 | Wrong role, suspended account, plan feature missing |
| 404 | Model not found |
| 422 | Validation failed (`errors` may appear in Laravel default validation response) |
| 429 | Rate limit exceeded |

### 1.4 Authentication

Protected routes require:

```
Authorization: Bearer {access_token}
Accept: application/json
```

Token is returned from `POST /register`, `POST /login`, or social token endpoints.

### 1.5 Locale & timezone

| Header / param | Purpose |
|----------------|---------|
| `Accept-Language` | Locale for translated content |
| `?locale=en` | Override locale on public catalog endpoints |
| `X-Timezone` | User timezone (common auth routes) |

### 1.6 Viewer country (export market visibility)

Used on public supplier/product endpoints to filter by manufacturer export markets:

| Priority | Source |
|----------|--------|
| 1 | Query `viewer_country_code=US` (ISO 3166-1 alpha-2) |
| 2 | Query `country=US` (2-letter code on map links) |
| 3 | Header `X-Viewer-Country-Code: US` |
| 4 | Logged-in user's `company.country` |

If no viewer country is resolved, export-market filtering is **not** applied.

### 1.7 Route prefixes

| Group | Prefix | Middleware |
|-------|--------|------------|
| Public | `/api/v1/` | locale |
| Common | `/api/v1/` | `auth:api`, locale, timezone |
| Buyer | `/api/v1/buyer/` | `auth:api`, `role.buyer` |
| Manufacturer | `/api/v1/manufacturer/` | `auth:api`, `role.manufacturer` |
| Admin | `/api/v1/admin/` | `auth:api`, `role.admin` |

Manufacturer routes under `subscription.active` also require an active subscription. Individual routes may require plan features (e.g. `export_markets_section`, `product_limit`).

---

## 2. Public APIs (no auth)

### 2.1 Authentication & account

#### POST `/register`

Register a buyer or manufacturer.

**Body (JSON or multipart for manufacturer files):**

| Field | Buyer | Manufacturer | Notes |
|-------|-------|--------------|-------|
| `role` | `"buyer"` | `"manufacturer"` | required |
| `first_name` | ✓ | ✓ | |
| `last_name` | ✓ | ✓ | |
| `email` | ✓ | ✓ | unique |
| `password` | ✓ | ✓ | min 8 |
| `company_name` | ✓ | ✓ | |
| `country` | ✓ | ✓ | |
| `city` | — | ✓ | manufacturer only |
| `terms_condition` | ✓ | ✓ | must be accepted |
| `bussiness_licence` | — | ✓ | file pdf/jpg/png |
| `factory_images[]` | — | optional | max 5 images |
| `company_website` | — | optional | url |
| `notes` | — | optional | |
| `device_name` | optional | optional | |

**Response 201 (buyer — immediate login):**

```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "token_type": "Bearer",
    "access_token": "eyJ...",
    "user": { "id": 1, "first_name": "...", "role": "Buyer", "email": "..." }
  }
}
```

**Response 201 (manufacturer pending approval):**

```json
{
  "success": true,
  "message": "Manufacturer account pending approval",
  "data": null,
  "manufacture_status": "pending"
}
```

---

#### POST `/login`

**Body:**

```json
{
  "email": "user@example.com",
  "password": "secret",
  "role": "buyer",
  "device_name": "Chrome Windows"
}
```

`role`: `buyer` | `manufacturer` | `admin`

**Response 200:**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token_type": "Bearer",
    "access_token": "eyJ...",
    "user": { "id": 1, "first_name": "...", "role": "Buyer", "two_factor_enabled": false }
  }
}
```

**Response 200 (2FA required):**

```json
{
  "success": true,
  "message": "...",
  "data": { "two_factor": true, "login_id": "uuid-cache-key" }
}
```

---

#### POST `/two-factor-challenge`

**Body:** `{ "login_id": "...", "code": "123456" }` or recovery code.

**Response 200:** Same as successful login (token + user).

---

#### POST `/forgot-password`

**Body:** `{ "email": "user@example.com" }`

**Response 200:** `{ "success": true, "message": "...", "data": null }`

---

#### POST `/reset-password`

**Body:** `{ "email", "password", "password_confirmation", "token" }`

**Response 200:** `{ "success": true, "message": "...", "data": null }`

---

### 2.2 Social authentication

| Method | Path | Description |
|--------|------|-------------|
| GET | `/auth/{provider}/redirect` | Browser OAuth redirect (`google`, `facebook`) |
| GET | `/auth/{provider}/callback` | OAuth callback |
| POST | `/auth/google/token` | Google ID token login |
| POST | `/auth/facebook/token` | Facebook access token login |
| POST | `/auth/social/complete-profile` | Complete profile after `setup_token` |

**POST `/auth/google/token` body:** `{ "id_token": "...", "role": "buyer" }`

**Response 200:** Token + user (or setup_token if profile incomplete).

---

### 2.3 Account restore (deleted accounts)

| Method | Path | Body |
|--------|------|------|
| POST | `/account/restore-delete/request` | `{ "email" }` |
| POST | `/account/restore-delete/verify` | `{ "email", "otp" }` |

---

### 2.4 Manufacturer additional information (public token link)

| Method | Path | Auth |
|--------|------|------|
| GET | `/manufacturer/additional-information/{token}` | None |
| POST | `/manufacturer/additional-information/{token}` | None |

**POST body:** Dynamic fields per admin request (files + text).

**Response:** `{ "success": true, "data": { "id", "status", "fields": [...] } }`

---

### 2.5 Catalog & content

#### GET `/currencies`

**Response:**

```json
{
  "success": true,
  "data": [
    { "code": "USD", "symbol": "$", "name": "US Dollar", "is_base": true }
  ]
}
```

---

#### GET `/quick-filters`

**Query:** `type` (optional filter by filter type)

**Response:** `{ "success": true, "data": [ { "id", "type", "label", "value", "sort_order" } ] }`

---

#### GET `/faqs/categories`

**Response:** Paginated FAQ categories.

---

#### PATCH `/faqs/{faq}/click`

Increments click counter. **Response:** `{ "success": true, "data": null }`

---

#### GET `/plans`

Public subscription plans listing.

**Response data item:**

```json
{
  "id": 1,
  "name": "Pro",
  "monthly_price": "99.00",
  "yearly_price": "990.00",
  "is_popular": true,
  "features": [
    {
      "id": 12,
      "label": "Up to 50 product listings",
      "input_type": "text",
      "value": "50",
      "features": { "id": 1, "name": "Product Limit", "key": "product_limit" }
    }
  ]
}
```

Each plan feature includes a **`label`** for display on that plan. If no custom label was set when the feature was assigned, `label` falls back to the global feature catalog name (`features.name`).

---

#### GET `/promotions/active`

**Response:** Active promotion with enrollment info.

---

#### GET `/categories`

**Query:** `page`, `per_page`, `search`, `featured`, `locale`

**Response:** Paginated industries/categories (`IndustryResource`).

---

#### GET `/shipping/methods`

Active shipping methods for product forms.

**Response:** `[ { "id", "name", "description", "is_active" } ]`

---

### 2.6 Products (public catalog)

| Method | Path | Notes |
|--------|------|-------|
| GET | `/products` | List / search |
| GET | `/products/category/{categoryId}` | By category |
| GET | `/products/sub-category/{subCategoryId}` | By subcategory |
| GET | `/products/{product}` | Detail |
| POST | `/products` | Create (legacy/dev — may require auth in practice) |
| PUT | `/products/{product}` | Update |
| DELETE | `/products/{product}` | Delete |

#### GET `/products` — query parameters

| Param | Type | Description |
|-------|------|-------------|
| `page`, `per_page` | int | Pagination (default 15, max 100) |
| `search` | string | Text search |
| `sort` | enum | `relevance`, `price-low`, `price-high`, `moq-low`, `newest`, `popularity` |
| `category_id`, `category_slug`, `category` | | Category filter |
| `industry_id`, `industry_slug`, `industry` | | Alias for category |
| `sub_category_id`, `sub_category_slug` | | Subcategory |
| `supplier_id`, `supplier` | | Filter by supplier |
| `country`, `city` | string | Supplier location |
| `viewer_country_code` | string(2) | Export visibility |
| `min_price`, `max_price` | number | Price range |
| `min_moq`, `max_moq`, `moq_range` | | MOQ filters |
| `certification`, `export_market` | string | Filters |
| `locale` | string | Translation locale |

**Response data item (`ProductResource`):**

```json
{
  "id": 1,
  "supplier_id": 5,
  "supplier_name": "Acme Co",
  "supplier_country": "China",
  "supplier_city": "Shenzhen",
  "supplier": { "id": 5, "name": "Acme Co", "country": "China", "city": "Shenzhen" },
  "name": "Widget A",
  "slug": "widget-a",
  "description": "...",
  "price": 12.5,
  "price_display": "$12.50",
  "conversion_available": true,
  "quantity": 100,
  "inquiry_count": 3,
  "view_count": 120,
  "is_approved": true,
  "image": "https://.../storage/...",
  "status": "active",
  "created_at": "2026-01-01T00:00:00.000000Z",
  "category": { "id": 1, "name": "Electronics", "slug": "electronics" },
  "pricing_quantities": { "moq": 100, "tiers": [] },
  "review_stats": { "average_rating": 4.5, "total_reviews": 10, "breakdown": [] }
}
```

Detail (`GET /products/{product}`) includes relations: images, specifications, shipping, reviews, etc.

---

### 2.7 Suppliers (public)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/suppliers` | Paginated supplier cards |
| GET | `/suppliers/map` | Country map aggregates |
| GET | `/suppliers/map/groups` | Map country groups |
| GET | `/suppliers/map/top-countries` | Top manufacturer countries |
| GET | `/suppliers/{supplier}` | Supplier profile detail |
| GET | `/suppliers/{supplier}/products` | Supplier products |
| GET | `/suppliers/{supplier}/reviews` | Supplier reviews |
| GET | `/suppliers/{supplier}/catalogs` | Public catalogs |
| GET | `/suppliers/{supplier}/certifications` | Public certifications |

#### GET `/suppliers` — query parameters

| Param | Type | Description |
|-------|------|-------------|
| `page`, `per_page` | int | Pagination |
| `search` | string | Company name search |
| `sort` | enum | `relevance`, `rating`, `products`, `newest` |
| `industry_id`, `industry_slug`, `category_id`, `category_slug` | | Industry filter |
| `country` | string | Location filter |
| `viewer_country_code` | string(2) | Export visibility |
| `certification`, `export_market` | string | |
| `moq_range` | enum | `1-100`, `100-500`, `500-1000`, `1000-5000`, `5000+` |
| `reviewed_only` | bool | Default `true` |
| `ids` | string | Comma-separated IDs (max 4) for compare widgets |

**Response data item (`PublicSupplierResource`):**

```json
{
  "id": 5,
  "name": "Acme Manufacturing",
  "slug": "acme-manufacturing",
  "short_description": "...",
  "location": { "city": "Shenzhen", "country": "China", "country_code": null },
  "industry": "Electronics",
  "industry_slug": "electronics",
  "reviewed": true,
  "reviewed_level": "standard",
  "rating": 4.5,
  "review_count": 12,
  "product_count": 45,
  "main_products": ["Widget A", "Widget B"],
  "certifications": ["ISO 9001"],
  "export_markets": ["North America", "Western Europe"]
}
```

**GET `/suppliers/{supplier}`** returns `PublicSupplierDetailResource` (card fields + long_description, logo, factory_photos, company profile, etc.).

**GET `/suppliers/map` response extras:**

```json
{
  "data": {
    "countries": [],
    "export_suppliers_count": 120,
    "has_export_suppliers": true
  }
}
```

---

### 2.8 Articles & help center

| Method | Path |
|--------|------|
| GET | `/articles` |
| GET | `/articles/{article}` |
| GET | `/help-center-categories` |
| GET | `/help-center-categories/{helpCenterCategory}` |
| GET | `/help-center-articles` |
| GET | `/help-center-articles/{helpCenterArticle}` |
| PATCH | `/help-center-articles/{helpCenterArticle}/is-helpful` |

**PATCH is-helpful body:** `{ "is_helpful": true }`

---

### 2.9 Contact

#### POST `/contact`

**Body:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "subject": "Partnership",
  "message": "Hello..."
}
```

**Response 201:** `{ "success": true, "data": { "id", "name", "email", "subject", "message", "created_at" } }`

---

## 3. Common Authenticated APIs (all roles)

**Prefix:** `/api/v1/` · **Auth:** Bearer token (any role)

### 3.1 Session

| Method | Path | Description |
|--------|------|-------------|
| POST | `/logout` | Revoke current token |
| GET | `/me` | Current user profile |

**GET `/me` response:** `UserResource` (role-specific fields — see §7.1).

---

### 3.2 Notifications

| Method | Path |
|--------|------|
| GET | `/me/notifications` |
| PATCH | `/me/notifications/{id}/read` |
| POST | `/me/notifications/read-all` |
| POST | `/me/notifications/test-broadcast` |

**GET query:** `page`, `per_page`, `unread_only`

**Response item:**

```json
{
  "id": 1,
  "type": "rfq.received",
  "title": "...",
  "body": "...",
  "read_at": null,
  "created_at": "..."
}
```

---

### 3.3 Preferences

| Method | Path | Body |
|--------|------|------|
| PATCH | `/me/preferences` | `{ "preferred_language", "timezone", ... }` |
| PATCH | `/me/currency-preference` | `{ "preferred_currency_code": "USD" }` |

---

### 3.4 Account management

| Method | Path | Body |
|--------|------|------|
| POST | `/account/deactivate` | `{ "reason" }` (optional) |
| POST | `/account/activate` | — |
| POST | `/account/delete-request` | `{ "password" }` |
| POST | `/account/change-password` | `{ "current_password", "password", "password_confirmation" }` |
| GET | `/account/login-history` | Query: `page`, `per_page` |

---

### 3.5 Customer support tickets

| Method | Path |
|--------|------|
| GET | `/customer-supports/options` |
| GET | `/customer-supports/tickets` |
| POST | `/customer-supports/tickets` |
| GET | `/customer-supports/tickets/{ticket}` |
| POST | `/customer-supports/tickets/{ticket}/messages` |

**POST ticket body:** `{ "subject", "category", "priority", "message", "attachments[]" }`

**Response (`TicketResource`):**

```json
{
  "id": 1,
  "ticket_number": "TKT-00001",
  "subject": "...",
  "status": "open",
  "priority": "medium",
  "messages": [ { "id", "body", "sender", "attachments", "created_at" } ]
}
```

---

### 3.6 Two-factor authentication

| Method | Path |
|--------|------|
| POST | `/two-factor/enable` |
| GET | `/two-factor/qr-code` |
| POST | `/two-factor/confirm` |
| GET | `/two-factor/recovery-codes` |
| POST | `/two-factor/recovery-codes/regenerate` |
| DELETE | `/two-factor/disable` |

**POST confirm body:** `{ "code": "123456" }`

---

### 3.7 Messaging (plan feature: `internal_messaging`)

| Method | Path |
|--------|------|
| GET | `/conversations` |
| POST | `/conversations` |
| GET | `/conversations/{conversation}` |
| PATCH | `/conversations/{conversation}` |
| POST | `/conversations/{conversation}/participants` |
| GET | `/conversations/{conversation}/messages` |
| POST | `/conversations/{conversation}/messages` |

**POST conversation body:** `{ "participant_ids": [2, 3], "subject": "..." }`

**POST message body:** `{ "body": "...", "attachments[]": files }`

**Response (`ConversationResource` / `MessageResource`):**

```json
{
  "id": 1,
  "subject": "RFQ discussion",
  "participants": [ { "id", "name", "avatar" } ],
  "last_message_at": "...",
  "unread_count": 2
}
```

---

## 4. Buyer APIs

**Prefix:** `/api/v1/buyer/` · **Auth:** Bearer + `role.buyer`

### 4.1 Dashboard

#### GET `/dashboard`

**Response:**

```json
{
  "success": true,
  "data": {
    "welcome": { "first_name": "Jane", "name": "Jane Doe" },
    "stats": {
      "active_conversations": { "value": 3, "badge": "+1 new" },
      "rfqs_submitted": { "value": 12, "badge": "2 pending" },
      "saved_suppliers": { "value": 5, "badge": null },
      "products_viewed": { "value": 20, "badge": null }
    },
    "recent_messages": [],
    "recent_rfqs": [],
    "recommended_suppliers": [],
    "recent_activity": []
  }
}
```

---

### 4.2 Profile

| Method | Path | Description |
|--------|------|-------------|
| GET | `/profile/` | Show profile |
| PUT | `/profile/update` | Update profile |
| DELETE | `/profile/delete` | Delete account |
| PUT | `/profile/change-password` | Change password |
| PATCH | `/profile/toggle-status` | Activate/deactivate |
| PUT | `/profile/notification-preferences` | Notification toggles |

**PUT update body:** `{ "first_name", "last_name", "company_name", "country", "phone", "avatar" (file) }`

---

### 4.3 RFQs

| Method | Path |
|--------|------|
| GET | `/rfqs` |
| GET | `/rfqs/search` |
| GET | `/rfqs/counts` |
| GET | `/rfqs/{rfq}` |
| POST | `/rfqs` |
| PATCH | `/rfqs/{rfq}/status` |
| POST | `/rfqs/{rfq}/respond-quote` |

**POST body:**

```json
{
  "product_id": 1,
  "quantity": 500,
  "quantity_unit": "pcs",
  "target_price": 10.5,
  "target_currency_code": "USD",
  "required_delivery_date": "2026-08-01",
  "shipping_terms": "FOB",
  "destination_country": "United States",
  "destination_port_city": "Los Angeles",
  "packaging_details": "Cartons",
  "additional_requirements": "..."
}
```

**GET query:** `page`, `per_page`, `status`, `search`, `sort`

**Response item (`Buyer RfqSubmissionResource`):**

```json
{
  "id": 1,
  "rfq_number": "RFQ-00001",
  "status": "pending",
  "quantity": 500,
  "quantity_unit": "pcs",
  "target_price": 10.5,
  "destination_country": "United States",
  "quote_price": null,
  "quote_currency_code": null,
  "quote_valid_until": null,
  "product": { "id": 1, "name": "Widget", "slug": "widget" },
  "supplier": { "id": 5, "company_name": "Acme", "location": "Shenzhen, China" },
  "conversation_id": 10,
  "message_endpoint": "...",
  "quote_action_endpoint": "..."
}
```

**POST respond-quote body:** `{ "action": "accept" | "reject", "note": "..." }`

---

### 4.4 Orders

| Method | Path |
|--------|------|
| GET | `/orders/stats` |
| GET | `/orders` |
| GET | `/orders/{order}` |

**GET query:** `page`, `per_page`, `status`, `search`, `date_from`, `date_to`

**Response:** `OrderResource` (buyer view — see §7.4).

---

### 4.5 Saved & compare — products

| Method | Path |
|--------|------|
| GET | `/products/saved` |
| POST | `/products/saved` |
| DELETE | `/products/saved/{product}` |
| GET | `/products/compare` |
| POST | `/products/compare` |
| DELETE | `/products/compare/{product}` |

**POST saved/compare body:** `{ "product_id": 1 }`

---

### 4.6 Product reviews

#### POST `/products/{product}/reviews`

**Body:** `{ "rating": 5, "title": "...", "comment": "..." }`

**Response:** `ProductReviewResource`

---

### 4.7 Saved & compare — suppliers

| Method | Path |
|--------|------|
| GET | `/suppliers/saved` |
| POST | `/suppliers/saved` |
| DELETE | `/suppliers/saved/{supplier}` |
| GET | `/suppliers/compare` |
| POST | `/suppliers/compare` |
| DELETE | `/suppliers/compare/{supplier}` |

**POST body:** `{ "supplier_id": 5 }`

---

## 5. Manufacturer APIs

**Prefix:** `/api/v1/manufacturer/` · **Auth:** Bearer + `role.manufacturer`

### 5.1 Subscriptions (no active subscription required)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/subscriptions` | Current subscription row (may be expired) |
| POST | `/subscriptions/subscribe` | Subscribe or **renew** after expired trial |
| POST | `/subscriptions/cancel` | Cancel auto-renew (access until `ends_at`) |
| POST | `/subscriptions/upgrade` | Change plan, or renew same plan when inactive |

**POST subscribe body:** `{ "plan_id": 1, "billing_interval": "month" | "year", "payment_method": "paypal", "payment_id": "...", "auto_renew": true, "paid_amount": 99.00 }`

**Subscribe / renew rules:**

| Manufacturer state | POST `/subscribe` |
|--------------------|-------------------|
| No subscription row | Creates new paid subscription (`source: purchase`) |
| Active subscription | **409** `already_subscribed` |
| Expired / inactive row (e.g. promotion trial ended) | **Renews** existing row → `status: active`, clears `promotion_id` |

**POST upgrade:** Same-plan upgrade is allowed when the current subscription is **not** entitlement-active (converts expired trial to paid). Active same-plan upgrade returns validation error `same_plan`.

**GET `/subscriptions` response** includes `is_active` (whether routes gated by `subscription.active` would pass), `source` (`purchase` | `promotion`), `promotion_id`, and `days_remaining`.

**Response:** `SubscriptionResource` (§7.5).

---

### 5.1.1 Promotions (no active subscription required)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/promotions/active` | Active founding promotion |
| GET | `/promotions/my-application` | Manufacturer application status |
| POST | `/promotions/apply` | Apply (creates `pending` pivot row) |

**Apply guard:** Returns **422** if manufacturer already has an **active** subscription (`promotion.already_has_subscription`).

**Promotion → subscription lifecycle:**

1. **Apply** → `promotion_user.status = pending`, no subscription, no trial.
2. **Admin accept** → pivot `accepted` + `trial_ends_at`; if no active subscription, creates or reactivates `subscriptions` row with `status: trialing`, `source: promotion`, `promotion_id`.
3. **Admin accept + active paid subscription** → pivot updated only; paid subscription unchanged.
4. **Trial expires** (`ends_at` in past) → `is_active: false`; gated routes return **403**; public supplier listing excludes manufacturer.
5. **Renew** → `POST /subscriptions/subscribe` with payment reactivates the row as paid (`source: purchase`).

Entitlements always come from `subscriptions`, not from `promotion_user` pivot status.

---

### 5.2 Review center (no subscription gate on route file)

#### GET `/review-center`

Manufacturer approval / additional-info status.

**Response:**

```json
{
  "success": true,
  "data": {
    "manufacture_status": "approved",
    "rejection_reason": null,
    "additional_information_requests": [],
    "checklist": []
  }
}
```

---

### 5.3 Routes requiring `subscription.active`

#### GET `/dashboard`

**Plan feature:** `basic_analytics` OR `advanced_analytics`

**Response:**

```json
{
  "success": true,
  "data": {
    "profile_completeness": { "percent": 80, "label": "Almost complete" },
    "stats": {
      "products_listed": { "value": 12 },
      "inquiries_30d": { "value": 5 },
      "orders_30d": { "value": 2 }
    },
    "recent_inquiries": [],
    "response_metrics": { "avg_response_hours": 4.2 },
    "quick_stats": {},
    "recent_activity": []
  }
}
```

---

### 5.4 Analytics

**Prefix:** `/analytics` · **Plan features** as noted

| Method | Path | Feature |
|--------|------|---------|
| GET | `/analytics/metrics` | basic \| advanced |
| GET | `/analytics/products` | basic \| advanced |
| GET | `/analytics/performance` | advanced |
| GET | `/analytics/countries` | advanced |
| GET | `/analytics/funnel` | advanced |

**Common query:** `period=7d|30d|90d|12m`, `page`, `per_page`

---

### 5.5 Export markets

**Prefix:** `/markets` · **Plan feature:** `export_markets_section`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/markets` | Overview |
| GET | `/markets/countries` | Country picker |
| POST | `/markets/regions` | Add region |
| PUT | `/markets/regions/{market}` | Update countries |
| DELETE | `/markets/regions/{market}` | Remove region |
| PUT | `/markets/countries/sync` | Bulk sync |

#### GET `/markets` response

```json
{
  "success": true,
  "data": {
    "stats": {
      "active_markets": 3,
      "total_inquiries": 10,
      "total_orders": 2,
      "growth_rate": { "value": 15, "direction": "up", "label": "+15%" }
    },
    "active_regions": [
      {
        "id": 1,
        "region": "North America",
        "country_codes": ["CA", "US"],
        "countries": [ { "code": "US", "name": "United States" } ]
      }
    ],
    "suggestions": [],
    "meta": {
      "regions": ["North America", "Western Europe"],
      "geographic_regions": ["Americas", "Europe"]
    }
  }
}
```

#### GET `/markets/countries query

`page`, `per_page`, `search`, `geographic_region`

**Response item:**

```json
{
  "code": "US",
  "name": "United States",
  "region": "North America",
  "geographic_region": "Americas",
  "is_selected": true
}
```

#### POST `/markets/regions`

**Body:** `{ "region": "North America", "country_codes": ["US", "CA"] }`

#### PUT `/markets/regions/{market}`

**Body:** `{ "country_codes": ["US"] }`

#### PUT `/markets/countries/sync`

**Body:** `{ "country_codes": ["US", "DE", "FR"] }`

---

### 5.6 Profile

| Method | Path | Plan feature |
|--------|------|--------------|
| GET | `/profile/` | — |
| PUT | `/profile/basic-profile` | — |
| PUT | `/profile/change/password` | — |
| PATCH | `/profile/toggle-status` | — |
| PUT | `/profile/notification-preferences` | — |
| PUT | `/profile/update` | `company_profile` |

**PUT update:** Full company profile (logo, descriptions, industries, export_markets sync, factory images, translations).

---

### 5.7 Products

| Method | Path | Plan feature |
|--------|------|--------------|
| GET | `/products/stats` | basic \| advanced analytics |
| GET | `/products` | — |
| POST | `/products` | `product_limit` |
| GET | `/products/{product_id}` | — |
| PUT | `/products/{product_id}` | — |
| DELETE | `/products/{product_id}` | — |
| PATCH | `/products/{product_id}/change-status` | — |
| PATCH | `/products/{product_id}/duplicate-to-draft` | `product_limit` |

**GET query:** `page`, `per_page`, `search`, `status`, `category_id`

**POST/PUT body:** Product fields (name, description, price, category, images, specifications, pricing tiers, shipping, translations).

---

### 5.8 Catalogs

| Method | Path | Plan feature |
|--------|------|--------------|
| GET | `/catalogs` | — |
| POST | `/catalogs` | `catalog_upload` |
| GET | `/catalogs/stats` | — |
| GET | `/catalogs/{catalog_id}` | — |
| PUT | `/catalogs/{catalog_id}` | `catalog_upload` |
| PATCH | `/catalogs/{catalog_id}/change-status` | `catalog_upload` |
| DELETE | `/catalogs/{catalog_id}` | `catalog_upload` |

**Response (`CatalogResource`):**

```json
{
  "id": 1,
  "title": "2026 Catalog",
  "file_url": "https://.../storage/catalogs/file.pdf",
  "status": "published",
  "download_count": 15
}
```

---

### 5.9 RFQs (inbox)

**Plan feature:** `inquiry_rfq_inbox`

| Method | Path |
|--------|------|
| GET | `/rfqs` |
| GET | `/rfqs/counts` |
| GET | `/rfqs/{rfq}` |
| POST | `/rfqs/{rfq}/reply` |
| POST | `/rfqs/{rfq}/quote` |

**POST quote body:** `{ "quote_price", "quote_currency_code", "quote_valid_until", "message", "attachments[]" }`

---

### 5.10 Orders

| Method | Path |
|--------|------|
| GET | `/orders/select/products` |
| GET | `/orders/select/buyers` |
| GET | `/orders/status-options` |
| GET | `/orders/stats` |
| GET | `/orders` |
| POST | `/orders/create` |
| POST | `/orders/{order}/status-updates` |
| GET | `/orders/{order}` |

**POST create body:** `{ "buyer_id", "product_id", "quantity", "total_amount", "currency_code", "estimated_delivery_at", "payment_terms", "shipping_terms", "destination", "notes", "attachments[]" }`

**POST status-updates body:** `{ "status", "message", "attachments[]" }`

**Response:** `Manufacturer OrderResource` (§7.4).

---

### 5.11 Certifications

**Plan feature:** `certifications_section`

| Method | Path |
|--------|------|
| GET | `/certificate/types` |
| GET | `/certificate/stats` |
| GET | `/certificate` |
| POST | `/certificate/create` |
| GET | `/certificate/{certificatId}` |
| PUT | `/certificate/{certificatId}` |
| DELETE | `/certificate/{certificateId}` |

---

## 6. Admin APIs

**Prefix:** `/api/v1/admin/` · **Auth:** Bearer + `role.admin`

### 6.1 Dashboard

#### GET `/dashboard`

```json
{
  "success": true,
  "data": {
    "stats": [
      { "key": "total_users", "value": 1000, "trend": { "direction": "up", "percent": 12 } }
    ],
    "pending_approvals": [],
    "recent_reports": [],
    "recent_activity": []
  }
}
```

---

### 6.2 Analytics

| Method | Path | Query |
|--------|------|-------|
| GET | `/analytics/metrics` | `period` |
| GET | `/analytics/growth` | `period` |
| GET | `/analytics/countries` | `page`, `per_page`, `period` |
| GET | `/analytics/industries` | `page`, `per_page`, `period` |

---

### 6.3 Users

| Method | Path |
|--------|------|
| GET | `/users` |
| GET | `/users/{user}` |
| GET | `/users/{user}/login-histories` |
| PATCH | `/users/{user}/deactivate` |
| PATCH | `/users/{user}/reactivate` |
| PATCH | `/users/{user}/suspend` |
| PATCH | `/users/{user}/manufacture-status` |
| DELETE | `/users/{user}` |
| PATCH | `/users/{user}/active` |

**PATCH manufacture-status body:** `{ "manufacture_status": "approved|rejected|pending", "reason": "..." }`

---

### 6.4 Manufacturers

| Method | Path |
|--------|------|
| GET | `/manufacturer` |
| POST | `/manufacturer/create` |
| GET | `/manufacturer/{manufacturer}` |
| DELETE | `/manufacturer/{manufacturer}` |
| PATCH | `/manufacturer/{manufacturer}/change/status` |
| PATCH | `/manufacturer/{manufacturer}/suspend` |
| GET | `/manufacturer/{manufacturer}/additional-information` |
| POST | `/manufacturer/{manufacturer}/additional-information` |
| GET | `/manufacturer-additional-information/{informationRequest}` |

---

### 6.5 Categories & subcategories

| Method | Path |
|--------|------|
| GET | `/categories` |
| POST | `/categories/create` |
| GET | `/categories/{category}` |
| PUT | `/categories/{category}` |
| DELETE | `/categories/{category}` |
| PUT | `/categories/{category}/position` |
| PATCH | `/categories/{category}/featured` |
| GET | `/subcategories` |
| POST | `/subcategories/create` |
| GET | `/subcategories/{subcategory}` |
| PUT | `/subcategories/{subcategory}` |
| DELETE | `/subcategories/{subcategory}` |
| PUT | `/subcategories/{subcategory}/position` |

---

### 6.6 Shipping methods

| Method | Path |
|--------|------|
| GET | `/shipping/methods` |
| POST | `/shipping/methods/create` |
| GET | `/shipping/methods/{shippingMethod}` |
| PUT | `/shipping/methods/{shippingMethod}` |
| DELETE | `/shipping/methods/{shippingMethod}` |

---

### 6.7 Products (admin moderation)

| Method | Path |
|--------|------|
| GET | `/products` |
| PATCH | `/products/{product}/approval-status` |

**PATCH body:** `{ "is_approved": true, "rejection_reason": null }`

---

### 6.8 RFQs

| Method | Path |
|--------|------|
| GET | `/rfqs` |
| GET | `/rfqs/{rfq}` |

---

### 6.9 Customer support (admin)

| Method | Path |
|--------|------|
| GET | `/customer-supports/tickets` |
| GET | `/customer-supports/tickets/{ticket}` |
| PATCH | `/customer-supports/tickets/{ticket}` |
| POST | `/customer-supports/tickets/{ticket}/messages` |

**PATCH body:** `{ "status", "priority", "assigned_to" }`

---

### 6.10 Currencies (rate-limited)

| Method | Path |
|--------|------|
| GET | `/currencies` |
| PATCH | `/currencies/{currency}` |
| POST | `/currency/rates` |
| GET | `/currency/rates/current` |
| GET | `/currency/rates/history` |
| POST | `/currency/sync-rates` |

---

### 6.11 FAQs (admin CRUD)

| Method | Path |
|--------|------|
| GET | `/faqs/categories/{faqCategory}` |
| POST | `/faqs/categories/create` |
| PUT | `/faqs/categories/{id}` |
| DELETE | `/faqs/categories/{faqCategory}` |
| PUT | `/faqs/categories/{faqCategory}/position` |
| POST | `/faqs/create` |
| GET | `/faqs/{faq}` |
| PUT | `/faqs/{faq}` |
| DELETE | `/faqs/{faq}` |
| PUT | `/faqs/{faq}/position` |

---

### 6.12 Quick filters

| Method | Path |
|--------|------|
| GET | `/quick-filters/counts` |
| GET | `/quick-filters/types` |
| GET | `/quick-filters/options` |
| POST | `/quick-filters/options` |
| GET | `/quick-filters/options/{quickFilterOption}` |
| PUT | `/quick-filters/options/{quickFilterOption}` |
| DELETE | `/quick-filters/options/{quickFilterOption}` |
| PATCH | `/quick-filters/options/{quickFilterOption}/toggle` |
| PATCH | `/quick-filters/options/{quickFilterOption}/sort` |

---

### 6.13 Subscriptions & payments

| Method | Path |
|--------|------|
| GET | `/subscriptions/stats` |
| GET | `/subscriptions` |
| GET | `/subscriptions/{subscription}` |
| GET | `/payments` |
| GET | `/subscription-logs` |

---

### 6.14 Plans & features

| Method | Path |
|--------|------|
| GET | `/plans/features` |
| PUT | `/plans/features/{feature}` |
| POST | `/plans/create` |
| GET | `/plans/{plan}` |
| PUT | `/plans/{plan}` |
| DELETE | `/plans/{plan}` |
| PATCH | `/plans/{plan}/toggle-popular` |
| PATCH | `/plans/{plan}/toggle-status` |

**POST `/plans/create` and PUT `/plans/{plan}` — feature items:**

| Field | Required | Description |
|-------|----------|-------------|
| `features[].id` | yes | Feature catalog id |
| `features[].input_type` | yes | `text` or `boolean` |
| `features[].value` | yes | Feature value |
| `features[].label` | no | Plan-specific display label; omitted or empty → API uses catalog feature name |

**Plan feature in responses:** `label` (resolved display text), `input_type`, `value`, nested `features` (catalog id, name, key). Use **`label`** for UI copy on that plan.

---

### 6.15 Certificate types & certifications

| Method | Path |
|--------|------|
| GET | `/certificate-types` |
| POST | `/certificate-types/create` |
| GET | `/certificate-types/{certificateType}` |
| PUT | `/certificate-types/{certificateType}` |
| DELETE | `/certificate-types/{certificateType}` |
| GET | `/certifications` |
| GET | `/certifications/stats` |
| DELETE | `/certifications/{certificationId}` |

---

### 6.16 Article categories (resource routes)

| Method | Path |
|--------|------|
| GET | `/article/categories` |
| POST | `/article/categories` |
| GET | `/article/categories/{category}` |
| PUT | `/article/categories/{category}` |
| DELETE | `/article/categories/{category}` |

---

### 6.17 Promotions

| Method | Path |
|--------|------|
| GET | `/promotions` |
| GET | `/promotions/active` |
| POST | `/promotions/reset` |
| GET | `/promotions/{promotion}` |
| PUT | `/promotions/{promotion}` |
| PATCH | `/promotions/{promotion}/toggle-status` |
| GET | `/promotions/{promotion}/participants` |
| POST | `/promotions/{promotion}/enroll` |
| PATCH | `/promotions/{promotion}/participants/{user}` |

---

### 6.18 Articles (admin)

| Method | Path |
|--------|------|
| GET | `/articles/stats` |
| GET | `/articles` |
| POST | `/articles/create` |
| GET | `/articles/{article}` |
| PUT | `/articles/{article}` |
| DELETE | `/articles/{article}` |
| PATCH | `/articles/{article}/toggle-status` |

---

### 6.19 Contacts

| Method | Path |
|--------|------|
| GET | `/contacts` |
| GET | `/contacts/{contact}` |
| DELETE | `/contacts/{contact}` |
| PATCH | `/contacts/{contact}/read-status` |

---

### 6.20 Help center (admin)

| Method | Path |
|--------|------|
| GET | `/help-center/categories` |
| POST | `/help-center/categories/create` |
| GET | `/help-center/categories/{helpCenterCategory}` |
| PUT | `/help-center/categories/{helpCenterCategory}` |
| PUT | `/help-center/categories/{helpCenterCategory}/position` |
| DELETE | `/help-center/categories/{helpCenterCategory}` |
| GET | `/help-center/articles` |
| POST | `/help-center/articles/create` |
| GET | `/help-center/articles/{id}` |
| PUT | `/help-center/articles/{id}` |
| PUT | `/help-center/articles/{id}/position` |
| DELETE | `/help-center/articles/{id}` |

---

### 6.21 Orders (admin)

| Method | Path |
|--------|------|
| GET | `/orders/stats` |
| GET | `/orders` |
| GET | `/orders/{order}` |

---

## 7. Shared Resource Schemas

### 7.1 UserResource

```json
{
  "id": 1,
  "first_name": "Jane",
  "last_name": "Doe",
  "avatar": "https://.../storage/avatars/1.jpg",
  "email": "jane@example.com",
  "role": "Buyer",
  "status": "active",
  "status_label": "Active",
  "two_factor_enabled": false,
  "preferred_language": "en",
  "timezone": "UTC",
  "preferred_currency": { "code": "USD", "symbol": "$" },
  "quote_notification": true,
  "message_notification": true,
  "company": { "company_name": "...", "country": "...", "phone": "..." }
}
```

Manufacturer adds: `manufacture_status`, `subscription`, `factory_images`, `total_products`.

---

### 7.2 PublicSupplierResource

See §2.7.

---

### 7.3 ProductResource

See §2.6.

---

### 7.4 OrderResource

```json
{
  "id": 1,
  "order_number": "ORD-00001",
  "buyer_id": 2,
  "manufacturer_id": 5,
  "product_id": 10,
  "title": "Widget order",
  "quantity": 500,
  "quantity_unit": "pcs",
  "total_amount": 5250.0,
  "currency_code": "USD",
  "estimated_delivery_at": "2026-09-01",
  "status": "in_production",
  "status_label": "In production",
  "buyer": { "id": 2, "company_name": "Buyer Co" },
  "manufacturer": { "id": 5, "company_name": "Acme" },
  "product": {},
  "status_updates": [],
  "attachments": []
}
```

---

### 7.5 SubscriptionResource

```json
{
  "id": 1,
  "billing_interval": "month",
  "status": "trialing",
  "status_label": "Trialing",
  "starts_at": "2026-01-01T00:00:00.000000Z",
  "ends_at": "2026-07-01T00:00:00.000000Z",
  "trial_ends_at": "2026-07-01T00:00:00.000000Z",
  "auto_renew": false,
  "is_active": true,
  "source": "promotion",
  "promotion_id": 3,
  "days_remaining": 42,
  "plan": { "id": 2, "name": "Growth", "features": [] }
}
```

| Field | Description |
|-------|-------------|
| `is_active` | `true` when status is `active` or `trialing` and `ends_at` is not past |
| `source` | `purchase` (paid) or `promotion` (founding trial) |
| `promotion_id` | Linked promotion when `source` is `promotion`; `null` after paid renewal |
| `days_remaining` | Days until `ends_at`; `null` if no end date or already expired |

---

## Appendix A — Endpoint count summary

| Section | Approx. endpoints |
|---------|-------------------|
| Public | 45+ |
| Common (auth) | 25+ |
| Buyer | 25+ |
| Manufacturer | 45+ |
| Admin | 90+ |

---

## Appendix B — Export to PDF

**VS Code:** Install "Markdown PDF" extension → right-click this file → Export (pdf).

**Pandoc (CLI):**

```bash
pandoc docs/API_REFERENCE.md -o docs/API_REFERENCE.pdf --pdf-engine=wkhtmltopdf
pandoc docs/API_REFERENCE.md -o docs/API_REFERENCE.docx
```

**Word:** Open `.md` in Microsoft Word (File → Open) → Save As `.docx` or Print to PDF.

---

*This document reflects routes in `routes/api/v1/*.php` as of June 2026. For exact validation rules, see `app/Http/Requests/Api/V1/`.*
