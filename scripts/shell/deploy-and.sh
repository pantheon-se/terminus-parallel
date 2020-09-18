#!/bin/bash

# Usage
# ./deploy-sequence.sh <site-name or uuid>

# Exit on error
set -e

SITES=$(terminus org:site:list ${ORG_ID} --format list --upstream ${UPS_ID} --field name | sort -V)
NOTE=$(git log -1 --pretty=tformat:"%s")
echo -e "Note: ${NOTE}"

function sequence() {
  local SITE=$1
  local DEV=$(echo "${SITE}.dev")
  local TEST=$(echo "${SITE}.test")
  local LIVE=$(echo "${SITE}.live")

  echo -e "Starting ${SITE}";

  # Check site upstream for updates, apply
  terminus site:upstream:clear-cache $1
  
  # terminus connection:set "${1}.dev" git
  # STATUS=$(terminus upstream:update:status "${1}.dev")
  terminus upstream:updates:apply $DEV

  # Run drush updates on dev, clear cache
  # terminus drush "${1}.dev" -- updb -y
  # terminus env:clear-cache "${1}.dev"

  # Deploy code to test and live
  echo -e "${SITE}: Starting test deploy..."
  terminus env:deploy $TEST --cc --updatedb -n
  echo -e "${SITE}: Starting live deploy..."
  terminus env:deploy $LIVE --cc --updatedb -n
  echo -e "Finished ${SITE}"
}

# Initiate sequence
for SITE in $SITES; do
  time sequence $SITE &
  sleep 6
done

# Wait for background jobs to finish processing.
wait
