#
# Cookbook Name:: tornado
# Recipe:: mysql
#
# Copyright (c) 2015 The Authors, All Rights Reserved.
#
###############################################################
## PLEASE REMEMBER TO LOCK THIS COOKBOOK ON PRODUCTION FIRST ##
###############################################################

# There is no test for this resource as it won't exist in production

package 'mysql-server'

service 'mysqld' do
  action [:enable, :start]
end

# Remove anonymous users
execute 'mysql-remove-empty-user' do
  command "mysql -uroot -e\"
  DELETE FROM mysql.user WHERE User='';
  FLUSH PRIVILEGES;\""
  not_if '[ ! -d /vagrant ]'
end

# Create test database and set user permissions
execute 'mysql-test-database' do
  command "mysql -uroot -e\"
  CREATE DATABASE IF NOT EXISTS tornado_test;
  GRANT ALL ON tornado_test.* TO 'tornado'@'%' IDENTIFIED BY 'tornado';
  FLUSH PRIVILEGES;\""
  not_if "mysql -uroot -e'USE tornado_test;'"
end

# Create production database and set user permissions
execute 'mysql-production-database' do
  command "mysql -uroot -e\"
  CREATE DATABASE IF NOT EXISTS tornado;
  GRANT ALL ON tornado.* TO 'tornado'@'%' IDENTIFIED BY 'tornado';
  FLUSH PRIVILEGES;\""
  not_if "mysql -uroot -e'USE tornado;'"
end

# populate production database schema
execute 'import_db' do
  command 'mysql -uroot tornado < /vagrant/database/master.sql || true'
  action :run
  not_if "mysql -uroot -e'SELECT * FROM tornado.organization;'"
  notifies :run, 'execute[import_sample_data]', :immediately
end

# Import fixtures the first time
execute 'import_sample_data' do
  command 'mysql -uroot tornado < /vagrant/database/fixtures.sql || true'
  not_if '[ ! -d /vagrant ]'
  action :nothing
end
