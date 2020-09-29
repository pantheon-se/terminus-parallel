#!/bin/bash

# Add timeout wrapper to deploy process

# Get paths
__dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Timeout after 15 minutes.
timeout 15m ${__dir}/deploy-sequence.sh $1
