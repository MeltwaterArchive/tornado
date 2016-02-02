# Project's Worksheets API

## GET /api/project/{projectId}/worksheet/{worksheetId}

Returns information about a worksheet and its workbook and charts.

#### Request

```
GET /api/project/{projectId}/worksheet/{worksheetId} --header "Content-Type: application/json"
```

#### Response

```json
{
    "data": {
        "project" : {
            "id": <integer>,
            "brand_id": <integer>,
            "name": "<string>"
        },
        "worksheet" : {
            "id": <integer>,
            "workbook_id": <integer>,
            "name": "<string>",
            "rank": <integer>,
            "comparison": "<string>",
            "measurement": "<string>",
            "chart_type": "<string>",
            "analysis_type": "<string>",
            "recording_id": <integer>,
            "secondary_recording_id": "<integer|null>",
            "baseline_dataset_id": "<integer|null>",
            "filters": "<string(json)>",
            "dimensions": "<string(csv)>",
            "start": "<integer|null>",
            "end": "<integer|null>",
            "parent_worksheet_id": "<integer|null>",
            "created_at": "<integer>"
        },
        "charts": [
            {
                "id": <integer>,
                "worksheet_id": <integer>,
                "name": "<string>",
                "rank": <integer>,
                "type": "<string>",
                "data": "<string(json)>"
            }
            ...
        ]
    }
}
```

## POST /api/project/{projectId}/worksheet

Creates a worksheet in the given project.

### Input Parameters

| **Name** | **Type** |**required**| **Description** |
|----------|----------|----|-----------------|
|name|string|Yes|Name of the worksheet|
|workbook_id|int|Yes|ID of the parent workbook|
|chart_type|string|Yes|Analyze chart type, possible values: *tornado*|
|type|string|Yes|Analyze type, possible values: *freqDist* or *timeSeries*|
|comparison|string|No|Comparison mode for the analyze, possible values: *baseline*, *compare*|
|measurement|string|No|Measurement mode for the analyze, possible values: *unique_authors*, *interactions*|
|secondary_recording_id|int|No|Id of the secondary recording|
|baseline_dataset_id|int|No|Id of the baseline dataset|
|filters|object|No|Filters to be applied for this analysis. Schema: {age: "array", gender: "array", region: "array", csdl: "string"}|
|start|int|No|Analyze start time represented in unix timestamp|
|end|int|No|Analyze start time represented in unix timestamp|

#### Request

```json
{
    "name": "<string>",
    "workbook_id": <integer>,
    "chart_type": "<string>",
    “type”: “<freqDist|timeSeries>”,
    "comparison": "<baseline|compare>",
    "measurement": "<interactions|unique_authors>",
    “secondary_recording_id”: “<integer>”,
    “baseline_dataset_id”: “<integer>”,
    “filters”: “<object>”,
    “start”: <integer>,
    “end”: <integer>
}
```

#### Response

##### 201 OK

```json
{
    "data": {
        "project" : {
            "id": <integer>,
            "brand_id": <integer>,
            "name": "<string>"
        },
        "worksheet" : {
            "id": <integer>,
            "workbook_id": <integer>,
            "name": "<string>",
            "rank": <integer>,
            "comparison": "<string>",
            "measurement": "<string>",
            "chart_type": "<string>",
            "analysis_type": "<string>",
            "recording_id": <integer>,
            "secondary_recording_id": "<integer|null>",
            "baseline_dataset_id": "<integer|null>",
            "filters": "<string(json)>",
            "dimensions": "<string(csv)>",
            "start": "<integer|null>",
            "end": "<integer|null>",
            "parent_worksheet_id": "<integer|null>"
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

## PUT /api/project/{projectId}/worksheet/{worksheetId}

Updates a worksheet.

### Input Parameters

| **Name** | **Type** |**required**| **Description** |
|----------|----------|----|-----------------|
|name|string|Yes|Name of the worksheet|

#### Request

```json
{
    "name": "<string>"
}
```

#### Response

##### 201 OK

```json
{
    "data": {
        "project" : {
            "id": <integer>,
            "brand_id": <integer>,
            "name": "<string>"
        },
        "worksheet" : {
            "id": <integer>,
            "workbook_id": <integer>,
            "name": "<string>",
            "rank": <integer>,
            "comparison": "<string>",
            "measurement": "<string>",
            "chart_type": "<string>",
            "analysis_type": "<string>",
            "recording_id": <integer>,
            "secondary_recording_id": "<integer|null>",
            "baseline_dataset_id": "<integer|null>",
            "filters": "<string(json)>",
            "dimensions": "<string(csv)>",
            "start": "<integer|null>",
            "end": "<integer|null>",
            "parent_worksheet_id": "<integer|null>",
            "created_at": "<integer>"
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

## DELETE /api/project/{projectId}/worksheet/{worksheetId}

Deletes a worksheet.

#### Request

```
DELETE /api/project/{projectId}/worksheet/{worksheetId} --header "Content-Type: application/json"
```

#### Response

```json
{
    "data": {},
    "meta": {}
}

#### 404 Not Found

A resource required for the request was not found. In this case the project or the worksheet.
