# Tenant Walkthrough (Real Data + Module Example)

## Scenario
- Tenant (landlord org): RoomGate Co.
- Tenant slug: `roomgate`
- User: Alice
- Alice role: `owner` in RoomGate Co.

---

## 1) Core tables with concrete rows

### `tenants`
| id | name        | slug     | status |
|----|-------------|----------|--------|
| 1  | RoomGate Co | roomgate | active |

```sql
INSERT INTO tenants (id, name, slug, status)
VALUES (1, 'RoomGate Co', 'roomgate', 'active');
```

### `users`
| id | name  | email           | status |
|----|-------|-----------------|--------|
| 10 | Alice | alice@gmail.com | active |

```sql
INSERT INTO users (id, name, email, password, status)
VALUES (10, 'Alice', 'alice@gmail.com', '$2y$10$hash...', 'active');
```

### `tenant_users` (membership)
| tenant_id | user_id | role  | status |
|-----------|---------|-------|--------|
| 1         | 10      | owner | active |

```sql
INSERT INTO tenant_users (tenant_id, user_id, role, status)
VALUES (1, 10, 'owner', 'active');
```

---

## 2) What happens in the browser (real URL)

URL:
```
http://localhost:8000/t/roomgate/dashboard
```

Breakdown:
- `/t` = tenant area (prefix)
- `roomgate` = tenant slug
- `/dashboard` = page

---

## 3) What Laravel does step-by-step (exact flow)

### Step A: Route matches
```php
Route::get('/t/{tenant}/dashboard', ...);
```

Laravel assigns:
- `{tenant}` = `"roomgate"`

### Step B: Middleware runs (`SetTenant`)

1) Read slug from URL
```php
$slug = $request->route('tenant'); // "roomgate"
```

2) Find tenant in DB
```php
$tenant = Tenant::where('slug', 'roomgate')->firstOrFail();
```

Result:
- `$tenant->id = 1`
- `$tenant->name = "RoomGate Co"`

3) Check if Alice belongs to this tenant
```php
$isMember = DB::table('tenant_users')
  ->where('tenant_id', 1)
  ->where('user_id', 10)
  ->where('status', 'active')
  ->exists();
```

This returns `true` because the row exists.

4) Save tenant as current tenant
```php
app(CurrentTenant::class)->set($tenant);
```

Now the app knows:
- Current tenant = RoomGate (id=1)

### Step C: Controller runs
```php
public function index(CurrentTenant $currentTenant)
{
    return view('dashboard::dashboard', [
        'tenant' => $currentTenant->get(),
    ]);
}
```

So the view receives:
- `$tenant->name = "RoomGate Co"`
- `$tenant->slug = "roomgate"`

### Step D: Layout + view render
- Tenant name in sidebar: RoomGate Co
- Links like `/t/roomgate/properties`
- Dashboard content: “Welcome to RoomGate Co dashboard.”

**Summary:** `/t/{slug}` tells your app which landlord account, middleware verifies access, then every module loads data using that tenant.

---

## 4) Tenant isolation example (blocked access)

Another tenant exists:

### `tenants`
| id | name     | slug     |
|----|----------|----------|
| 2  | BlueLand | blueland |

Alice is not in `tenant_users` for tenant_id = 2.

If Alice tries:
```
/t/blueland/dashboard
```

Middleware does:
- find tenant id=2
- membership check fails
- returns **403**: You are not a member of this tenant

---

## 5) Module wiring (Dashboard + Property)

### CoreServiceProvider binding (example)
```php
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentTenant::class, fn () => new CurrentTenant());
    }
}
```

### Register middleware alias in global Kernel
File: `app/Http/Kernel.php`
```php
protected $routeMiddleware = [
    // ...
    'tenant' => \Modules\Core\App\Http\Middleware\SetTenant::class,
];
```

Note: Namespace casing must match your module folder structure.

---

## 6) Dashboard module

### Route
File: `Modules/Dashboard/routes/web.php`
```php
Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('t/{tenant}')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('tenant.dashboard');
    });
```

### Controller
File: `Modules/Dashboard/app/Http/Controllers/DashboardController.php`
```php
namespace Modules\Dashboard\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Core\App\Services\CurrentTenant;

class DashboardController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        return view('dashboard::dashboard', [
            'tenant' => $currentTenant->get(),
        ]);
    }
}
```

### Layout (app shell)
File: `Modules/Dashboard/resources/views/layouts/app.blade.php`
```php
<title>{{ $title ?? 'Dashboard' }}</title>
</head>
<body style="font-family: sans-serif;">
    <div style="display:flex; min-height:100vh;">
        <!-- Sidebar -->
        <aside style="width:240px; padding:16px; border-right:1px solid #ddd;">
            <div style="font-weight:bold; margin-bottom:12px;">
                Tenant: {{ $tenant->name ?? 'Unknown' }}
            </div>
            <nav style="display:flex; flex-direction:column; gap:8px;">
                <a href="{{ route('tenant.dashboard', ['tenant' => $tenant->slug]) }}">Dashboard</a>
                <a href="{{ route('tenant.properties.index', ['tenant' => $tenant->slug]) }}">Properties</a>
            </nav>
        </aside>

        <!-- Main -->
        <main style="flex:1; padding:16px;">
            <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h2 style="margin:0;">{{ $title ?? '' }}</h2>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            </header>

            @yield('content')
        </main>
    </div>
</body>
</html>
```

### Dashboard view
File: `Modules/Dashboard/resources/views/dashboard.blade.php`
```php
@extends('dashboard::layouts.app')
@php
    $title = 'Dashboard';
@endphp

@section('content')
    <p>Welcome to {{ $tenant->name }} dashboard.</p>
@endsection
```

---

## 7) Property module

### Route
File: `Modules/Property/routes/web.php`
```php
Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('t/{tenant}')
    ->group(function () {
        Route::get('/properties', [PropertyController::class, 'index'])
            ->name('tenant.properties.index');
    });
```

### Controller
File: `Modules/Property/app/Http/Controllers/PropertyController.php`
```php
namespace Modules\Property\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Core\App\Services\CurrentTenant;

class PropertyController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        // Example only: later you will query properties table with tenant_id.
        $fakeProperties = [
            ['name' => 'Sunrise Apartment'],
            ['name' => 'Blue Condo'],
        ];

        return view('property::index', [
            'tenant' => $currentTenant->get(),
            'properties' => $fakeProperties,
            'title' => 'Properties',
        ]);
    }
}
```

### View (uses dashboard layout)
File: `Modules/Property/resources/views/index.blade.php`
```php
@extends('dashboard::layouts.app')
@section('content')
    <ul>
        @foreach ($properties as $p)
            <li>{{ $p['name'] }}</li>
        @endforeach
    </ul>
@endsection
```

---

## 8) Example domain flows (data + behavior)

### Create a property
`properties`
| id | tenant_id | name           | status |
|----|-----------|----------------|--------|
| 5  | 1         | RoomGate Tower | active |

```sql
INSERT INTO properties (id, tenant_id, name, status)
VALUES (5, 1, 'RoomGate Tower', 'active');
```

Request:
```
POST /t/roomgate/properties
```

Expected checks:
- User is logged in.
- CurrentTenant == 1.
- User has role owner/admin for this tenant.
- Create property with tenant_id = 1.

### Add a room type and a room
`room_types`
| id | tenant_id | name     | status |
|----|-----------|----------|--------|
| 2  | 1         | Standard | active |

```sql
INSERT INTO room_types (id, tenant_id, name, status)
VALUES (2, 1, 'Standard', 'active');
```

`rooms`
| id | tenant_id | property_id | room_type_id | room_number | status    |
|----|-----------|-------------|--------------|-------------|-----------|
| 20 | 1         | 5           | 2            | 101         | available |

```sql
INSERT INTO rooms (id, tenant_id, property_id, room_type_id, room_number, status)
VALUES (20, 1, 5, 2, '101', 'available');
```

### Create a contract (lease)
`contracts`
| id  | tenant_id | occupant_user_id | room_id | start_date | end_date   | status |
|-----|-----------|------------------|---------|------------|------------|--------|
| 100 | 1         | 10               | 20      | 2026-01-01 | 2026-12-31 | active |

```sql
INSERT INTO contracts (id, tenant_id, occupant_user_id, room_id, start_date, end_date, status)
VALUES (100, 1, 10, 20, '2026-01-01', '2026-12-31', 'active');
```

Expected checks:
- Room is available.
- No overlapping contract for the same room.
- Occupant belongs to tenant (tenant_users row exists).

### Generate an invoice from contract
`invoices`
| id  | tenant_id | contract_id | status | total_cents | currency_code |
|-----|-----------|-------------|--------|-------------|---------------|
| 500 | 1         | 100         | open   | 50000       | USD           |

`invoice_items`
| id  | tenant_id | invoice_id | description  | amount_cents |
|-----|-----------|------------|--------------|--------------|
| 501 | 1         | 500        | Monthly rent | 50000        |

```sql
INSERT INTO invoices (id, tenant_id, contract_id, status, total_cents, currency_code)
VALUES (500, 1, 100, 'open', 50000, 'USD');

INSERT INTO invoice_items (id, tenant_id, invoice_id, description, amount_cents)
VALUES (501, 1, 500, 'Monthly rent', 50000);
```

### Record payment and allocation
`payments`
| id  | tenant_id | payer_user_id | amount_cents | status |
|-----|-----------|---------------|--------------|--------|
| 900 | 1         | 10            | 50000        | paid   |

`payment_allocations`
| id  | tenant_id | payment_id | invoice_id | applied_cents |
|-----|-----------|------------|------------|---------------|
| 901 | 1         | 900        | 500        | 50000         |

```sql
INSERT INTO payments (id, tenant_id, payer_user_id, amount_cents, status)
VALUES (900, 1, 10, 50000, 'paid');

INSERT INTO payment_allocations (id, tenant_id, payment_id, invoice_id, applied_cents)
VALUES (901, 1, 900, 500, 50000);
```

Result:
- Invoice 500 becomes paid.
- Contract 100 remains active.
- Room 20 stays occupied.

### Maintenance request (tenant side)
`maintenance_requests`
| id  | tenant_id | created_by_user_id | room_id | status |
|-----|-----------|--------------------|---------|--------|
| 300 | 1         | 10                 | 20      | open   |

```sql
INSERT INTO maintenance_requests (id, tenant_id, created_by_user_id, room_id, status)
VALUES (300, 1, 10, 20, 'open');
```

Expected checks:
- Request belongs to tenant_id = 1.
- User is a member of tenant_id = 1.

---

## 9) Flutter API flow (Sanctum)

Login:
```
POST /api/login
```
Body:
```json
{
  "email": "alice@gmail.com",
  "password": "secret"
}
```

Response:
```json
{
  "token": "plain_text_token",
  "user": { "id": 10, "name": "Alice" }
}
```

Use token:
```
Authorization: Bearer plain_text_token
```

Protected call:
```
GET /api/tenants/roomgate/properties
```

Expected:
- Authenticated user id = 10
- Resolve tenant slug `roomgate` -> tenant_id = 1
- Confirm tenant_users row exists
- Return only properties where tenant_id = 1

---

## 10) One-time check: module views and routes are loaded

Make sure each module's RouteServiceProvider loads its `routes/web.php` and the module service provider loads views.
Usually nwidart already does this.

After adding files, run:
```
composer dump-autoload
php artisan optimize:clear
php artisan route:list
```

You should see routes like:
- `tenant.dashboard`
- `tenant.properties.index`

How to test quickly:
1) Create a tenant row with slug `roomgate`.
2) Create a user and log in.
3) Insert membership:
```sql
INSERT INTO tenant_users (tenant_id, user_id, role, status)
VALUES (1, 1, 'owner', 'active');
```

4) Visit:
- `/t/roomgate/dashboard`
- `/t/roomgate/properties`

If you paste your actual module folder casing (sometimes `Modules/Core/app` vs `Modules/Core/App`),
I can adjust the namespaces exactly so you will not hit class-not-found errors.
