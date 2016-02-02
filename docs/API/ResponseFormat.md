# API Response Format

API Response format is unified across all API endpoints.

We currently assume that all responses should contain both **data**
(default `[]`) and **meta** (default `{}`) properties.

```json
{
    "data": [],
    "meta": {}
}
```

The **meta** response is used for things like:
- list pagination metadata: `{"count": 20, "limit": 10, "offset": 0}`
- validation errors: `{"inputPropertyPath": "errorMessage", "inputPropertyPath2": "errorMessage2"}`
- any other type of controller errors, for instance 404 explanations: `{"error": "errorCode", "error_message": "errorMessage"}`
- anything related to the response data but not the response data itself

The **data** field is used for response data (any type acceptable).