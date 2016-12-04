requiredPlugins = ["vagrant-vbguest"]

for plugin in requiredPlugins do
  unless Vagrant.has_plugin?(plugin)
    raise 'Please run: vagrant plugin install ' + plugin
  end
end

Vagrant.configure(2) do |config|
  config.vm.box = "ubuntu/xenial64"
  config.vm.hostname = "dev.minichan.org"

  config.vm.synced_folder ".", "/vagrant", type: "virtualbox"
  config.vm.network "private_network", ip: "172.16.194.44", hostsupdater: "skip"

  config.vm.provider "virtualbox" do |vb|
    vb.memory = 1024
    vb.cpus = 2
    vb.customize ["modifyvm", :id, "--nictype1", "virtio"]
    vb.customize ["modifyvm", :id, "--nictype2", "virtio"]
  end

  config.ssh.shell = 'bash'

  config.vm.provision "shell", path: "dev/provision.sh"
end
