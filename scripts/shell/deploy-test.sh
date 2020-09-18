#!/bin/bash

# Exit on error
set -e

SITES=$(terminus org:site:list "Purina Demo" --format list --field name | sort -V)

# Create a ton of sites.
for SITE in $SITES; do
  echo -e "Deploying to test: $SITE..."
  $(terminus env:deploy "${SITE}.test" --cc --updatedb) &
  sleep 2
done
