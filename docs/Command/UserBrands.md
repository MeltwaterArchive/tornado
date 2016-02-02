# User Brands Adding

The `tornado:user:brands` command manages User's brands assignment.

Run it as `./src/app/console tornado:user:brands user_id brands`
where "user_id" is numeric ID of the User and "brands" is a comma separated list of
brands IDs to which User will belong to. 

Running the command with `user_id` and `brands` arguments allows set Brands to the User.

Run it as `./src/app/console tornado:user:brands user_id` to list Brands to which User belongs to.

`./src/app/console tornado:user:brands user_id --clear` will clear the all User's brands assignment.