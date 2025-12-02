# TouristAndTravellism

A Travel AI Planning Backend API built with PHP, MongoDB, Cloudinary, and Google Gemini.

## Overview

This project is the second version of the TouristAndTravellism API. In the previous version, both PHP and HTML/CSS/JS were combined, which didn't work properly and was messy. In this second version, we split the backend as a dedicated FastAPI (though the code shows PHP implementation - likely a transition note).

The API provides comprehensive travel planning functionality with AI-powered trip suggestions, user authentication, photo management, and trip tracking.

## Features

- **User Authentication**: JWT-based authentication with registration, login, profile management, and token refresh
- **Trip Planning**: AI-powered trip planning using Google Gemini
- **Trip Management**: Create, read, update, and delete trips
- **Photo Management**: Upload and manage trip photos using Cloudinary
- **Health Monitoring**: API health check endpoint
- **CORS Support**: Cross-origin resource sharing enabled
- **Error Handling**: Comprehensive error logging and response handling

## Tech Stack

- **Backend**: PHP 8.0+
- **Database**: MongoDB
- **Authentication**: Firebase JWT
- **File Storage**: Cloudinary
- **AI Service**: Google Gemini
- **HTTP Client**: Guzzle HTTP
- **Configuration**: PHP dotenv
- **Autoloading**: Composer PSR-4

## Requirements

- PHP >= 8.0
- Composer
- MongoDB
- Cloudinary account
- Google Gemini API key

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd TouristAndTravellism
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment Setup**
   - Copy `.env.example` to `.env`
   - Configure the following environment variables:
     ```env
     # Database
     MONGODB_URI=mongodb://localhost:27017
     DATABASE_NAME=tourist_travellism

     # JWT
     JWT_SECRET=your-jwt-secret-key
     JWT_EXPIRY=3600

     # Cloudinary
     CLOUDINARY_CLOUD_NAME=your-cloud-name
     CLOUDINARY_API_KEY=your-api-key
     CLOUDINARY_API_SECRET=your-api-secret

     # Google Gemini
     GEMINI_API_KEY=your-gemini-api-key

     # CORS
     CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8080

     # Debug
     DEBUG=true
     ```

4. **Start your web server**
   - Ensure your web server (Apache/Nginx) points to the `public/` directory
   - Or use PHP's built-in server for development:
     ```bash
     cd public
     php -S localhost:8000
     ```

## Project Structure

```
TouristAndTravellism/
├── public/
│   └── index.php              # Entry point
├── src/
│   ├── Config/
│   │   ├── DatabaseConfig.php # Database configuration
│   │   └── EnvConfig.php      # Environment configuration
│   ├── Controllers/
│   │   ├── AuthController.php # Authentication endpoints
│   │   ├── TripController.php # Trip management
│   │   └── PhotoController.php# Photo management
│   ├── Middleware/
│   │   └── JwtMiddleware.php  # JWT authentication middleware
│   ├── Models/
│   │   ├── UserModel.php      # User data model
│   │   ├── TripModel.php      # Trip data model
│   │   └── UserPhotoModel.php # Photo data model
│   ├── Routes/
│   │   └── RouteConfig.php    # Route definitions
│   ├── Services/
│   │   ├── AuthService.php    # Authentication logic
│   │   ├── TripService.php    # Trip planning logic
│   │   ├── PhotoService.php   # Photo handling
│   │   ├── CloudinaryService.php # Cloudinary integration
│   │   ├── GeminiService.php  # AI service integration
│   │   └── JwtService.php     # JWT token handling
│   └── Utils/
│       └── ResponseHelper.php # Response utilities
├── storage/
│   └── logs/                  # Application logs
├── vendor/                    # Composer dependencies
├── .gitignore                 # Git ignore rules
├── composer.json              # Composer configuration
└── README.md                  # This file
```

## API Routes

### Authentication Routes

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/auth/register` | Register a new user | No |
| POST | `/api/auth/login` | User login | No |
| GET | `/api/auth/profile` | Get user profile | Yes |
| POST | `/api/auth/refresh` | Refresh JWT token | Yes |

### Trip Routes

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/trips/plan` | Plan a trip using AI | Yes |
| POST | `/api/trips` | Create a new trip | Yes |
| GET | `/api/trips` | Get all user trips | Yes |
| GET | `/api/trips/{id}` | Get specific trip by ID | Yes |
| PATCH | `/api/trips/{id}/status` | Update trip status | Yes |
| DELETE | `/api/trips/{id}` | Delete a trip | Yes |

### Photo Routes

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/trips/{tripId}/photos` | Upload photo to trip | Yes |
| GET | `/api/trips/{tripId}/photos` | Get all photos for a trip | Yes |
| GET | `/api/photos` | Get all user photos | Yes |
| DELETE | `/api/photos/{id}` | Delete a photo | Yes |

### Health Check

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/health` | API health check | No |

## API Usage Examples

### Authentication

**Register User:**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "name": "John Doe"
  }'
```

**Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### Trip Management

**Create Trip:**
```bash
curl -X POST http://localhost:8000/api/trips \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "title": "Trip to Paris",
    "description": "A wonderful trip to Paris",
    "destination": "Paris, France",
    "startDate": "2024-06-01",
    "endDate": "2024-06-07"
  }'
```

**Plan Trip with AI:**
```bash
curl -X POST http://localhost:8000/api/trips/plan \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "destination": "Tokyo, Japan",
    "duration": 7,
    "budget": 2000,
    "interests": ["culture", "food", "technology"]
  }'
```

### Photo Management

**Upload Photo:**
```bash
curl -X POST http://localhost:8000/api/trips/TRIP_ID/photos \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "photo=@/path/to/photo.jpg" \
  -F "caption=Beautiful sunset"
```

## Response Format

All API responses follow a consistent JSON format:

**Success Response:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Error message",
  "code": 400
}
```

## Error Handling

The API includes comprehensive error handling:
- 400 Bad Request: Invalid input data
- 401 Unauthorized: Missing or invalid JWT token
- 403 Forbidden: Insufficient permissions
- 404 Not Found: Resource not found
- 500 Internal Server Error: Server-side errors

Errors are logged to `storage/logs/error.log` for debugging.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

**PAMARTHILVSIVANAGESWARARAO**
- Email: 24a85a0506@sves.org.in


## Run Command 
**$env:PATH = "C:\xampp\php;" + $env:PATH**
**php -S localhost:8000**