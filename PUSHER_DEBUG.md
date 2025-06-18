# Pusher Debugging Guide

## Common Issues and Solutions

### 1. Environment Variables
Make sure your `.env` file has the correct Pusher credentials:

```env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=us3
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
BROADCAST_DRIVER=pusher
```

**Important**: Leave `PUSHER_HOST` empty to use the default Pusher host (`api-us3.pusherapp.com`).

### 2. Host Configuration Issue
If you see the error "Unable to parse URI: https://:443", it means the host is empty. The fix is:

1. **Leave PUSHER_HOST empty** in your `.env` file
2. **Clear config cache**: `php artisan config:cache`
3. **Test again**: Use the test route `/api/test-pusher`

### 3. Test Pusher Connection
Use the test route to verify Pusher is working:

```bash
curl -X POST http://your-domain/api/test-pusher
```

The test route will show:
- Configuration status
- Missing environment variables
- Detailed error messages

### 4. Check Logs
Monitor Laravel logs for Pusher errors:

```bash
tail -f storage/logs/laravel.log
```

### 5. Channel Naming
- **Public channels**: `channel-name`
- **Private channels**: `private-channel-name`
- **Presence channels**: `presence-channel-name`

### 6. Client-Side Setup
For mobile apps, use these Pusher credentials:

```javascript
// Flutter/Dart
PusherClient pusher = PusherClient(
  'your_app_key', // PUSHER_APP_KEY
  PusherOptions(
    cluster: 'us3', // PUSHER_APP_CLUSTER
    encrypted: true,
  ),
);

// Subscribe to channels
Channel channel = pusher.subscribe('private-user.123');
channel.bind('message.new', (event) {
  print('New message: ${event.data}');
});
```

### 7. Authentication for Private Channels
For private channels, you need to implement authentication:

```php
// In routes/channels.php
Broadcast::channel('private-user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

### 8. Debugging Steps

1. **Check credentials**: Verify Pusher credentials in your dashboard
2. **Test connection**: Use the test route `/api/test-pusher`
3. **Check logs**: Monitor Laravel logs for errors
4. **Verify channels**: Ensure channel names match between server and client
5. **Check permissions**: For private channels, verify authentication
6. **Network issues**: Check if your server can reach Pusher servers

### 9. Common Error Messages

- **"Invalid key"**: Check PUSHER_APP_KEY
- **"Invalid signature"**: Check PUSHER_APP_SECRET
- **"Channel not found"**: Verify channel name and permissions
- **"Connection timeout"**: Check network connectivity
- **"Unable to parse URI: https://:443"**: Leave PUSHER_HOST empty

### 10. Mobile App Integration

For mobile apps, you need to:

1. Install Pusher client library
2. Initialize with correct credentials
3. Subscribe to appropriate channels
4. Handle authentication for private channels
5. Listen for events

### 11. Testing Checklist

- [ ] Environment variables set correctly
- [ ] PUSHER_HOST is empty (or correct)
- [ ] Test route returns success
- [ ] No errors in Laravel logs
- [ ] Client can connect to Pusher
- [ ] Client can subscribe to channels
- [ ] Events are received on client
- [ ] Private channel authentication works

## Quick Fix Commands

```bash
# Clear config cache
php artisan config:cache

# Clear route cache
php artisan route:cache

# Check environment variables
php artisan tinker
echo config('services.pusher.key');

# Test Pusher connection
curl -X POST http://your-domain/api/test-pusher

# Run environment test script
php test-env.php
```

## Troubleshooting Specific Issues

### Host Configuration Error
**Error**: "Unable to parse URI: https://:443"

**Solution**:
1. Set `PUSHER_HOST=` (empty) in `.env`
2. Run `php artisan config:cache`
3. Test again

### Missing Environment Variables
**Error**: "Missing Pusher configuration"

**Solution**:
1. Check all required variables in `.env`
2. Ensure no typos in variable names
3. Clear config cache 