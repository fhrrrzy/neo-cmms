# Webhook API Documentation

## Security

All webhook endpoints are protected by API key authentication. You can provide the API key in two ways:

1. **Header**: `X-Webhook-Key: your-api-key-here`
2. **Query Parameter**: `?api_key=your-api-key-here`

## Setup

### 1. Generate API Key

```bash
php artisan webhook:test --generate-key
```

This will generate a secure 64-character random string.

### 2. Add to .env

```bash
WEBHOOK_API_KEY=your-generated-key-here
```

### 3. Test Endpoints

```bash
# List all available endpoints
php artisan webhook:test

# Test a specific endpoint (with interactive execution)
php artisan webhook:test equipment
php artisan webhook:test running-time
php artisan webhook:test all
```

## Available Endpoints

### 1. Sync Equipment
```bash
GET /api/webhook/sync/equipment
```

**Example:**
```bash
curl -X GET \
  'http://localhost:8988/api/webhook/sync/equipment' \
  -H 'X-Webhook-Key: YOUR_API_KEY'
```

**Optional Parameters:**
- `plant_codes` (array or comma-separated): Specific plant codes to sync

**Response:**
```json
{
  "success": true,
  "message": "Equipment sync completed",
  "data": {
    "synced": 150,
    "failed": 0
  }
}
```

---

### 2. Sync Running Time
```bash
GET /api/webhook/sync/running-time
```

**Example:**
```bash
curl -X GET \
  'http://localhost:8988/api/webhook/sync/running-time?start_date=2025-01-26&end_date=2025-01-29' \
  -H 'X-Webhook-Key: YOUR_API_KEY'
```

**Optional Parameters:**
- `start_date` (YYYY-MM-DD): Default is 3 days ago
- `end_date` (YYYY-MM-DD): Default is today
- `plant_codes` (array or comma-separated): Specific plant codes

---

### 3. Sync Work Orders
```bash
GET /api/webhook/sync/work-orders
```

**Example:**
```bash
curl -X GET \
  'http://localhost:8988/api/webhook/sync/work-orders' \
  -H 'X-Webhook-Key: YOUR_API_KEY' \
  -G \
  -d 'start_date=2025-01-26' \
  -d 'end_date=2025-01-29'
```

---

### 4. Sync Equipment Work Orders
```bash
GET /api/webhook/sync/equipment-work-orders
```

**Example:**
```bash
curl -X GET \
  'http://localhost:8988/api/webhook/sync/equipment-work-orders?api_key=YOUR_API_KEY&start_date=2025-01-26&end_date=2025-01-29'
```

---

### 5. Sync Equipment Materials
```bash
GET /api/webhook/sync/equipment-materials
```

**Example:**
```bash
curl -X GET \
  'http://localhost:8988/api/webhook/sync/equipment-materials' \
  -H 'X-Webhook-Key: YOUR_API_KEY' \
  -G \
  -d 'start_date=2025-01-26' \
  -d 'end_date=2025-01-29'
```

---

### 6. Sync Daily Plant Data
```bash
GET /api/webhook/sync/daily-plant-data
```

**Example:**
```bash
curl -X GET \
  'http://localhost:8988/api/webhook/sync/daily-plant-data' \
  -H 'X-Webhook-Key: YOUR_API_KEY' \
  -G \
  -d 'start_date=2025-01-26' \
  -d 'end_date=2025-01-29'
```

---

### 7. Sync All Data (Sequential)
```bash
GET /api/webhook/sync/all
```

This endpoint syncs all data types in the correct sequence:
1. Equipment
2. Running Time
3. Work Orders
4. Equipment Work Orders
5. Equipment Materials
6. Daily Plant Data

**Example:**
```bash
curl -X GET \
  'http://localhost:8988/api/webhook/sync/all' \
  -H 'X-Webhook-Key: YOUR_API_KEY' \
  -G \
  -d 'start_date=2025-01-26' \
  -d 'end_date=2025-01-29'
```

**Response:**
```json
{
  "success": true,
  "message": "Full sync completed",
  "date_range": {
    "start": "2025-01-26",
    "end": "2025-01-29"
  },
  "data": {
    "equipment": { "synced": 150, "failed": 0 },
    "running_time": { "synced": 450, "failed": 0 },
    "work_orders": { "synced": 89, "failed": 0 },
    "equipment_work_orders": { "synced": 234, "failed": 0 },
    "equipment_materials": { "synced": 567, "failed": 0 },
    "daily_plant_data": { "synced": 12, "failed": 0 }
  }
}
```

---

## Error Responses

### 401 Unauthorized (Invalid API Key)
```json
{
  "success": false,
  "message": "Unauthorized - Invalid API key"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Equipment sync failed",
  "error": "Connection timeout to external API"
}
```

---

## Best Practices

1. **Use Header Authentication**: Preferred over query parameters for security
2. **Monitor Logs**: Check `storage/logs/laravel.log` for sync details
3. **Validate Date Ranges**: Always check start_date <= end_date
4. **Specific Plant Codes**: Use `plant_codes` parameter to limit sync scope
5. **Sequential Sync**: Use `/all` endpoint for complete data synchronization

---

## Integration Examples

### Shell Script (Cron Job)
```bash
#!/bin/bash

API_KEY="your-api-key-here"
BASE_URL="http://localhost:8988"

# Daily sync at 2 AM
curl -X GET \
  "${BASE_URL}/api/webhook/sync/all" \
  -H "X-Webhook-Key: ${API_KEY}" \
  -G \
  -d "start_date=$(date -d '3 days ago' +%Y-%m-%d)" \
  -d "end_date=$(date +%Y-%m-%d)" \
  >> /var/log/cmms-sync.log 2>&1
```

### Python Script
```python
import requests
from datetime import datetime, timedelta

API_KEY = "your-api-key-here"
BASE_URL = "http://localhost:8988"

headers = {
    "X-Webhook-Key": API_KEY,
    "Accept": "application/json"
}

params = {
    "start_date": (datetime.now() - timedelta(days=3)).strftime("%Y-%m-%d"),
    "end_date": datetime.now().strftime("%Y-%m-%d")
}

response = requests.get(
    f"{BASE_URL}/api/webhook/sync/all",
    headers=headers,
    params=params
)

print(response.json())
```

### PHP Script
```php
<?php

$apiKey = 'your-api-key-here';
$baseUrl = 'http://localhost:8988';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/webhook/sync/all?start_date=' . date('Y-m-d', strtotime('-3 days')) . '&end_date=' . date('Y-m-d'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Webhook-Key: ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP {$httpCode}\n";
echo $response;
```
