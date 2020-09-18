#!/bin/bash

NOTE=$(git log -1 --pretty=%B)

# Just print the name of the input
echo "Name: ${1}, Note: ${NOTE}"