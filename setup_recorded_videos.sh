#!/bin/bash

# Create directory for recorded videos
echo "Creating directory for recorded videos..."
mkdir -p public/streams/videos

# Set permissions
chmod -R 755 public/streams

echo "Directory created successfully!"
echo "Location: public/streams/videos"

# Run migration
echo ""
echo "Running migration..."
php artisan migrate

echo ""
echo "Setup complete!"
echo ""
echo "IMPORTANT: Make sure to create the 'streams' and 'streams/videos' directories in your public folder if they don't exist."
