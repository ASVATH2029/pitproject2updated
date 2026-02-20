#!/bin/bash
USERNAME="$1"
if [ -z "$USERNAME" ]; then
    exit 1
fi
# Password is read from stdin
read -r PASSWORD
echo "$PASSWORD" | su - "$USERNAME" -c "echo ok" > /dev/null 2>&1
exit $?
