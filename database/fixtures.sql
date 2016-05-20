INSERT INTO `tornado`.`organization` (`id`, `name`, `permissions`) VALUES (1, 'Test Organization', 'api');
INSERT INTO `tornado`.`user` (`id`, `email`, `username`, `password`, `organization_id`, `type`) VALUES (1, 'admin@test.com', 'admin', '$2y$10$ZRpvzrzYPgDowIRYqnD/tuJxqz2.I/4J2f8pS.wVDrU7InOE9DN9S', 1, 0);
INSERT INTO `tornado`.`role` (`id`, `name`) VALUES (1, 'role_superadmin'), (2, 'role_admin'), (3, 'role_spaonly');
INSERT INTO `tornado`.`users_roles` (`user_id`, `role_id`) VALUES (1, 1), (1, 2);