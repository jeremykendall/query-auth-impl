Vagrant.configure("2") do |config|
  config.vm.box = "precise64"
  config.vm.box_url = "http://files.vagrantup.com/precise64.box"

  config.vm.network :private_network, ip: "192.168.56.102"
    config.ssh.forward_agent = true

  config.vm.provider :virtualbox do |v|
    v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    v.customize ["modifyvm", :id, "--memory", 512]
    v.customize ["modifyvm", :id, "--name", "query-auth-implementation"]
  end
  
  config.vm.synced_folder "./", "/var/sites/dev.query-auth", id: "vagrant-root", :owner => "vagrant", :group => "www-data", :extra => "dmode=775,fmode=664" 
  config.vm.provision :shell, :inline => "sudo apt-get update"

  config.vm.provision :shell, :inline => 'echo -e "mysql_root_password=
controluser_password=awesome" > /etc/phpmyadmin.facts;'

  config.vm.provision :puppet do |puppet|
    puppet.manifests_path = "manifests"
    puppet.module_path = "modules"
    puppet.options = ['--verbose']
  end
end
