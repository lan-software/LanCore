#!/usr/bin/env bash
#
# Initialise Garage for local development.
#
# Run this ONCE after the first `sail up -d`:
#   ./contrib/garage-init.sh
#
# It will:
#   1. Wait for Garage to be healthy
#   2. Assign the single node to a layout and apply it
#   3. Create the "lancore" bucket
#   4. Create an API key named "lancore"
#   5. Grant the key full access to the bucket
#   6. Print the credentials to paste into your .env
#
set -euo pipefail

COMPOSE_CMD="docker compose"
GARAGE_SERVICE="garage"
GARAGE="$COMPOSE_CMD exec $GARAGE_SERVICE /garage"
BUCKET_NAME="lancore"
KEY_NAME="lancore"

echo "==> Waiting for Garage to become healthy..."
for i in $(seq 1 30); do
    if $GARAGE status >/dev/null 2>&1; then
        echo "    Garage is up."
        break
    fi
    if [ "$i" -eq 30 ]; then
        echo "    ERROR: Garage did not start in time." >&2
        exit 1
    fi
    sleep 2
done

echo "==> Configuring cluster layout..."
NODE_ID=$($GARAGE status 2>/dev/null | grep 'NO ROLE ASSIGNED' | awk '{print $1}')
if [ -n "$NODE_ID" ]; then
    $GARAGE layout assign -z dc1 -c 1G "$NODE_ID"
    VERSION=$($GARAGE layout show 2>/dev/null | grep 'Current cluster layout version:' | awk '{print $NF}')
    NEXT_VERSION=$((VERSION + 1))
    $GARAGE layout apply --version "$NEXT_VERSION"
    echo "    Layout applied (version $NEXT_VERSION)."
else
    echo "    Node already has a role assigned, skipping layout."
fi

echo "==> Creating bucket '$BUCKET_NAME'..."
if $GARAGE bucket info "$BUCKET_NAME" >/dev/null 2>&1; then
    echo "    Bucket already exists, skipping."
else
    $GARAGE bucket create "$BUCKET_NAME"
    echo "    Bucket created."
fi

echo "==> Creating API key '$KEY_NAME'..."
KEY_OUTPUT=$($GARAGE key info "$KEY_NAME" 2>/dev/null || true)
if echo "$KEY_OUTPUT" | grep -q 'Key ID:'; then
    echo "    Key already exists, skipping creation."
else
    KEY_OUTPUT=$($GARAGE key create "$KEY_NAME")
    echo "    Key created."
fi

KEY_ID=$(echo "$KEY_OUTPUT" | grep 'Key ID:' | awk '{print $NF}')
SECRET_KEY=$(echo "$KEY_OUTPUT" | grep 'Secret key:' | awk '{print $NF}')

echo "==> Granting key '$KEY_NAME' access to bucket '$BUCKET_NAME'..."
$GARAGE bucket allow --read --write --owner "$BUCKET_NAME" --key "$KEY_NAME"
echo "    Permissions granted."

echo ""
echo "============================================"
echo "  Garage is ready!"
echo "============================================"
echo ""
echo "Add these to your .env file:"
echo ""
echo "  AWS_ACCESS_KEY_ID=$KEY_ID"
echo "  AWS_SECRET_ACCESS_KEY=$SECRET_KEY"
echo ""
echo "Garage WebUI: http://localhost:3909"
echo "S3 Endpoint:  http://localhost:3900 (host) / http://garage:3900 (docker)"
echo ""
