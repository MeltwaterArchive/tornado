# DataSet API

## GET /datasets

Returns the list of system available global datasets.

### Requirements

This API endpoint accepts only `application/json` Content-Type requests.

#### Request
```
GET /datasets --header "Content-Type: application/json"
```

#### Response

##### 200 OK

```json
{
    "data": [
		{
        	"id": "<integer>",
			"name": "<string>",
			"dimensions": "<string>",
			"visibility": "<string>",
			"data": {}
        },
        {
            "id": "<integer>",
            "name": "<string>",
            "dimensions": "<string>",
            "visibility": "<string>",
            "data": {}
        }
    ],
    "meta": {
    	"count": "<int>"
    }
}
```

##### 400 Bad Request

Returns when Content-Type required for the request was invalid.