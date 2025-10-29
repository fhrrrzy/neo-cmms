#!/bin/bash

# Webhook Test Script
# Usage: ./test-webhook.sh [endpoint] [start_date] [end_date]

# Load API key from .env
if [ -f .env ]; then
    export $(grep WEBHOOK_API_KEY .env | xargs)
fi

# Configuration
API_KEY="${WEBHOOK_API_KEY}"
BASE_URL="${APP_URL:-http://localhost:8000}"
ENDPOINT="${1:-equipment}"
START_DATE="${2:-$(date -d '3 days ago' +%Y-%m-%d)}"
END_DATE="${3:-$(date +%Y-%m-%d)}"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== CMMS Webhook Test ===${NC}"
echo ""

# Check if API key is set
if [ -z "$API_KEY" ]; then
    echo -e "${RED}Error: WEBHOOK_API_KEY not found in .env${NC}"
    echo ""
    echo "Generate one using:"
    echo "  php artisan webhook:test --generate-key"
    echo ""
    echo "Then add to .env:"
    echo "  WEBHOOK_API_KEY=your-generated-key"
    exit 1
fi

# Build URL
URL="${BASE_URL}/api/webhook/sync/${ENDPOINT}"

echo -e "${GREEN}Testing endpoint:${NC} ${ENDPOINT}"
echo -e "${GREEN}URL:${NC} ${URL}"
echo -e "${GREEN}Date range:${NC} ${START_DATE} to ${END_DATE}"
echo ""

# Add date parameters if applicable
if [[ "$ENDPOINT" != "equipment" ]]; then
    QUERY_PARAMS="start_date=${START_DATE}&end_date=${END_DATE}"
else
    QUERY_PARAMS=""
fi

# Show curl command
echo -e "${YELLOW}Curl command:${NC}"
if [ -z "$QUERY_PARAMS" ]; then
    echo "curl -X GET \\"
    echo "  '${URL}' \\"
    echo "  -H 'X-Webhook-Key: ${API_KEY}' \\"
    echo "  -H 'Accept: application/json'"
else
    echo "curl -X GET \\"
    echo "  '${URL}?${QUERY_PARAMS}' \\"
    echo "  -H 'X-Webhook-Key: ${API_KEY}' \\"
    echo "  -H 'Accept: application/json'"
fi
echo ""

# Ask for confirmation
read -p "Execute this request? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Executing...${NC}"
    echo ""
    
    # Execute curl
    if [ -z "$QUERY_PARAMS" ]; then
        RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X GET \
            "${URL}" \
            -H "X-Webhook-Key: ${API_KEY}" \
            -H "Accept: application/json")
    else
        RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X GET \
            "${URL}?${QUERY_PARAMS}" \
            -H "X-Webhook-Key: ${API_KEY}" \
            -H "Accept: application/json")
    fi
    
    # Extract HTTP code and body
    HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
    BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')
    
    # Display results
    echo -e "${GREEN}HTTP Status:${NC} ${HTTP_CODE}"
    echo ""
    echo -e "${YELLOW}Response:${NC}"
    echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
    echo ""
    
    # Success/failure message
    if [[ "$HTTP_CODE" == "200" ]]; then
        echo -e "${GREEN}✓ Request successful${NC}"
    else
        echo -e "${RED}✗ Request failed${NC}"
    fi
else
    echo "Cancelled."
fi

echo ""
echo -e "${YELLOW}Available endpoints:${NC}"
echo "  equipment, running-time, work-orders, equipment-work-orders,"
echo "  equipment-materials, daily-plant-data, all"
echo ""
echo -e "${YELLOW}Usage examples:${NC}"
echo "  ./test-webhook.sh equipment"
echo "  ./test-webhook.sh running-time 2025-10-26 2025-10-29"
echo "  ./test-webhook.sh all"
