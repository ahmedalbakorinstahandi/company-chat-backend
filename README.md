# Company Chat Backend

A Laravel-based backend for a company chat application with real-time messaging, stories, and notifications.

## Features

- User Authentication (Register, Login, OTP Verification)
- Company Management
- Real-time Messaging
- Stories with Views and Favorites
- Push Notifications
- File Uploads
- Role-based Access Control

## Requirements

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer
- Node.js & NPM
- Firebase Account
- Pusher Account

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/company-chat-backend.git
cd company-chat-backend
```

2. Install PHP dependencies:
```bash
composer install
```

3. Copy the environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your environment variables in `.env`:
```
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=your_cluster

# Firebase
FIREBASE_CREDENTIALS=path/to/firebase-credentials.json
FIREBASE_PROJECT_ID=your_project_id
FIREBASE_DATABASE_URL=your_database_url
FIREBASE_STORAGE_BUCKET=your_storage_bucket
FIREBASE_SERVER_KEY=your_server_key
```

6. Run database migrations:
```bash
php artisan migrate
```

7. Start the development server:
```bash
php artisan serve
```

## API Documentation

### Authentication

#### Register
- **POST** `/api/register`
- Body:
  ```json
  {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "password": "password123",
    "phone_number": "+1234567890",
    "role": "employee"
  }
  ```

#### Login
- **POST** `/api/login`
- Body:
  ```json
  {
    "email": "john@example.com",
    "password": "password123",
    "device_token": "firebase_device_token"
  }
  ```

#### Send OTP
- **POST** `/api/send-otp`
- Body:
  ```json
  {
    "email": "john@example.com"
  }
  ```

#### Verify OTP
- **POST** `/api/verify-otp`
- Body:
  ```json
  {
    "email": "john@example.com",
    "otp": "123456"
  }
  ```

#### Reset Password
- **POST** `/api/reset-password`
- Body:
  ```json
  {
    "email": "john@example.com",
    "otp": "123456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
  }
  ```

### Companies

#### List Companies
- **GET** `/api/companies`
- Headers: `Authorization: Bearer {token}`

#### Create Company
- **POST** `/api/companies`
- Headers: `Authorization: Bearer {token}`
- Body:
  ```json
  {
    "name": "Company Name",
    "email": "company@example.com",
    "description": "Company description",
    "logo": "file"
  }
  ```

#### Add Employee
- **POST** `/api/companies/{company}/employees`
- Headers: `Authorization: Bearer {token}`
- Body:
  ```json
  {
    "user_id": 1
  }
  ```

### Messages

#### List Messages
- **GET** `/api/messages?receiver_id=1`
- Headers: `Authorization: Bearer {token}`

#### Send Message
- **POST** `/api/messages`
- Headers: `Authorization: Bearer {token}`
- Body:
  ```json
  {
    "receiver_id": 1,
    "content": "Hello!",
    "images": ["file1", "file2"]
  }
  ```

#### Mark as Read
- **POST** `/api/messages/{message}/read`
- Headers: `Authorization: Bearer {token}`

### Stories

#### List Stories
- **GET** `/api/stories`
- Headers: `Authorization: Bearer {token}`

#### Create Story
- **POST** `/api/stories`
- Headers: `Authorization: Bearer {token}`
- Body:
  ```json
  {
    "content": "Story content",
    "image": "file"
  }
  ```

#### View Story
- **POST** `/api/stories/{story}/view`
- Headers: `Authorization: Bearer {token}`
- Body:
  ```json
  {
    "is_favorite": true
  }
  ```

## Real-time Events

The application uses Pusher for real-time communication. The following events are broadcasted:

### Messages
- `message.new`: When a new message is sent
- `message.read`: When a message is marked as read
- `message.typing`: When a user is typing

### Stories
- `story.new`: When a new story is created
- `story.view`: When a story is viewed

### Companies
- `company.employee.added`: When an employee is added to a company
- `company.employee.removed`: When an employee is removed from a company

## Push Notifications

The application uses Firebase Cloud Messaging for push notifications. Notifications are sent for:

- New messages
- Story views
- Company updates

## Security

- All API endpoints (except authentication) require a valid Sanctum token
- File uploads are validated and stored securely
- Passwords are hashed using bcrypt
- OTP verification for sensitive operations
- Role-based access control

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License.
