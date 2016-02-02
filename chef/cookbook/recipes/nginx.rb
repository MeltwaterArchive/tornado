#
# Cookbook Name:: tornado
# Recipe:: nginx
#
# Copyright (c) 2015 The Authors, All Rights Reserved.
#
###############################################################
## PLEASE REMEMBER TO LOCK THIS COOKBOOK ON PRODUCTION FIRST ##
###############################################################

package 'nginx'

template '/etc/nginx/conf.d/tornado.conf' do
  source 'etc/nginx/conf.d/tornado.conf.erb'
end

file '/etc/nginx/conf.d/default.conf' do
  action :delete
end

bash 'insert_daemon_off' do
  user 'root'
  code 'echo "daemon off;" >> /etc/nginx/nginx.conf'
  not_if 'grep -q "daemon off" /etc/nginx/nginx.conf'
end

supervisor_service 'nginx' do
  command '/usr/sbin/nginx'
  action :enable
  autostart true
  autorestart true
  user 'root'
end
