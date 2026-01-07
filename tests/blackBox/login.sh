#!/bin/bash

# Konfigurasi
BASE_URL="http://127.0.0.1:8000"
COOKIE_FILE="cookie.txt"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'
# --- SCENARIO: LOGIN ---
echo -e "Mengirim data login ke $BASE_URL/login via POST..."

# Kita gunakan -X POST untuk memastikan methodnya benar
RESPONSE=$(curl -s -c $COOKIE_FILE -L \
    -X POST \
    -d "email=admin@gmail.com" \
    -d "password=password" \
    "$BASE_URL/login")

# Simpan respon untuk pengecekan manual
echo "$RESPONSE" > debug_post_result.html

# --- VERIFIKASI ---
# Karena route login kamu cuma POST, kita cek apakah hasilnya mengarah ke dashboard
if echo "$RESPONSE" | grep -qiE "dashboard|logout|profile|Selamat Datang"; then
    echo -e "${GREEN}[SUCCESS] Login Berhasil! Berhasil masuk ke sistem.${NC}"
else
    echo -e "${RED}[FAIL] Login Gagal.${NC}"
    echo "Server tidak memberikan akses ke Dashboard."
    echo "Silakan buka file 'debug_post_result.html' untuk melihat jawaban server."
fi
echo "$RESPONSE"
echo -e "\n${YELLOW}=== TEST SELESAI ===${NC}"