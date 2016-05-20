# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.require_version ">= 1.7.0", "< 2.0.0"

# Make sure all dependencies are installed
[
  { :name => "vagrant-omnibus", :version => ">= 1.4.1" },
  { :name => "vagrant-berkshelf", :version => ">= 4.0.3" },
  { :name => "vagrant-hostmanager", :version => ">= 1.5.0" },
  { :name => "vagrant-cachier", :version => ">= 1.2.0"}
].each do |plugin|

  Vagrant::Plugin::Manager.instance.installed_specs.any? do |s|
    req = Gem::Requirement.new([plugin[:version]])
    if (not req.satisfied_by?(s.version)) && plugin[:name] == s.name
      raise "#{plugin[:name]} #{plugin[:version]} is required. Please run `vagrant plugin install #{plugin[:name]}`"
    end
  end

  # Ideally we'd use has_plugin here but there's a bug in 1.7.2 so we need
  # to wait for 1.7.3 to be released
  #if not Vagrant.has_plugin?(plugin[:name], plugin[:version])
  #  raise "#{plugin[:name]} #{plugin[:version]} is required. Please run `vagrant plugin install #{plugin[:name]}`"
  #end
end

Vagrant.configure(2) do |config|
  config.vm.box = "bento/centos-6.7"

  config.vm.network "private_network", ip: "192.168.33.10"

  config.vm.hostname = "tornado.dev"
  config.vm.provider "virtualbox" do |vb|
    vb.memory = "1024"
  end

  # Shared folders
  config.vm.synced_folder ".", "/vagrant", :nfs => true
  config.vm.synced_folder ".", "/var/www/tornado", :nfs => true

  # Provisioning
  config.omnibus.chef_version = "12.10.24"

  config.berkshelf.berksfile_path = "chef/cookbook/Berksfile"
  config.berkshelf.enabled = true

  config.vm.provision :chef_zero do |chef|
    chef.run_list = [
      "recipe[tornado]",
    ]
    chef.nodes_path = "chef/cookbook"
    chef.log_level = :info
  end

  # We expect Vagrant to manage our hosts file
  config.hostmanager.enabled = true
  config.hostmanager.manage_host = true
  config.hostmanager.ignore_private_ip = false
  config.hostmanager.include_offline = true
  config.vm.provision :hostmanager


  # If we're destroying/creating boxes a lot, let's cache packages
  # using vagrant-cachier
  config.cache.scope = :machine

  config.cache.synced_folder_opts = {
    type: :nfs,
    mount_options: ['rw', 'vers=3', 'tcp', 'nolock']
  }
end
