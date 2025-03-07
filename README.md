# Laravel Pre-Interview Test

## Project: Asynchronous Bookmark Metadata Fetcher

**Goal:** Build a minimal API that allows users to submit web links (bookmarks) and retrieve their metadata **asynchronously** using a **message broker** or task queue.

## Core Requirements

### 1. Authentication
* To keep things simple, use a hardcoded API token for authentication.
* If you prefer, you can implement a basic auth system, but this is **not required**, as the main focus is on the message broker/task queue.

### 2. Bookmark Submission
* A bookmarks table with:
    * id (UUID)
    * url (string)
    * title (nullable string)
    * description (nullable string)
    * created_at
* Only the url is stored initially; **title and description should be filled asynchronously**.

### 3. Asynchronous Processing (Main Focus)
* When a bookmark is submitted, it should trigger an **asynchronous task** to fetch the webpage metadata (title & description) and update the database.
* You can use **one of the following options** for handling the task queue:
    * **Redis Pub/Sub**
    * **RabbitMQ**
    * **Celery (with Redis or RabbitMQ as the broker)**
* Handle potential failures (e.g., invalid URLs, unreachable pages).

### 4. API Endpoints
* POST /bookmarks → Accepts a URL and stores it (publishing a task/message).
* GET /bookmarks → Lists all bookmarks (including fetched metadata).

## Optional Enhancements
* **Soft Deletes**: Allow users to mark bookmarks as deleted instead of removing them.
* **Docker Setup**: Provide a docker-compose.yml to run the app with Redis/RabbitMQ/Celery easily.

## Deliverables
* A **Git repository** with the project.
* A **README** explaining setup and usage.
* A **Postman collection or sample API requests**.

## Evaluation Criteria
* **Message Broker or Task Queue Integration** (Priority)
* **Code Quality & API Design**
* **Asynchronous Processing Implementation**
* **Error Handling & Performance Considerations**
* **Documentation & Ease of Setup**
