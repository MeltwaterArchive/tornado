#
# Cookbook Name:: tornado
# Recipe:: default
#
# Copyright (c) 2015 The Authors, All Rights Reserved.
#
###############################################################
## PLEASE REMEMBER TO LOCK THIS COOKBOOK ON PRODUCTION FIRST ##
###############################################################

package 'vim-enhanced'
package 'epel-release'

include_recipe 'supervisor::default'
include_recipe 'tornado::php'
include_recipe 'tornado::memcached'
include_recipe 'tornado::log_dir'
include_recipe 'tornado::mysql'
include_recipe 'tornado::nginx'
include_recipe 'tornado::tornado'
include_recipe 'tornado::php_dev'
