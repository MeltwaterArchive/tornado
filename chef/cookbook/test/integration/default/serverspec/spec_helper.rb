require 'serverspec'
require 'yarjuf'

RSpec.configure do |c|
  c.output_stream = File.open('/home/kitchen/serverspec-result.xml', 'w')
  c.formatter = 'JUnit'
end

set :backend, :exec
