# API Sync Webhook Documentation

This document describes the webhook endpoints available for triggering API synchronization operations.

## Base URL
```
http://your-domain.com/api/webhook/sync
```

## Available Endpoints

### 1. Sync Equipment
Synchronizes equipment data from the external API.

**Endpoint:** `GET /api/webhook/sync/equipment`

**Parameters:**
- `plant_codes` (optional): Comma-separated plant codes or array. If not provided, all active plants will be synced.

**Example:**
```bash
# Sync all active plants
curl "http://your-domain.com/api/webhook/sync/equipment"

# Sync specific plants
curl "http://your-domain.com/api/webhook/sync/equipment?plant_codes=P001,P002"
```

**Response:**
```json
{
  "success": true,
  "message": "Equipment sync completed",
  "data": {
    "processed": 150,
    "success": 150,
    "failed": 0
  }
}
```

---

### 2. Sync Running Time
Synchronizes running time data from the external API.

**Endpoint:** `GET /api/webhook/sync/running-time`

**Parameters:**
- `plant_codes` (optional): Comma-separated plant codes or array
- `start_date` (optional): Start date in YYYY-MM-DD format (default: 3 days ago)
- `end_date` (optional): End date in YYYY-MM-DD format (default: today)

**Example:**
```bash
# Sync with default date range (last 3 days)
curl "http://your-domain.com/api/webhook/sync/running-time"

# Sync with custom date range
curl "http://your-domain.com/api/webhook/sync/running-time?start_date=2025-10-01&end_date=2025-10-29"

# Sync specific plants with date range
curl "http://your-domain.com/api/webhook/sync/running-time?plant_codes=P001&start_date=2025-10-15&end_date=2025-10-29"
```

**Response:**
```json
{
  "success": true,
  "message": "Running time sync completed",
  "date_range": {
    "start": "2025-10-01",
    "end": "2025-10-29"
  },
  "data": {
    "processed": 420,
    "success": 420,
    "failed": 0
  }
}
```

---

### 3. Sync Work Orders
Synchronizes work order data from the external API.

**Endpoint:** `GET /api/webhook/sync/work-orders`

**Parameters:**
- `plant_codes` (optional): Comma-separated plant codes or array
- `start_date` (optional): Start date in YYYY-MM-DD format (default: 3 days ago)
- `end_date` (optional): End date in YYYY-MM-DD format (default: today)

**Example:**
```bash
# Sync with default date range
curl "http://your-domain.com/api/webhook/sync/work-orders"

# Sync with custom date range
curl "http://your-domain.com/api/webhook/sync/work-orders?start_date=2025-10-01&end_date=2025-10-29"
```

**Response:**
```json
{
  "success": true,
  "message": "Work orders sync completed",
  "date_range": {
    "start": "2025-10-01",
    "end": "2025-10-29"
  },
  "data": {
    "processed": 85,
    "success": 85,
    "failed": 0
  }
}
```

---

### 4. Sync Equipment Work Orders
Synchronizes equipment work order relationships from the external API.

**Endpoint:** `GET /api/webhook/sync/equipment-work-orders`

**Parameters:**
- `plant_codes` (optional): Comma-separated plant codes or array
- `start_date` (optional): Start date in YYYY-MM-DD format (default: 3 days ago)
- `end_date` (optional): End date in YYYY-MM-DD format (default: today)

**Example:**
```bash
curl "http://your-domain.com/api/webhook/sync/equipment-work-orders?start_date=2025-10-01&end_date=2025-10-29"
```

**Response:**
```json
{
  "success": true,
  "message": "Equipment work orders sync completed",
  "date_range": {
    "start": "2025-10-01",
    "end": "2025-10-29"
  },
  "data": {
    "processed": 120,
    "success": 120,
    "failed": 0
  }
}
```

---

### 5. Sync Equipment Materials
Synchronizes equipment material data from the external API.

**Endpoint:** `GET /api/webhook/sync/equipment-materials`

**Parameters:**
- `plant_codes` (optional): Comma-separated plant codes or array
- `start_date` (optional): Start date in YYYY-MM-DD format (default: 3 days ago)
- `end_date` (optional): End date in YYYY-MM-DD format (default: today)

**Example:**
```bash
curl "http://your-domain.com/api/webhook/sync/equipment-materials?start_date=2025-10-01&end_date=2025-10-29"
```

**Response:**
```json
{
  "success": true,
  "message": "Equipment materials sync completed",
  "date_range": {
    "start": "2025-10-01",
    "end": "2025-10-29"
  },
  "data": {
    "processed": 235,
    "success": 235,
    "failed": 0
  }
}
```

---

### 6. Sync Daily Plant Data
Synchronizes daily plant statistics from the external API.

**Endpoint:** `GET /api/webhook/sync/daily-plant-data`

**Parameters:**
- `plant_codes` (optional): Comma-separated plant codes or array
- `start_date` (optional): Start date in YYYY-MM-DD format (default: 3 days ago)
- `end_date` (optional): End date in YYYY-MM-DD format (default: today)

**Example:**
```bash
curl "http://your-domain.com/api/webhook/sync/daily-plant-data?start_date=2025-10-01&end_date=2025-10-29"
```

**Response:**
```json
{
  "success": true,
  "message": "Daily plant data sync completed",
  "date_range": {
    "start": "2025-10-01",
    "end": "2025-10-29"
  },
  "data": {
    "processed": 58,
    "success": 58,
    "failed": 0
  }
}
```

---

### 7. Sync All
Synchronizes all data types sequentially in the correct dependency order.

**Endpoint:** `GET /api/webhook/sync/all`

**Parameters:**
- `plant_codes` (optional): Comma-separated plant codes or array
- `start_date` (optional): Start date in YYYY-MM-DD format (default: 3 days ago)
- `end_date` (optional): End date in YYYY-MM-DD format (default: today)

**Sync Order:**
1. Equipment
2. Work Orders
3. Running Time
4. Equipment Work Orders
5. Equipment Materials
6. Daily Plant Data

**Example:**
```bash
# Sync all data types with default settings
curl "http://your-domain.com/api/webhook/sync/all"

# Sync all with custom date range
curl "http://your-domain.com/api/webhook/sync/all?start_date=2025-10-01&end_date=2025-10-29"

# Sync specific plants with date range
curl "http://your-domain.com/api/webhook/sync/all?plant_codes=P001,P002&start_date=2025-10-15&end_date=2025-10-29"
```

**Response:**
```json
{
  "success": true,
  "message": "Full sync completed",
  "date_range": {
    "start": "2025-10-01",
    "end": "2025-10-29"
  },
  "data": {
    "equipment": {
      "processed": 150,
      "success": 150,
      "failed": 0
    },
    "work_orders": {
      "processed": 85,
      "success": 85,
      "failed": 0
    },
    "running_time": {
      "processed": 420,
      "success": 420,
      "failed": 0
    },
    "equipment_work_orders": {
      "processed": 120,
      "success": 120,
      "failed": 0
    },
    "equipment_materials": {
      "processed": 235,
      "success": 235,
      "failed": 0
    },
    "daily_plant_data": {
      "processed": 58,
      "success": 58,
      "failed": 0
    }
  }
}
```

---

## Error Responses

When an error occurs, the API will return a 500 status code with the following format:

```json
{
  "success": false,
  "message": "Equipment sync failed",
  "error": "Connection timeout to external API"
}
```

---

## Usage with Cron/Schedulers

You can use these webhooks with cron jobs or external schedulers:

### Example Cron Setup (Linux)
```bash
# Sync equipment daily at 1:00 AM
0 1 * * * curl -s "http://your-domain.com/api/webhook/sync/equipment"

# Sync running time every 6 hours
0 */6 * * * curl -s "http://your-domain.com/api/webhook/sync/running-time"

# Full sync daily at 2:00 AM
0 2 * * * curl -s "http://your-domain.com/api/webhook/sync/all"
```

### Example with External Scheduler (e.g., Zapier, Make.com)
Set up HTTP GET requests to the webhook URLs with your desired schedule and parameters.

---

## Notes

1. **Plant Codes**: If not specified, all active plants in the database will be synchronized.

2. **Date Ranges**: 
   - Default range is 3 days ago to today
   - Dates must be in YYYY-MM-DD format
   - Start date must be before or equal to end date

3. **Logging**: All webhook calls are logged with details including IP address, plant codes, and date ranges.

4. **Dependencies**: The `/all` endpoint respects data dependencies and syncs in the correct order automatically.

5. **Timeout**: The sync service uses a configurable timeout (default: 300 seconds) from `config/ims.php`.

6. **Database Transactions**: Each sync operation is wrapped in a database transaction to ensure data consistency.

---

## Security Recommendations

For production use, consider adding authentication/authorization to these webhook endpoints:

1. Add API token validation
2. Implement IP whitelisting
3. Use Laravel Sanctum or Passport for API authentication
4. Add rate limiting to prevent abuse

Example with middleware:
```php
Route::prefix('webhook/sync')->middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    // ... webhook routes
});
```
