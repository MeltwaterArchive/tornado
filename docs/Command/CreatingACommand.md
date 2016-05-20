# Creating Command

Each command must be registered as a service and extends the `Symfony\Component\Console\Command\Command`.

To create a new command, you need to add it to the [commands file](../src/config/console/commands.yml). It should look something like this:

```yml
  command.something:
    class: Command\SomethingCommand
    arguments: [@validator]
    tags:
        - { name: command }
```

The **command** tag **must** be applied to the command service in order to register it to the console application command bag.

