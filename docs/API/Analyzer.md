# Analyzer API

## POST /analyzer

Generates the appropriate analyze queries and the charts representing the data.

### Input Parameters

| **Name** | **Type** |**required**| **Description** |
|----------|----------|----|-----------------|
|worksheet_id|int|Yes|Id of the worksheet|
|recording_id|int|Yes|Id of the recording|
|dimensions|list|Yes|List of dimension targets; min 1, max 3|
|chart_type|string|Yes|Analyze chart type, possible values: *tornado*|
|type|string|Yes|Analyze type, possible values: *freqDist* or *timeSeries*|
|comparison|string|No|Comparison mode for the analyze, possible values: *baseline*, *compare*|
|measurement|string|No|Measurement mode for the analyze, possible values: *unique_authors*, *interactions*|
|secondary_recording_id|int|No|Id of the secondary recording|
|baseline_dataset_id|int|No|Id of the baseline dataset|
|filters|object|No|Filters to be applied for this analysis. Schema: {age: "array", gender: "array", region: "array", csdl: "string"}|
|start|int|No|Analyze start time represented in unix timestamp|
|end|int|No|Analyze start time represented in unix timestamp|

### Request

```json
{
    “worksheet_id”: <integer>,
    “recording_id”: “<string>”,
    “dimensions”: [“<target 1>”,”<target 2>”],
    "chart_type": "tornado",
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

### Response

*@TODO Do we need to return the charts here? Or should we just tell the UI to
refresh?*

#### 200 OK

```json
{
    "data": {
        “charts”: [
        <chart1>,
        <chart2>,
        ...
        ]
    },
    "meta": {}
}
```

#### 400 Bad Request

There were validation errors; this could be type-, range- or target-related.

```json
{
    "data": {},
    "meta": {
        “inputPath”: ”English error message”,
        “inputPath2”: ”English error message”,
    }
}
```

###### Example

```json
#1
{
    "data": {},
    "meta": {
        "dimensions": "This field is missing.",
		"dimensions.1": "Invalid target given.",
		"type": "Type must be one of the following value: timeSeries, freqDist"
    }
}

#2
{
    "data": {},
    "meta": {
        "filters.csdl": "This value should be of type string."
    }
}
```

#### 404 Not Found

A resource required for the request was not found. In this case recording or worksheet.