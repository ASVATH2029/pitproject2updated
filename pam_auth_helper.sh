#!/bin/bash
# PAM Authentication Helper for PITS
# Validates a Linux user's password by checking against /etc/shadow
# Usage: echo "password" | sudo ./pam_auth_helper.sh username
# Exit 0 = success, Exit 1 = failure

USERNAME="$1"
if [ -z "$USERNAME" ]; then
    exit 1
fi

# Read password from stdin
read -r PASSWORD

# Get the stored password hash from /etc/shadow (requires root)
STORED=$(getent shadow "$USERNAME" 2>/dev/null | cut -d: -f2)

# Fail if user doesn't exist or account is locked/has no password
if [ -z "$STORED" ] || [ "$STORED" = "!" ] || [ "$STORED" = "*" ] || [ "$STORED" = "!!" ]; then
    exit 1
fi

# Verify password using Python3 crypt module
# Variables passed via environment (not visible in ps output)
export SHADOW_HASH="$STORED"
export CHECK_PASS="$PASSWORD"

python3 -c "
import crypt, os, sys
if crypt.crypt(os.environ['CHECK_PASS'], os.environ['SHADOW_HASH']) == os.environ['SHADOW_HASH']:
    sys.exit(0)
sys.exit(1)
" 2>/dev/null

RESULT=$?
unset SHADOW_HASH CHECK_PASS
exit $RESULT
