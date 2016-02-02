require 'spec_helper'

describe 'tornado::nginx' do

  # Serverspec examples can be found at
  # http://serverspec.org/resource_types.html

  describe package('mysql') do
    it { should be_installed }
  end

  # Commenting out for now
  # From Robin:
  #
  # Might not be possible to run mysql in docker :confused:
  # Not sure, never tried
  # Postgres doesn't like it htough
  #
  # describe service('mysqld') do
  #   it { should be_running }
  # end

end
