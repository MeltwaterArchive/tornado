require 'spec_helper'

describe 'tornado::default' do

  # Serverspec examples can be found at
  # http://serverspec.org/resource_types.html

  describe package('vim-enhanced') do
    it { should be_installed }
  end

  describe yumrepo('epel') do
    it { should be_enabled }
  end

  # Supervisor
  describe command('which supervisorctl') do
    its(:exit_status) { should eq 0 }
  end

end
