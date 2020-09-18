#!/bin/bash

# Exit on error
set -e

echo -e "Starting ${1}";
terminus site:upstream:clear-cache $1
terminus upstream:update:status "${1}.dev"
terminus upstream:updates:apply "${1}.dev"
terminus drush "${1}.dev" -- updb -y
terminus env:clear-cache "${1}.dev"
terminus env:deploy ${1}.test --cc --updatedb --note ''