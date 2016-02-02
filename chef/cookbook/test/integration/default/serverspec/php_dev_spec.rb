require 'spec_helper'

describe 'tornado::php_dev' do

  # Serverspec examples can be found at
  # http://serverspec.org/resource_types.html

  # Packages
  describe package('git') do
    it { should be_installed }
  end

  describe package('php-xml') do
    it { should be_installed }
  end

  describe package('php-mbstring') do
    it { should be_installed }
  end

  describe package('php-intl') do
    it { should be_installed }
  end

  describe package('graphviz') do
    it { should be_installed }
  end

end
