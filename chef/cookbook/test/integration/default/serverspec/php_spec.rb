require 'spec_helper'

describe 'tornado::php' do

  # Serverspec examples can be found at
  # http://serverspec.org/resource_types.html

  # Make sure our PHP Repos are enabled
  describe yumrepo('remi') do
    it { should be_enabled }
  end

  describe yumrepo('remi-php55') do
    it { should be_enabled }
  end

  # Packages
  describe package('php') do
    it { should be_installed }
  end

  describe package('php-pdo') do
    it { should be_installed }
  end

  describe package('php-mysqlnd') do
    it { should be_installed }
  end

  describe package('php-fpm') do
    it { should be_installed }
  end

  describe service('php-fpm') do
    it { should be_running.under('supervisor') }
  end

  describe command('php --version') do
    its(:stdout) { should contain 'PHP 5.5' }
  end

  describe command('composer --version') do
    its(:stdout) { should contain 'Composer version 1.0' }
  end

  # This makes sure date.timezone and display_errors are set
  describe file('/etc/php.ini') do
    it { should contain 'date.timezone = ' }
    it { should contain 'display_errors = On' }
  end

end
