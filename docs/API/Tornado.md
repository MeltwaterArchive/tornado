# Tornado API

As per the standard DataSift Public API, each of the endpoints referenced in
this document should be prefixed with the API version in the form `v1.2`.

## Changelog

### 1.1 - 2015-12-10

 * Added the Recording API (POST only)
 * Corrected the response code for POST /tornado/project

## Account API

Information on the Account API can be found at the following address:
(http://dev.datasift.com/pylon/docs/api/acct-api-endpoints)

## PYLON API

Information on the PYLON API can be found at the following address:
(http://dev.datasift.com/pylon/docs/api/pylon-api-endpoints)

## Project API

The Tornado Project API should be called using DataSift Identity-level API keys
and not the main account key.

### POST /tornado/project

Creates a new Tornado project. `recordings` and `recording_id` are not allowed
to be submitted at the same time. Where `recording_id` is specified, it will be
interpreted as if `recordings` has been submitted with a single recording_id in
it.

#### Body

```json
{
    “name”: “<string>”,
    “recording_id”: “<hash from /pylon/start>”,
    "recordings": [
        "<recording_id1>",
        "<recording_id2>",
        ...
    ]
}
```

#### Response 201 Created

```json
{
    “id”: “<string>”,
    “name”: “<string>”,
    “recordings”: [
        “<recording_id1>”,
        “<recording_id2>”,
        ...
    ]
}
```

#### Response 400 Bad Request

There was a validation error with the request

#### Response 404 Not Found

The appropriate Recording was not found

#### Response 409 Conflict

A Project with the supplied name already exists

### GET /tornado/project/&lt;project_id&gt;

Gets a Tornado Project by id

#### Response 200 OK

```json
{
    “id”: “<string>”,
    “name”: “<string>”,
    “recordings”: [
        “<recording_id>”
    ]
}
```

#### Response 404 Not Found

The specified Project was not found, or one of the referred-to Recordings was not
found.

### GET /tornado/project[?page=&lt;page&gt;[&per_page=&lt;per_page&gt;]]

Gets a list of Tornado Projects, with a default page size of 25

#### Response 200 OK

```json
{
    “page”: <integer>,
    “pages”: <integer>, // the total number of pages
    “per_page”: <integer>,
    “count”: <integer>, // the total number of items found
    “projects”: [
        {
            “id”: “<string>”,
            “name”: “<string>”,
            “recordings”: [
                “<recording_id>”
            ]
        },
        {
            “id”: “<string>”,
            “name”: “<string>”,
            “recordings”: [
                “<recording_id>”
            ]
        },
        …
    ]
}
```

### PUT /tornado/project/&lt;project_id&gt;

Updates a Tornado Project’s name and recordings. Should either be left out, no
change will be made to the omitted item.

#### Body

```json
{
    “name”: “<string>”,
    "recordings": [
        "<recording_id1>",
        "<recording_id2>",
        ...
    ]
}
```

#### Response 200 OK

```json
{
    “id”: “<string>”,
    “name”: “<string>”,
    “recordings”: [
        “<recording_id>”
    ]
}
```

#### Response 400 Bad Request

There was a validation error with the request

#### Response 404 Not Found

The specified Project was not found, or one of the referred-to Recordings was not
found.

#### Response 409 Conflict

Another Project with the supplied name already exists

### DELETE /tornado/project/&lt;project_id&gt;

Deletes a Tornado project. Please note: this will not stop any associated
recordings

#### Response 204 No Content

The Project has been deleted

#### Response 404 Not Found

The specified Project could not be found

## Recording API

The Tornado Recording API should be called using DataSift Identity-level API keys
and not the main account key.

### POST /tornado/recording

Creates a Recording in Tornado; this does not create a recording in the PYLON
platform; the /pylon/start endpoint is required for this to occur.

#### Body

```json
{
    "hash": "<hash from pylon/compile>",
    "name": "<string>"
}
```

#### Response 201 Created

```json
{
    "id": "<string; the id of the Recording>",
    "hash": "<string; the CSDL hash the Recording represents>",
    "name": "<string>",
    "status": "<started|stopped>",
    "created_at": <integer; UNIX timestamp>
}
```

#### Response 400 Bad Request

There was a validation error with the request

#### Response 404 Not Found

The CSDL hash referred to could not be found

#### Response 409 Conflict

A Recording with the supplied hash has already been created