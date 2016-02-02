# Dimension API

## GET /api/project/:id/worksheet/:id/dimensions

Returns the list of dimensions available for this project and worksheet.

### Requirements

This API endpoint accepts only `application/json` Content-Type requests.

#### Request
```
GET /api/project/:id/worksheet/:id/dimensions --header "Content-Type: application/json"
```

#### Response

##### 200 OK

```json
{
    "data": {
        "groups": [
            {
                "name": "<string>",
                "items": [
                    {
                        "target": "<string>",
                        "label": "<string|optional>",
                        "description": "<string|optional>"
                    },
                    {
                        "target": "<string>",
                        "label": "<string|optional>",
                        "description": "<string|optional>"
                    }
                ]
            },
            {
                "name": "<string>",
                "items": [
                    {
                        "target": "<string>",
                        "label": "<string|optional>",
                        "description": "<string|optional>"
                    },
                    {
                        "target": "<string>",
                        "label": "<string|optional>",
                        "description": "<string|optional>"
                    }
                ]
            }
        ]
    },
    "meta": {
    	"dimensions_count": "<int>",
        "groups_count": "<int>"
    }
}
```

##### 400 Bad Request

Returns when Content-Type required for the request was invalid.
