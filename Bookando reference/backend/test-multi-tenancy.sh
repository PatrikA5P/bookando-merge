#!/bin/bash

# Multi-Tenancy Test Script
# This script proves that organization data is isolated

set -e  # Exit on error

API_URL="http://localhost:3001/api"

echo "🧪 Multi-Tenancy Test"
echo "===================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Step 1: Create two organizations
echo -e "${BLUE}Step 1: Creating two organizations${NC}"

ORG_A=$(curl -s -X POST "${API_URL}/../health/db" | grep -q "connected" && echo "org_a_$(date +%s)" || echo "org_a_manual")
ORG_B=$(curl -s -X POST "${API_URL}/../health/db" | grep -q "connected" && echo "org_b_$(date +%s)" || echo "org_b_manual")

# We'll create orgs via Prisma Studio or manually
echo "⚠️  Please create two organizations manually:"
echo "   1. Open Prisma Studio: cd backend && npx prisma studio"
echo "   2. Go to 'Organization' table"
echo "   3. Create two organizations:"
echo "      - Org A: name='Fahrschule Müller', email='mueller@example.com'"
echo "      - Org B: name='Fahrschule Schmidt', email='schmidt@example.com'"
echo ""
echo "   Copy the IDs and paste them here:"
read -p "   Organization A ID: " ORG_A_ID
read -p "   Organization B ID: " ORG_B_ID

echo ""

# Step 2: Register users for both organizations
echo -e "${BLUE}Step 2: Registering users${NC}"

echo "Registering User A..."
USER_A_RESPONSE=$(curl -s -X POST "${API_URL}/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@muller.com",
    "password": "password123",
    "firstName": "Hans",
    "lastName": "Müller",
    "organizationId": "'"$ORG_A_ID"'"
  }')

USER_A_TOKEN=$(echo $USER_A_RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$USER_A_TOKEN" ]; then
  echo -e "${RED}❌ Failed to register User A${NC}"
  echo "Response: $USER_A_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✅ User A registered${NC}"

echo "Registering User B..."
USER_B_RESPONSE=$(curl -s -X POST "${API_URL}/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@schmidt.com",
    "password": "password123",
    "firstName": "Klaus",
    "lastName": "Schmidt",
    "organizationId": "'"$ORG_B_ID"'"
  }')

USER_B_TOKEN=$(echo $USER_B_RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$USER_B_TOKEN" ]; then
  echo -e "${RED}❌ Failed to register User B${NC}"
  echo "Response: $USER_B_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✅ User B registered${NC}"
echo ""

# Step 3: Create customers for Organization A
echo -e "${BLUE}Step 3: Creating customers for Organization A${NC}"

CUSTOMER_A1=$(curl -s -X POST "${API_URL}/customers" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_A_TOKEN" \
  -d '{
    "firstName": "Max",
    "lastName": "Mustermann",
    "email": "max@example.com",
    "phone": "+41 79 123 45 67"
  }')

CUSTOMER_A2=$(curl -s -X POST "${API_URL}/customers" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_A_TOKEN" \
  -d '{
    "firstName": "Anna",
    "lastName": "Meier",
    "email": "anna@example.com",
    "phone": "+41 79 234 56 78"
  }')

echo -e "${GREEN}✅ Created 2 customers for Org A${NC}"
echo ""

# Step 4: Create customers for Organization B
echo -e "${BLUE}Step 4: Creating customers for Organization B${NC}"

CUSTOMER_B1=$(curl -s -X POST "${API_URL}/customers" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_B_TOKEN" \
  -d '{
    "firstName": "Peter",
    "lastName": "Weber",
    "email": "peter@example.com",
    "phone": "+41 79 345 67 89"
  }')

CUSTOMER_B2=$(curl -s -X POST "${API_URL}/customers" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_B_TOKEN" \
  -d '{
    "firstName": "Lisa",
    "lastName": "Fischer",
    "email": "lisa@example.com",
    "phone": "+41 79 456 78 90"
  }')

echo -e "${GREEN}✅ Created 2 customers for Org B${NC}"
echo ""

# Step 5: Verify isolation
echo -e "${BLUE}Step 5: Verifying Multi-Tenancy Isolation${NC}"

echo "Fetching customers for Organization A..."
CUSTOMERS_A=$(curl -s -X GET "${API_URL}/customers" \
  -H "Authorization: Bearer $USER_A_TOKEN")

COUNT_A=$(echo $CUSTOMERS_A | grep -o '"count":[0-9]*' | sed 's/"count"://')

echo "Organization A sees: $COUNT_A customers"
echo "$CUSTOMERS_A" | grep -o '"firstName":"[^"]*"' | sed 's/"firstName":"//; s/"//'

echo ""

echo "Fetching customers for Organization B..."
CUSTOMERS_B=$(curl -s -X GET "${API_URL}/customers" \
  -H "Authorization: Bearer $USER_B_TOKEN")

COUNT_B=$(echo $CUSTOMERS_B | grep -o '"count":[0-9]*' | sed 's/"count"://')

echo "Organization B sees: $COUNT_B customers"
echo "$CUSTOMERS_B" | grep -o '"firstName":"[^"]*"' | sed 's/"firstName":"//; s/"//'

echo ""

# Verify
if [ "$COUNT_A" = "2" ] && [ "$COUNT_B" = "2" ]; then
  echo -e "${GREEN}✅ SUCCESS: Multi-Tenancy is working!${NC}"
  echo -e "${GREEN}   Each organization sees only their own customers.${NC}"
else
  echo -e "${RED}❌ FAILED: Multi-Tenancy isolation not working${NC}"
  echo "   Org A should see 2 customers, saw: $COUNT_A"
  echo "   Org B should see 2 customers, saw: $COUNT_B"
  exit 1
fi

echo ""
echo "🎉 All tests passed!"
