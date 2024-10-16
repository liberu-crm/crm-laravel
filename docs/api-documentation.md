# CRM API Documentation

## Authentication

This API uses Laravel Sanctum for authentication. To authenticate, you need to include a bearer token in the Authorization header of your requests.

To obtain a token, make a POST request to `/api/login` with your email and password. The response will include an access token.

Example:
```
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

Response:
```json
{
    "access_token": "1|abcdefghijklmnopqrstuvwxyz123456"
}
```

Include this token in subsequent requests:

```
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456
```

## Endpoints

All endpoints are prefixed with `/api/v1`.

### Contacts

#### List all contacts
GET /contacts

#### Create a new contact
POST /contacts
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890"
}
```

#### Get a specific contact
GET /contacts/{id}

#### Update a contact
PUT /contacts/{id}
```json
{
    "name": "John Doe Updated",
    "email": "john_updated@example.com",
    "phone": "0987654321"
}
```

#### Delete a contact
DELETE /contacts/{id}

### Deals

#### List all deals
GET /deals

#### Create a new deal
POST /deals
```json
{
    "title": "New Deal",
    "value": 1000,
    "status": "open"
}
```

#### Get a specific deal
GET /deals/{id}

#### Update a deal
PUT /deals/{id}
```json
{
    "title": "Updated Deal",
    "value": 2000,
    "status": "won"
}
```

#### Delete a deal
DELETE /deals/{id}

### Tasks

#### List all tasks
GET /tasks

#### Create a new task
POST /tasks
```json
{
    "title": "New Task",
    "description": "Task description",
    "due_date": "2023-06-30",
    "status": "pending"
}
```

#### Get a specific task
GET /tasks/{id}

#### Update a task
PUT /tasks/{id}
```json
{
    "title": "Updated Task",
    "description": "Updated description",
    "due_date": "2023-07-15",
    "status": "in_progress"
}
```

#### Delete a task
DELETE /tasks/{id}

## Error Handling

The API uses standard HTTP status codes to indicate the success or failure of requests. In case of an error, the response will include a JSON object with an `error` key containing a description of the error.

Example error response:
```json
{
    "error": "Unauthenticated."
}
```

Common status codes:
- 200: Success
- 201: Created
- 204: No Content (successful deletion)
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Unprocessable Entity (validation errors)
- 500: Internal Server Error