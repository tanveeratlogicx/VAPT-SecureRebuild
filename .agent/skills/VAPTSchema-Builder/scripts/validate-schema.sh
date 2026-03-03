#!/bin/bash
# Description: Helper script to validate generated JSON schemas syntax.

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <schema_file.json>"
    exit 1
fi

FILE=$1

if [ ! -f "$FILE" ]; then
    echo "Error: File $FILE not found!"
    exit 1
fi

echo "Validating JSON syntax for $FILE..."

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "Warning: 'jq' is not installed. Falling back to python json.tool"
    python3 -m json.tool "$FILE" > /dev/null
    if [ $? -eq 0 ];  then
        echo "✅ Valid JSON syntax."
    else
        echo "❌ Invalid JSON syntax."
        exit 1
    fi
else
    # Use JQ to validate and check required keys
    jq empty "$FILE" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
         echo "❌ Invalid JSON syntax."
         exit 1
    fi
     echo "✅ Valid JSON syntax."
     
     # Check basic structure
     HAS_CONTROLS=$(jq 'has("controls")' "$FILE")
     HAS_ENFORCEMENT=$(jq 'has("enforcement")' "$FILE")
     
     if [ "$HAS_CONTROLS" == "true" ] && [ "$HAS_ENFORCEMENT" == "true" ]; then
         echo "✅ Valid Top-Level Structure."
     else
         echo "❌ Missing 'controls' or 'enforcement' top-level keys."
         exit 1
     fi
fi
