# Login

## POST /login

Sign in a user unless invalid credentials provided. Otherwise returns 400 http response with errors.

### Requirements

Accepts `form-data`, `x-www-form-urlencodede` requests.

### Input Parameters

| **Name** | **Type** |**required**| **Description** |
|----------|----------|----|-----------------|
|login|string|Yes|User email or username|
|password|string|Yes|User plain password|

#### Request
```
POST /login --data "login=<login>&password=<password>"
```

#### Response

##### 302 OK

Redirects successfully logged user to the secured area dashboard.

#### 400 Bad Request

There were validation errors. Returns `application/json` or `document/html` response.

```json
{
    "data": {},
    "meta": {
        “login”: ”English error message”,
        “password”: ”English error message”,
    }
}
```

###### Example

```json
#1 application/json response
{
    "data": {},
    "meta": {
        "login": "Invalid username or email.",
		"password": "Incorrect password."
    }
}
```