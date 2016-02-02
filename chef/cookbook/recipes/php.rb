#
# Cookbook Name:: tornado
# Recipe:: php
#
# Copyright (c) 2015 The Authors, All Rights Reserved.
#
###############################################################
## PLEASE REMEMBER TO LOCK THIS COOKBOOK ON PRODUCTION FIRST ##
###############################################################

yum_repository 'remi' do
  description 'Les RPM de remi pour Enterprise Linux 6 - $basearch'
  mirrorlist 'http://rpms.famillecollet.com/enterprise/6/remi/mirror'
  gpgkey 'http://rpms.famillecollet.com/RPM-GPG-KEY-remi'
  action :create
end

yum_repository 'remi-php55' do
  description 'Les RPM de remi de PHP 5.5 pour Enterprise Linux 6 - $basearch'
  mirrorlist 'http://rpms.famillecollet.com/enterprise/6/php55/mirror'
  gpgkey 'http://rpms.famillecollet.com/RPM-GPG-KEY-remi'
  action :create
end

package 'php'
package 'php-fpm'
package 'php-pdo'
package 'php-mysqlnd'

ruby_block 'edit php-fpm.conf' do
  block do
    rc = Chef::Util::FileEdit.new('/etc/php-fpm.conf')
    rc.search_file_replace_line(/^daemonize = yes/, 'daemonize = no')
    rc.write_file
  end
  notifies :restart, 'supervisor_service[php-fpm]', :delayed
end

ruby_block 'edit php.ini' do
  block do
    rc = Chef::Util::FileEdit.new('/etc/php.ini')
    rc.search_file_replace_line(/^display_errors = Off/, 'display_errors = On')
    rc.write_file
  end
  notifies :restart, 'supervisor_service[php-fpm]', :delayed
end

bash 'insert_php_timezone' do
  user 'root'
  code 'echo "date.timezone = UTC" >> /etc/php.ini'
  not_if 'grep -q "^date.timezone" /etc/php.ini'
end

supervisor_service 'php-fpm' do
  command '/usr/sbin/php-fpm'
  action :enable
  autostart true
  autorestart true
  user 'root'
  notifies :restart, 'supervisor_service[nginx]', :delayed
end

remote_file 'composer' do
  source 'https://getcomposer.org/composer.phar'
  path '/usr/bin/composer'
  mode 0755
end
