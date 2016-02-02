# User Agencies Adding

The `tornado:user:agencies` command manages User's agencies assignment.

Run it as `./src/app/console tornado:user:agencies user_id agencies`
where "user_id" is numeric ID of the User and "agencies" is a comma separated list of
agencies IDs to which User will belong to. 

Running the command with `user_id` and `agencies` arguments allows set Agencies to the User.

Run it as `./src/app/console tornado:user:agencies user_id` to list Agencies to which User belongs to.

`./src/app/console tornado:user:agencies user_id --clear` will clear the all User's agencies assignment.