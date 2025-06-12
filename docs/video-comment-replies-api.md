# Video Comment Replies API

This document describes the API endpoints for video comment reply functionality.

## Endpoints

### 1. Get Video Comments (with replies)
**GET** `/api/tutorials/{video}/comments`

Returns paginated list of parent comments with their replies loaded.

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "video_id": 1,
      "parent_id": null,
      "user": {
        "id": 1,
        "name": "John Doe",
        "code": "hm-12345",
        "image": "https://example.com/image.jpg"
      },
      "content": "This is a great video!",
      "likes": 5,
      "dislikes": 0,
      "replies_count": 2,
      "is_reply": false,
      "replies": [
        {
          "id": 2,
          "video_id": 1,
          "parent_id": 1,
          "user": {
            "id": 2,
            "name": "Jane Smith",
            "code": "hm-67890",
            "image": "https://example.com/image2.jpg"
          },
          "content": "I agree!",
          "likes": 1,
          "dislikes": 0,
          "replies_count": 0,
          "is_reply": true,
          "replies": [],
          "created_at": "2024/01/01"
        }
      ],
      "created_at": "2024/01/01"
    }
  ]
}
```

### 2. Create Reply to Comment
**POST** `/api/tutorials/{video}/comments/{comment}/reply`

Creates a reply to a specific comment. Requires authentication.

**Request Body:**
```json
{
  "content": "This is my reply to the comment"
}
```

**Response:** (201 Created)
```json
{
  "data": {
    "id": 3,
    "video_id": 1,
    "parent_id": 1,
    "user": {
      "id": 3,
      "name": "Bob Johnson",
      "code": "hm-11111",
      "image": "https://example.com/image3.jpg"
    },
    "content": "This is my reply to the comment",
    "likes": 0,
    "dislikes": 0,
    "replies_count": 0,
    "is_reply": true,
    "replies": [],
    "created_at": "2024/01/01"
  }
}
```

### 3. Get Replies for a Comment
**GET** `/api/tutorials/{video}/comments/{comment}/replies`

Returns paginated list of replies for a specific comment.

**Response:**
```json
{
  "data": [
    {
      "id": 2,
      "video_id": 1,
      "parent_id": 1,
      "user": {
        "id": 2,
        "name": "Jane Smith",
        "code": "hm-67890",
        "image": "https://example.com/image2.jpg"
      },
      "content": "I agree!",
      "likes": 1,
      "dislikes": 0,
      "replies_count": 0,
      "is_reply": true,
      "replies": [],
      "created_at": "2024/01/01"
    }
  ]
}
```

### 4. Like a Reply
**POST** `/api/tutorials/{video}/comments/{comment}/replies/{reply}/like`

Likes a specific reply. Requires authentication.

**Response:** (200 OK)
```json
[]
```

### 5. Dislike a Reply
**POST** `/api/tutorials/{video}/comments/{comment}/replies/{reply}/dislike`

Dislikes a specific reply. Requires authentication.

**Response:** (200 OK)
```json
[]
```

## Business Rules

1. **Reply Permissions**: Users cannot reply to their own comments
2. **Nested Replies**: Replies to replies are automatically converted to replies to the parent comment (no deep nesting)
3. **Like/Dislike**: Users cannot like/dislike their own comments or replies
4. **Authentication**: Creating replies and liking/disliking requires authentication
5. **Validation**: Reply content is required and limited to 2000 characters

## Database Schema

The `comments` table includes a `parent_id` field:
- `parent_id` is nullable and references `comments.id`
- When `parent_id` is null, it's a parent comment
- When `parent_id` has a value, it's a reply to that comment

## Error Responses

- **401 Unauthorized**: When authentication is required but not provided
- **403 Forbidden**: When user tries to reply to their own comment or like/dislike their own content
- **404 Not Found**: When video or comment doesn't exist
- **422 Validation Error**: When request data is invalid 
