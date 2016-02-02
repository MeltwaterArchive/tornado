require 'spec_helper'

describe 'tornado::log_dir' do

  # Serverspec examples can be found at
  # http://serverspec.org/resource_types.html

  describe file('/var/log/tornado') do
    it { should be_directory }
    it { should be_owned_by 'apache' }
    it { should be_grouped_into 'apache' }
  end

end
