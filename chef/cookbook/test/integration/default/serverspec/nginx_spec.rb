require 'spec_helper'

describe 'tornado::nginx' do

  # Serverspec examples can be found at
  # http://serverspec.org/resource_types.html

  describe package('nginx') do
    it { should be_installed }
  end

  describe service('nginx') do
    it { should be_running.under('supervisor') }
  end

  describe file('/etc/nginx/nginx.conf') do
    it { should contain 'daemon off;' }
  end

  describe file('/etc/nginx/conf.d/default.conf') do
    it { should_not exist }
  end

  describe file('/etc/nginx/conf.d/tornado.conf') do
    it { should contain 'server_name tornado.dev' }
    it { should contain 'server 127.0.0.1:9000' }
    it { should contain 'root /var/www/tornado' }
  end
end
