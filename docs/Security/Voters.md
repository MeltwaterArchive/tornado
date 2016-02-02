# Security Voters

Each Voter must implement a `\Tornado\Security\Authorization\VoterInterface`.

To create a new Voter, you need to register it as a service and tags with `voter` tag. For instance:
```yml
  voter.something:
    class: Security\UserVoter
    arguments: [@session]
    tags:
        - { name: voter }
```

The **voter** tag allows a `Tornado\Security\Authorization\AccessDecisionManager` take all registered
voters and perform an authorization process.

To do the authorization you **should** use `AccessDecisionManager` rather than separate `Voter` class.

