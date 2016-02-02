# Project's Workbooks API

## GET /api/project/{projectId}/workbook

Lists all workbooks and their worksheets in the project.

#### Request

```
GET /api/project/{projectId}/workbook --header "Content-Type: application/json"
```

#### Response

```json
{
  "data": {
    "workbooks": [
      {
        "id": <integer>,
        "project_id": <integer>,
        "name": <string>,
        "recording_id": <integer>,
        "rank": <integer>,
        "worksheets": <array>
      },
      ...
    ]
  },
  "meta": {}
}
```

## GET /api/project/{projectId}/workbook/{workbookId}

Returns information about a workbook and its worksheets.

#### Request

```
GET /api/project/{projectId}/workbook/{workbookId} --header "Content-Type: application/json"
```

#### Response

```json
{
  "data": {
    "workbook": [
      {
        "id": <integer>,
        "project_id": <integer>,
        "name": <string>,
        "recording_id": <integer>,
        "rank": <integer>,
        "worksheets": <array>
      },
      ...
    ]
  },
  "meta": {}
}
```

## POST /api/project/{projectId}/workbook

Creates a workbook in the given project.

### Input Parameters

| **Name** | **Type** |**required**| **Description** |
|----------|----------|----|-----------------|
|name|string|Yes|Name of the workbook|
|recording_id|int|Yes|Id of the recording|

#### Request

```json
{
    "name": "<string>",
    “recording_id”: <integer>
}
```

#### Response

##### 201 OK

```json
{
  "data": {
    "workbook": {
      "id": <integer>,
      "project_id": <integer>,
      "name": "<string>",
      "recording_id": <integer>,
      "worksheets": <array>
    }
  },
  "meta": {}
}
```

#### 400 Bad Request

There were validation errors.

```json
{
    "data": {},
    "meta": {
        “inputPath”: ”English error message”,
        “inputPath2”: ”English error message”,
    }
}
```

#### 404 Not Found

A resource required for the request was not found. In this case the project.

## PUT /api/project/{projectId}/workbook/{workbookId}

Updates a workbook.

### Input Parameters

| **Name** | **Type** |**required**| **Description** |
|----------|----------|----|-----------------|
|name|string|Yes|Name of the workbook|
|recording_id|int|Yes|Id of the recording|

#### Request

```json
{
    "name": "<string>",
    “recording_id”: <integer>
}
```

#### Response

##### 201 OK

```json
{
  "data": {
    "workbook": {
      "id": <integer>,
      "project_id": <integer>,
      "name": "<string>",
      "recording_id": <integer>,
      "rank": <integer>,
      "worksheets": <array>
    }
  },
  "meta": {}
}
```

#### 400 Bad Request

There were validation errors.

```json
{
    "data": {},
    "meta": {
        “inputPath”: ”English error message”,
        “inputPath2”: ”English error message”,
    }
}
```

#### 404 Not Found

A resource required for the request was not found. In this case the project.

## DELETE /api/project/{projectId}/workbook/{workbookId}

Deletes a workbook.

#### Request

```
DELETE /api/project/{projectId}/workbook/{workbookId} --header "Content-Type: application/json"
```

#### Response

```json
{
    "data": {},
    "meta": {}
}

#### 404 Not Found

A resource required for the request was not found. In this case the project or the workbook.
