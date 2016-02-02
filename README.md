Tornado
=======

## Description

A GUI to help agencies and non-data scientists to use DataSift's PYLON platform to gain insights efficiently

## Documentation

Documentation for the Tornado project can be found in [the docs directory](docs/).

## Change Log

Recent changes to the Tornado project can be found in [CHANGELOG.md](CHANGELOG.md).

## Configuration

Configuration for Tornado should be stored in `/etc/tornado/parameters.yml' - an example production configuration (also
used for the development VM described below) can be found in (parameters.yml)[resources/config/production/parameters.yml].

## Development VM

This project includes a Vagrant file to start a development Virtual Machine. First you need to install
[VirtualBox](https://www.virtualbox.org/), [Vagrant](https://www.vagrantup.com/) and
[Chef-dk](http://downloads.chef.io/chef-dk).

You also need some vagrant plugins:
```bash
vagrant plugin install vagrant-omnibus
vagrant plugin install vagrant-berkshelf
vagrant plugin install vagrant-cachier
vagrant plugin install vagrant-hostmanager
```

To start the virtual Machine:
```bash
vagrant up
```

Now you can log into the VM using:
```bash
vagrant ssh
```

The project folder will be in /vagrant; you should be able to view the running project at (http://tornado.dev).

The VM will be brought up with a single Organization and user - you can use the email address `admin@example.com` and
password `adminpassword` to get started.

## Getting started

First, you need to install all development dependencies using [Composer](https://getcomposer.org/) and
[Bower](http://bower.io/):

```bash
$ curl -sS https://getcomposer.org/installer | php
$ mv composer.phar /usr/local/bin/composer
$ npm install -g bower
```

This project include a Makefile that allows you to test and build the project with simple commands.
To see all available options:

```bash
make help
```

To install all the development dependencies:

```bash
make build_dev
```

## Running all tests

To run all tests, use the following make target:

```bash
make qa_all
```

This generates the phpunit coverage report in target/coverage.

Generate the documentation:

```bash
make docs
```

Generate static analysis reports in target/report:

```bash
make reports
```

Other make options allows you to install this library globally and build an RPM package. Please check all the available
options using `make help`.

### Running JS Tests

Client Side JavaScript tests are run through Grunt using the headless browser phantomJS.

```bash
make js_test
```

## Packaging

```bash
make rpm
```

## Server

To start a development server:

```
make server
```

Go to localhost:8000 and you should see your new project. If you want to change the port you can add it to the above
command:

```
make server PORT=50000
```

## Developer(s) Contact

* Christopher Hoult <christopher.hoult@datasift.com>
* Michael Heap <michael.heap@datasift.com>