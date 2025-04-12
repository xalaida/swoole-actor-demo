#!/bin/bash

# Function to send a request
send_request() {
  sleep "$1"
  curl -s http://localhost:1337 &
}

send_request 0
send_request 0.1
send_request 0.2
send_request 0.3
send_request 0.4

# Wait for all background jobs to finish
wait

echo "All requests sent."
