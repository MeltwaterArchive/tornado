# Pagination

## Data Structure

Pagination data are returned under the `meta.pagination` Response property. The data contain the following properties:

- **firstPage** - first page
- **currentPage** - current page
- **totalPages** - number of the last page
- **nextPage** - number of the next page
- **previousPage** - number of the previous page
- **totalItemsCount** - the whole amount of items
- **perPage** - number of the max items per page
- **sortBy** - data sorting property
- **order** - data sorting order (default: `DESC`)

### Example

```json
"meta": {
    "pagination": {
        "firstPage": 1,
        "currentPage": 1,
        "totalPages": 7,
        "nextPage": 2,
        "previousPage": 1,
        "totalItemsCount": 13,
        "perPage": 2,
        "sortBy": "name",
        "order": "asc"
    }
}
```

## Usage

To build the pagination you should use `\Tornado\DataMapper\Paginator` class. It accepts any data provider class which implements the **PaginatorProviderInterface** (`\Tornado\DataMapper\DoctrineRepository` already implements it). After instantiating the class you must call the `::paginate` method which builds the pagination metadata. Moreover `\Tornado\DataMapper\Paginator` implements the `\JsonSerializable` interface for easier handling in controller result.