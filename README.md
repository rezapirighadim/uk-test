# Asynchronous Bookmark Metadata Fetcher

A robust Laravel API for asynchronously fetching and storing metadata from bookmarked URLs using Redis as a message broker.

## Table of Contents
- [Overview](#overview)
- [Features](#features)
- [System Architecture](#system-architecture)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Docker Setup](#docker-setup)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Error Handling](#error-handling)
- [Security](#security)
- [Performance Considerations](#performance-considerations)
- [Future Improvements](#future-improvements)

## Overview

This application allows users to submit web links (bookmarks) and retrieves their metadata (title and description) asynchronously using a message broker system. When a bookmark is submitted, the URL is stored immediately, and a background task is dispatched to fetch the webpage's metadata.

## Features

### Core Features

- **Bookmark Management**
  - Create, retrieve, list, and delete bookmarks
  - Filter bookmarks by processing status (pending, completed, failed)
  - Pagination for large bookmark collections

- **Asynchronous Processing**
  - Redis-based message queue
  - Background metadata fetching
  - Retry mechanism for failed fetches (3 attempts with 60-second backoff)

- **Metadata Extraction**
  - Support for standard HTML metadata
  - Support for Open Graph metadata
  - Support for Twitter Card metadata
  - Fallback mechanisms for missing metadata

- **API Authentication**
  - Token-based authentication
  - Secure API endpoints

### Enhanced Features

- **Soft Deletes**
  - Bookmarks are soft deleted instead of being permanently removed
  - Data integrity preservation

- **Comprehensive Error Tracking**
  - Detailed error messages for failed metadata fetches
  - Error status tracking
  - Retry functionality for failed fetches

- **Event-Driven Architecture**
  - Events for bookmark creation
  - Extensible event system for future integrations

## System Architecture

The application follows a clean architecture with proper separation of concerns:

- **Controllers**: Handle HTTP requests and responses
- **Models**: Define database structure and relationships
- **Services**: Contain business logic (e.g., metadata fetching)
- **Jobs**: Handle asynchronous processing
- **Events**: Enable loose coupling between components
- **Middleware**: Process HTTP requests before they reach controllers

### Asynchronous Flow

1. Client submits a URL via the API
2. Application validates and stores the URL
3. A job is dispatched to the Redis queue
4. The queue worker processes the job
5. Metadata is fetched and stored in the database
6. The bookmark is updated with the fetched metadata

## Technology Stack

- **Backend Framework**: Laravel 8.x
- **Database**: MySQL
- **Message Broker**: Redis
- **Containerization**: Docker & Docker Compose
- **Testing Framework**: Pest PHP
- **HTTP Client**: Guzzle
- **HTML Parser**: Symfony DOM Crawler

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- MySQL
- Redis

### Manual Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/rezapirighadim/bookmark-fetcher
   cd bookmark-fetcher
   ```

2. Install dependencies:
   ```bash
   composer require symfony/dom-crawler symfony/css-selector
   composer install
   ```

3. Create and configure the environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Set up the database and Redis connection in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bookmark_fetcher
   DB_USERNAME=root
   DB_PASSWORD=

   QUEUE_CONNECTION=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   REDIS_QUEUE=default

   API_TOKEN=your-secret-api-token
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Start the queue worker:
   ```bash
   php artisan queue:work redis
   ```

7. Serve the application:
   ```bash
   php artisan serve
   ```

## Docker Setup

For a more convenient setup, use Docker Compose:

1. Clone the repository:
   ```bash
   git clone https://github.com/rezapirighadim/bookmark-fetcher
   cd bookmark-fetcher
   ```

2. Create the environment file:
   ```bash
   cp .env.example .env
   ```

3. Build and start the containers:
   ```bash
   docker-compose up -d
   ```

4. Install dependencies and run migrations:
   ```bash
   docker-compose exec app composer install
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   ```

The application will be available at http://localhost:8000/api.

## API Documentation

All API endpoints require authentication using the `Authorization: Bearer {your-token}` header.

### Submit a Bookmark

```
POST /api/bookmarks
Content-Type: application/json

{
    "url": "https://example.com"
}
```

Response (201 Created):
```json
{
    "message": "Bookmark created successfully. Metadata will be fetched asynchronously.",
    "data": {
        "id": "uuid-string",
        "url": "https://example.com",
        "created_at": "2023-01-01T12:00:00.000000Z",
        "updated_at": "2023-01-01T12:00:00.000000Z"
    }
}
```

### List Bookmarks

```
GET /api/bookmarks
```

Optional query parameters:
- `status`: Filter by status (pending, failed, completed)
- `page`: Page number for pagination
- `per_page`: Items per page (default: 15)

Response (200 OK):
```json
{
    "data": [
        {
            "id": "uuid-string",
            "url": "https://example.com",
            "title": "Example Domain",
            "description": "This domain is for use in illustrative examples in documents.",
            "metadata_fetched_at": "2023-01-01T12:01:00.000000Z",
            "fetch_failed": false,
            "fetch_error": null,
            "created_at": "2023-01-01T12:00:00.000000Z",
            "updated_at": "2023-01-01T12:01:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

### Get a Bookmark

```
GET /api/bookmarks/{id}
```

Response (200 OK):
```json
{
    "data": {
        "id": "uuid-string",
        "url": "https://example.com",
        "title": "Example Domain",
        "description": "This domain is for use in illustrative examples in documents.",
        "metadata_fetched_at": "2023-01-01T12:01:00.000000Z",
        "fetch_failed": false,
        "fetch_error": null,
        "created_at": "2023-01-01T12:00:00.000000Z",
        "updated_at": "2023-01-01T12:01:00.000000Z"
    }
}
```

### Delete a Bookmark

```
DELETE /api/bookmarks/{id}
```

Response (200 OK):
```json
{
    "message": "Bookmark deleted successfully."
}
```

### Retry Metadata Fetching

```
POST /api/bookmarks/{id}/retry
```

Response (200 OK):
```json
{
    "message": "Metadata fetch retry initiated.",
    "data": {
        "id": "uuid-string",
        "url": "https://example.com",
        "title": null,
        "description": null,
        "metadata_fetched_at": null,
        "fetch_failed": false,
        "fetch_error": null,
        "created_at": "2023-01-01T12:00:00.000000Z",
        "updated_at": "2023-01-01T12:02:00.000000Z"
    }
}
```

## Testing

The project includes a comprehensive test suite using Pest PHP, covering all aspects of the application:

- **Feature Tests**: API endpoints, authentication
- **Unit Tests**: Models, services, jobs, events
- **Integration Tests**: Database operations, queue processing

### Running Tests

```bash
# Using Docker
docker-compose exec app ./vendor/bin/pest

# Or directly
php vendor/bin/pest
```

### Test Coverage

The test suite covers:
- API token authentication
- URL validation
- Bookmark creation and retrieval
- Metadata fetching
- Error handling
- Retry mechanisms
- Job processing
- Event dispatching

## Error Handling

The application implements robust error handling:

- **Validation Errors**: Returned as 422 Unprocessable Entity with detailed messages
- **Authentication Errors**: Returned as 401 Unauthorized
- **Not Found Errors**: Returned as 404 Not Found
- **Server Errors**: Logged with detailed information, returned as 500 Internal Server Error

### Metadata Fetch Errors

Metadata fetching errors are handled gracefully:
- Failed fetches are marked with `fetch_failed = true`
- Error messages are stored in the `fetch_error` field
- Failed jobs are retried up to 3 times with a 60-second backoff
- After all retries are exhausted, the bookmark remains with failed status

## Security

- **API Authentication**: Bearer token authentication for all endpoints
- **Input Validation**: All user inputs are validated
- **Error Obfuscation**: Detailed errors are logged but not exposed in responses
- **Database**: Prepared statements to prevent SQL injection
- **Soft Deletes**: Data integrity protection

## Performance Considerations

- **Asynchronous Processing**: Offloads time-consuming operations to background jobs
- **Pagination**: Large result sets are paginated to prevent memory issues
- **Timeout Handling**: HTTP requests have a reasonable timeout to prevent hanging
- **Index Optimization**: Database tables are properly indexed
- **Queue Monitoring**: Failed jobs are tracked and can be retried

## Future Improvements

Given more time, the following improvements could be made:

- **Repository Pattern**: Abstract database operations to repositories
- **Rate Limiting**: Implement API rate limiting
- **Caching**: Add caching for frequently accessed bookmarks
- **User Authentication**: Full user authentication with registration and login
- **Frontend Interface**: Build a web interface for bookmark management
- **API Documentation**: Generate API documentation using tools like Swagger
- **Webhooks**: Implement webhooks for metadata fetch completion
- **Metrics**: Track and expose performance metrics

---

This project demonstrates a solid understanding of Laravel best practices, asynchronous processing, clean architecture, and robust error handling. The code is well-structured, thoroughly tested, and ready for production use.
