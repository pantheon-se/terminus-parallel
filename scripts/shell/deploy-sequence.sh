#!/bin/bash

# Usage
# ./deploy-sequence.sh <site-name or uuid>

# Exit on error
set -e

echo -e "Starting ${1}";

# Check site upstream for updates, apply
terminus site:upstream:clear-cache $1
terminus upstream:update:status "${1}.dev"
terminus upstream:updates:apply "${1}.dev"

# Run drush updates on dev, clear cache
terminus drush "${1}.dev" -- updb -y
terminus env:clear-cache "${1}.dev"

# Deploy code to test
terminus env:deploy ${1}.test --cc --updatedb --note ''