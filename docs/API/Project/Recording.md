# Project's Recording API

## GET /api/project/{projectId}/recordings

Returns the list of Recording which belongs to the Project.

### Requirements

This API endpoint accepts only `application/json` Content-Type requests.

#### Request

```
GET /api/project/{projectId}/recordings --header "Content-Type: application/json"
```

#### Response

##### 200 OK

```json
{
  "data": [
    {
      "id": "<integer>",
      "project_id": "<integer>",
      "datasift_recording_id": "<string>",
      "name": "<string>"
    },
    {
      "id": "<integer>",
            "project_id": "<integer>",
            "datasift_recording_id": "<string>",
            "name": "<string>"
    }
  ],
  "meta": {
    "count": "<integer>"
  }
}
```

##### 404 Not Found

Returns when Project could not be found.

##### 400 Bad Request

Returns when Content-Type required for the request was invalid.