
require "yaml"

vconfig = YAML::load_file("./Vagrantparams.yml")

Vagrant.configure("2") do |config|
    config.vm.box = "ubuntu/trusty64"
    config.vm.box_url = "https://cloud-images.ubuntu.com/vagrant/trusty/current/trusty-server-cloudimg-amd64-vagrant-disk1.box"
    config.vm.host_name = vconfig["hostname"]
    config.vm.network "private_network", :ip => vconfig['ip']
    config.vm.synced_folder ".", "/vagrant", nfs: vconfig["nfs"]

    config.vm.provider "virtualbox" do |vb|
        vb.customize [
            "modifyvm", :id,
            "--name", vconfig["hostname"],
            "--natdnsproxy1", "on",
            "--natdnshostresolver1", "on",
            "--memory", vconfig["memory"],
            "--cpus", vconfig['cpu']
        ]
    end

    config.vm.provision "ansible" do |ansible|
        ansible.sudo = true
        ansible.host_key_checking = false
        ansible.playbook = "provisioning/playbook.yml"
        ansible.verbose = "v"
    end
end
