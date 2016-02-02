Tornado DataMapper
==================

Tornado DataMapper is a simple wrapper around Doctrine DBAL that implements a *Repository*
concept and uses POPO's (*Plain Old PHP Objects*) as data holders.

# Using with DI Container

All dependencies are already defined in the application's Dependency Injection Container.

If you have a Data Object that you want to be managed by a repository that connects to MySQL
database all you have to do is define the appropriate service:


    repository.users:
        class: Tornado\DataMapper\DoctrineRepository
        arguments:
            - @doctrine.dbal.connection.mysql
            - "Tornado\Entity\User"
            - "users"

First argument is Doctrine DBAL connection to use with the repository. Our default connection
is a service called `@doctrine.dbal.connection.mysql`.

Second argument for the repository constructor is a *class name* of the managed object.

Third argument for the repository constructor is a *table name* in your database.

Then you can just use your repository like this:


    $usersRepository = $container->get('repository.users');
    $users = $usersRepository->find([
        'active' => 1
    ], [
        'name' => \Tornado\DataMapper\DataMapperInterface::ORDER_ASCENDING
    ]);

    $theUser = $usersRepository->findOne(['id' => 234]);


# Data Object

Data objects are similar to Doctrine's *Entities*. They're just simple objects that
represent a row from a database.

All Data Objects MUST implement `Tornado\DataMapper\DataObjectInterface` which helps the
mapper to populate them with retrieved data or store them in a database.

Data Objects MUST populate themselves with data inside `::loadFromArray()` method and
they MUST convert themselves to arrays inside `::toArray()` method.

Example implementation:


    public function loadFromArray(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->email = $data['email'];
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email
        ];
    }


# Data Mapper (Repository)

Data Mappers are responsible for mapping Data Objects to database and vice versa. They are
similar in concept to a *Repository*.

Data Mappers MUST implement `Tornado\DataMapper\DataMapperInterface`.

## Doctrine Repository

The Tornado DataMapper comes with `Tornado\DataMapper\DoctrineRepository` which wraps around
Doctrine DBAL and MySQL connection.

Its constructor takes database connection, Data Object class name and related table name in
the database as constructor arguments.

## Extending Repository

No data retrieval logic should leak outside of a repository (e.g. Doctrine's `QueryBuilder`
MUST NOT be used outside of `DoctrineRepository`), so if you want to customize how
data is retrieved you should extend a repository (or implement new one from scratch) and add
appropriate methods to it.

### Method naming convention

- Any method in a repository that reads data from a source (database or other) and returns a
collection of objects MUST start with `::find*()` prefix.
- Any method in a repository that reads data from a source (database or other) and returns a
single object MUST start with `::findOne*()` prefix.
- Any method in a repository that reads data from a source filtered on a specific field MUST
be called `::findBy[FieldName]()` or `::findOneBy[FieldName]()` (e.g. `::findOneById($id)`,
`::findByAgency(Agency $agency)`).

### Behavior

- When no results were found for a method `::find*()` it MUST return an empty array or a
collection object.
- When no results were found for a method `::findOne*()` it MUST return `null`.

