# Creating a route

To create a new controller, you need to add it to the [services file](../src/config/services.yml). It should look something like this:

```yml
  controller.something:
    class: Controller\SomethingController
    arguments: [@request, @template, @example]
```

Once the controller is registered as a service, we can attach it to a route. To do this, edit the [routing file](../src/config/routes.yml).

Add an entry that looks like this:

```
internal_name:
  path: /foo/bar/
  defaults: { _controller: 'controller.something:create' }
```

Next, create `src/Controller/SomethingController` and write the `create` function. Visit /foo/bar to see the output.

# Responses

Controller should return:
 - the `Tornado\Controller\Result` for success http response
 - one of the Symfony HTTP Exception for error http response
 - redirect response
