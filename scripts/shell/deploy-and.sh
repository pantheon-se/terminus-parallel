#!/bin/bash

# Exit on error
set -e

# Primary Terminus deploy event sequence
function sequence() {
  local SITE=$1
  local ID=$2
  local DEV=$(echo "${SITE}.dev")
  local TEST=$(echo "${SITE}.test")
  local LIVE=$(echo "${SITE}.live")
  local START=$SECONDS

  echo -e "Starting ${SITE}";

  # Check site upstream for updates, apply
  terminus site:upstream:clear-cache $1 -q
  
  # terminus connection:set "${1}.dev" git
  # STATUS=$(terminus upstream:update:status "${1}.dev")
  terminus upstream:updates:apply $DEV -q

  # Run drush updates on dev, clear cache
  # terminus drush "${1}.dev" -- updb -y
  # terminus env:clear-cache "${1}.dev"

  # Deploy code to test and live
  echo -e "${SITE}: Starting test deploy..."
  terminus env:deploy $TEST --cc --updatedb -n -q
  echo -e "${SITE}: Starting live deploy..."
  terminus env:deploy $LIVE --cc --updatedb -n -q
  echo -e "Finished ${SITE}"

  # Report time to results.
  local DURATION=$(( SECONDS - START ))
  local TIME_DIFF=$(bc <<< "scale=2; $DURATION / 60")
  local MIN=$(printf "%.2f" $TIME_DIFF)
  echo "${SITE},${ID},${MIN}" >> /tmp/results.txt
}

# MAIN SCRIPT THREAD

# Save sites to temp CSV
terminus org:site:list ${ORG_UUID} --format csv --upstream ${UPSTREAM_UUID} --fields name,id | sort -V > /tmp/sites.csv

# Extract note
# Current unused as Terminus does not accept the notes well. Will require more formatting research.
NOTE=$(git log -1 --pretty=tformat:"%s")
echo -e "Note: ${NOTE}"

# Set time format for time output
TIMEFORMAT=%R

# Initiate sequence
while IFS=, read -r SITE ID
do
  # Yeet the process into the background
  sequence $SITE $ID &
  sleep 6
done < /tmp/sites.csv

# Wait for background jobs to finish processing.
wait
