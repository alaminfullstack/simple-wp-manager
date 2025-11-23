#!/bin/bash

# Docker Container Monitor Script
# Monitors WordPress Docker containers and reports status to Laravel app

# Configuration
API_URL="YOUR_LARAVEL_APP_URL/api/sites/update-status"
API_TOKEN="YOUR_API_TOKEN"
LOG_FILE="/var/log/docker-monitor.log"
WP_CONTAINER_PREFIX="wp_"

# Function to log with timestamp
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Function to get container status
get_container_status() {
    local container_name=$1
    local status=$(docker inspect -f '{{.State.Status}}' "$container_name" 2>/dev/null)
    
    if [ $? -eq 0 ]; then
        case "$status" in
            "running")
                echo "running"
                ;;
            "exited"|"dead")
                echo "stopped"
                ;;
            "paused")
                echo "paused"
                ;;
            *)
                echo "unknown"
                ;;
        esac
    else
        echo "not_found"
    fi
}

# Function to send status update to Laravel API
send_status_update() {
    local container_name=$1
    local status=$2
    
    response=$(curl -s -X POST "$API_URL" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $API_TOKEN" \
        -d "{\"container_name\": \"$container_name\", \"status\": \"$status\"}" \
        2>&1)
    
    if [ $? -eq 0 ]; then
        log_message "Status updated for $container_name: $status"
        return 0
    else
        log_message "Failed to update status for $container_name: $response"
        return 1
    fi
}

# Main monitoring logic
main() {
    log_message "Starting Docker container monitoring..."
    
    # Get all WordPress containers
    containers=$(docker ps -a --filter "name=${WP_CONTAINER_PREFIX}" --format "{{.Names}}")
    
    if [ -z "$containers" ]; then
        log_message "No WordPress containers found"
        return 0
    fi
    
    # Count containers
    container_count=$(echo "$containers" | wc -l)
    log_message "Found $container_count WordPress container(s)"
    
    # Check each container
    while IFS= read -r container_name; do
        # Skip database containers
        if [[ "$container_name" == *"_db"* ]]; then
            continue
        fi
        
        status=$(get_container_status "$container_name")
        log_message "Container: $container_name, Status: $status"
        
        # Send status update to API
        send_status_update "$container_name" "$status"
        
    done <<< "$containers"
    
    log_message "Monitoring cycle completed"
}

# Create log file if it doesn't exist
if [ ! -f "$LOG_FILE" ]; then
    touch "$LOG_FILE"
    chmod 664 "$LOG_FILE"
fi

# Run main function
main

# Exit
exit 0