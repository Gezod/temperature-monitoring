#!/bin/bash

BASE_URL="http://localhost:8000"

GREEN="\e[32m"
RED="\e[31m"
NC="\e[0m"

check_status () {
  if [[ "$1" == "$2" ]]; then
    echo -e "${GREEN}✓ BERHASIL${NC} (HTTP $1)"
  else
    echo -e "${RED}✗ GAGAL${NC} (HTTP $1, expected $2)"
  fi
}

echo "======================================"
echo " BLACKBOX TESTING ITERASI 4"
echo " Analytics, Anomaly & Reporting"
echo "======================================"

echo ""
echo "[1] Filter Analytics (Branch + Date Range)"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
"$BASE_URL/analytics?branch=Surabaya&start_date=2025-08-12&end_date=2025-08-14")
check_status $STATUS 200

echo ""
echo "[2] Filter Validation (Missing Date)"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
"$BASE_URL/analytics?branch=Surabaya")
check_status $STATUS 422

echo ""
echo "[3] Input Temperature (Trigger Anomaly)"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
-X POST "$BASE_URL/temperature" \
-H "Content-Type: application/json" \
-d '{
  "branch": "Surabaya",
  "machine": "Compressor A",
  "temperature": 45.0
}')
check_status $STATUS 201

echo ""
echo "[4] Get Anomaly List"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
"$BASE_URL/anomalies")
check_status $STATUS 200

echo ""
echo "[5] Resolve Anomaly (ID = 1)"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
-X PATCH "$BASE_URL/anomalies/1/resolve")
check_status $STATUS 200

echo ""
echo "[6] Export PDF Report"
curl -s -L \
"$BASE_URL/export/pdf?branch=Surabaya&start_date=2025-08-12&end_date=2025-08-14" \
-o laporan_suhu.pdf

if [[ -f "laporan_suhu.pdf" ]]; then
  echo -e "${GREEN}✓ BERHASIL${NC} (PDF berhasil diunduh)"
else
  echo -e "${RED}✗ GAGAL${NC} (PDF tidak ditemukan)"
fi

echo ""
echo "[7] Export Excel Report"
curl -s -L \
"$BASE_URL/export/excel?branch=Surabaya&start_date=2025-08-12&end_date=2025-08-14" \
-o laporan_suhu.xlsx

if [[ -f "laporan_suhu.xlsx" ]]; then
  echo -e "${GREEN}✓ BERHASIL${NC} (Excel berhasil diunduh)"
else
  echo -e "${RED}✗ GAGAL${NC} (Excel tidak ditemukan)"
fi

echo ""
echo "======================================"
echo " BLACKBOX TESTING SELESAI"
echo "======================================"
