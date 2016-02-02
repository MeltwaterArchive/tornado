#
# Cookbook Name:: tornado
# Recipe:: memcached
#
# Copyright (c) 2015 The Authors, All Rights Reserved.
#
###############################################################
## PLEASE REMEMBER TO LOCK THIS COOKBOOK ON PRODUCTION FIRST ##
###############################################################

include_recipe 'memcached'

package 'php-pear'
package 'php-pecl-memcached'
package 'telnet'
