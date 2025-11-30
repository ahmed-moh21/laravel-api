# Flash-Sale Checkout API (Laravel 12)

This repository contains an implementation of the Flash-Sale Checkout Interview Task.  
The project focuses on **correct stock handling under high concurrency**, **short-lived reservation holds**, and an **idempotent and out-of-order-safe payment webhook**.  
It uses **Laravel 12**, **MySQL (InnoDB)**, and any Laravel-supported cache driver.

---

##  Objective

The goal is to simulate a flash-sale flow where many users attempt to purchase a limited-stock product simultaneously.  
Key requirements include preventing overselling, reserving stock via holds, processing orders, and handling payment webhooks safely— even when duplicated or delivered out of order.

---

##  Features Implemented

###  Product Endpoint  
**GET /api/products/{id}**  
Returns product details including **real-time available stock** (total stock minus active unexpired holds).  
Reads are cached for performance and invalidated when stock changes.

###  Hold Creation (Temporary Reservation)  
**POST /api/holds**  
Request body:  
```json
{ "product_id": 1, "qty": 1 }
```  
Creates a temporary reservation lasting ~2 minutes.  
Holds immediately reduce available stock for other users.  
Overselling is prevented through database transactions and row-level locking.

Returns:  
```json
{ "hold_id": X, "expires_at": "timestamp" }
```

###  Order Creation  
**POST /api/orders**  
Request body:  
```json
{ "hold_id": X }
```  
Validates:  
- Hold exists  
- Hold is not expired  
- Hold has not been used before  

Creates a new order in *pending payment* state.

###  Payment Webhook (Idempotent + Out-of-Order Safe)  
**POST /api/payments/webhook**  
Uses header:  
```
Idempotency-Key: <uuid>
```  
Behavior:  
- Duplicate webhooks are ignored safely  
- Webhook may arrive before the order creation response  
- Final order state is always correct  
  - `success` → order marked **paid**  
  - `failed` → order **cancelled** and stock restored  

---

##  Concurrency & Data Integrity Strategy

- **MySQL row-level locking (`SELECT ... FOR UPDATE`)** ensures stock cannot be oversold.  
- Holds and orders are wrapped in **database transactions** to avoid race conditions.  
- Holds auto-expire via:  
  - Lazy cleanup when accessed  
  - Scheduled cleanup (`schedule:work`)  
- Webhook idempotency is enforced by storing and checking idempotency keys.  
- Cache invalidation ensures no stale stock values are ever returned.

---

##  Installation & Running Locally

### 1. Clone the repository
```bash
git clone https://github.com/ahmed-moh21/laravel-api.git
cd laravel-api
```

### 2. Install dependencies
```bash
composer install
```

### 3. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```
Update `.env` with database credentials and choose cache driver (`database`, `redis`, or `file`).

If using the database cache driver:
```bash
php artisan cache:table
```

### 4. Run migrations and seeders
```bash
php artisan migrate
php artisan db:seed
```

### 5. Start the application
```bash
php artisan serve
```

### 6. Start scheduler for hold expiry
```bash
php artisan schedule:work
```

---

## Running Tests

Run the full test suite:
```bash
php artisan test
```

Tests cover:
- Parallel hold creation at stock limit (no oversell)  
- Hold expiry restoring availability  
- Webhook idempotency (duplicate webhooks safe)  
- Webhook arriving before order creation response  

---

##  Project Structure Overview

- **app/** — Controllers, Models, Services,Console
- **routes/api.php** — API routes  
- **database/migrations/** — DB schema  
- **database/seeders/** — initial product seeding  
- **tests/** — automated concurrency & webhook tests  
