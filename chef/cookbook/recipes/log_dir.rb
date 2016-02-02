#
# Cookbook Name:: tornado
# Recipe:: mysql
#
# Copyright (c) 2015 The Authors, All Rights Reserved.
#
###############################################################
## PLEASE REMEMBER TO LOCK THIS COOKBOOK ON PRODUCTION FIRST ##
###############################################################

directory '/var/log/tornado' do
  user 'apache'
  group 'apache'
  mode 0755
  action :create
end
