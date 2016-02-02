# MAKEFILE
#
# @author      Nicola Asuni <nicola.asuni@datasift.com>
# @link        https://github.com/datasift/ms-app-tornado
# ------------------------------------------------------------------------------

# List special make targets that are not associated with files
.PHONY: help all test btest consoletest composertest docs jscs phpcs scss \
        scss_lint phpcs_test phpcbf phpcbf_test phpmd phpmd_test phpcpd phploc \
        phpdep phpcmpinfo report qa qa_test qa_all clean build build_dev \
        update install uninstall rpm

# Project version
VERSION=`cat VERSION`

# Project release number (packaging build number)
RELEASE=`cat RELEASE`

# name of RPM or DEB package
PKGNAME=ms-app-tornado

# Default installation path for code
SERVICEPATH=var/www/tornado/

# Configuration path
CONFIGPATH=etc/tornado/

# Default installation path for documentation
DOCPATH=usr/share/doc/$(PKGNAME)/

LOGPATH=var/log/tornado

# Installation path for the code
PATHINSTBIN=$(DESTDIR)/$(SERVICEPATH)

# Installation path for the configuration files
PATHINSTCFG=$(DESTDIR)/$(CONFIGPATH)

# Installation path for documentation
PATHINSTDOC=$(DESTDIR)/$(DOCPATH)

PATHINSTLOG=$(DESTDIR)/$(LOGPATH)

# Current directory
CURRENTDIR=`pwd`

# RPM Packaging path (where RPMs will be stored)
PATHRPMPKG=$(CURRENTDIR)/target/RPM

# Default port number for the example server
PORT?=8000

# Composer executable (disable APC to as a work-around of a bug)
COMPOSER=$(shell which php) -d "apc.enable_cli=0" $(shell which composer)

# --- MAKE TARGETS ---

# Display general help about this command
help:
	@echo ""
	@echo "Welcome to ms-app-tornado make."
	@echo "The following commands are available:"
	@echo ""
	@echo "    make qa          : Run the tests and code style checks"
	@echo "    make qa_test     : Run code style tests on the unit test"
	@echo "    make qa_all      : Run the targets: qa and qa_all"
	@echo ""
	@echo "    make test        : Run the PHPUnit tests"
	@echo "    make btest       : Run the Behat tests (behavior test)"
	@echo "    make consoletest : Run the console application"
	@echo "    make composertest : Run the composer validator"
	@echo ""
	@echo "    make scss        : Compile the scss files"
	@echo "    make jscs        : Run JSCS on the JavaScript source code and show any style violations"
	@echo "    make scss_lint   : Run scss-lint on the SCSS source code and show any style violations"
	@echo ""
	@echo "    make phpcs       : Run PHPCS on the source code and show any style violations"
	@echo "    make phpcs_test  : Run PHPCS on the test code and show any style violations"
	@echo ""
	@echo "    make phpcbf      : Run PHPCBF on the source code to fix style violations"
	@echo "    make phpcbf_test : Run PHPCBF on the test code to fix style violations"
	@echo ""
	@echo "    make phpmd       : Run PHP Mess Detector on the source code"
	@echo "    make phpmd_test  : Run PHP Mess Detector on the test code"
	@echo ""
	@echo "    make cs_build    : Run the clientside build process (JS/CSS/Images)"
	@echo ""
	@echo "    make phpcpd      : Run PHP Copy/Paste Detector"
	@echo "    make phploc      : Run PHPLOC to analyze the structure of the project"
	@echo "    make phpdep      : Run JDepend static analysis and generate graphs"
	@echo "    make phpcmpinfo  : Find out the minimum version and extensions required"
	@echo "    make report      : Run the targets: phpcpd, phploc and phpdep"
	@echo ""
	@echo "    make docs        : Generate source code documentation"
	@echo ""
	@echo "    make server     : Run the development server at http://localhost:"$(PORT)
	@echo ""
	@echo "    make clean       : Delete the vendor and target directory"
	@echo "    make build       : Clean and download the composer dependencies"
	@echo "    make build_dev   : Clean and download the composer dependencies including dev ones"
	@echo "    make update      : Update composer dependencies"
	@echo ""
	@echo "    make install     : Install this library"
	@echo "    make uninstall   : Remove all installed files"
	@echo ""
	@echo "    make rpm         : Build an RPM package"
	@echo ""

# alias for help target
all: help

# run the PHPUnit tests
test:
	APP_ENV=test ./src/vendor/bin/phpunit test/unit

# Run the Behat tests (behavior test)
btest:
	APP_ENV=test ./src/vendor/bin/behat --config ./behat.yml -f pretty,junit,html --out ,target/behat,target/behat.html

# tests the console application
consoletest:
	@php ./src/app/console

composertest:
	@(cd src && $(COMPOSER) validate)

# generate docs using phpDocumentor
docs:
	@rm -rf target/phpdocs
	./src/vendor/phpdocumentor/phpdocumentor/bin/phpdoc project:run \
	--target="target/phpdocs/" --directory="src/" --ignore="vendor/" \
	--encoding="UTF-8" --title="ms-app-tornado" --parseprivate

# Run JSCS on the JavaScript source code and show any style violations
jscs:
	@jscs -c ./.jscsrc src/public/assets/javascript

# Run scss on the SCSS source code and show any style violations
scss:
	@scss --update src/public/assets/scss:src/public/assets/css

# Run scss-lint on the SCSS source code and show any style violations
scss_lint:
	@scss-lint -c ./.scss-lint.yml src/public/assets/scss/

# run PHPCS on the source code and show any style violations
phpcs:
	@./src/vendor/bin/phpcs --standard=psr2 src/app src/lib

# run PHPCS on the test code and show any style violations
phpcs_test:
	@./src/vendor/bin/phpcs --ignore="./test/features/" --standard=psr2 test

# run PHPCBF on the source code and show any style violations
phpcbf:
	@./src/vendor/bin/phpcbf --ignore="./vendor/" --standard=psr2 src

# run PHPCBF on the test code and show any style violations
phpcbf_test:
	@./src/vendor/bin/phpcbf --standard=psr2 test

# Run PHP Mess Detector on the source code
phpmd:
	@./src/vendor/bin/phpmd src text ./phpmd.xml,unusedcode,design --exclude vendor

# run PHP Mess Detector on the test code
phpmd_test:
	@./src/vendor/bin/phpmd test text unusedcode,design

# run the client side (JS/CSS/Images) build process
cs_build:
	@npm --prefix ./src install ./src
	@grunt --gruntfile ./src/Gruntfile.js build

# run PHP Copy/Paste Detector
phpcpd:
	@mkdir -p ./target/report/
	@./src/vendor/bin/phpcpd src --exclude vendor > ./target/report/phpcpd.txt || true

# run PHPLOC to analyze the structure of the project
phploc:
	@mkdir -p ./target/report/
	@./src/vendor/bin/phploc src --exclude vendor > ./target/report/phploc.txt

# PHP static analysis
phpdep:
	@mkdir -p ./target/report/
	@./src/vendor/bin/pdepend --jdepend-xml=./target/report/dependencies.xml \
	--summary-xml=./target/report/metrics.xml \
	--jdepend-chart=./target/report/dependecies.svg \
	--overview-pyramid=./target/report/overview-pyramid.svg \
	--ignore=vendor ./src

# parse any data source to find out the minimum version and extensions required for it to run
phpcmpinfo:
	COMPATINFO=phpcompatinfo.json \
	./src/vendor/bartlett/php-compatinfo/bin/phpcompatinfo --no-ansi \
	analyser:run --alias source > ./target/report/phpcompatinfo.txt

# run the targets: phpcpd, phploc and phpdep
report: phpcpd phploc phpdep phpcmpinfo

# alias to run various tests and code style checks
#qa: test btest phpcs phpmd jscs scss
qa: test btest cs_build phpcs phpmd

# alias to run code style tests on the unit test
qa_test: phpcs_test phpmd_test

# alias to run targets: qa and qa_test
qa_all: qa qa_test consoletest composertest

# Run the development server
server:
	APP_ENV=development php -t src/public -S localhost:$(PORT)

# delete the vendor and target directory
clean:
	@rm -rf ./src/vendor/

# clean and download the composer dependencies
build:
	rm -rf ./src/vendor/
	cd src && $(COMPOSER) -n install --no-dev --no-interaction
	cd src && bower install

# clean and download the composer dependencies including dev ones
build_dev:
	rm -rf ./src/vendor/
	cd src && $(COMPOSER) -n install --no-interaction
	cd src && bower install

# update composer dependencies
update:
	(cd src && $(COMPOSER) -n update --no-interaction)

example:
	cd src/ && ls

# Install this application
install: uninstall
	mkdir -p $(PATHINSTBIN)
	cp -rf ./src/* $(PATHINSTBIN)
	rm -rf $(PATHINSTBIN)/public/assets/javascript/test/
	find $(PATHINSTBIN) -type d -exec chmod 755 {} \;
	find $(PATHINSTBIN) -type f -exec chmod 644 {} \;
	mkdir -p $(PATHINSTCFG)
	mkdir -p $(PATHINSTLOG)
	mkdir -p $(PATHINSTDOC)
	cp -f ./README.md $(PATHINSTDOC)
	cp -f ./VERSION $(PATHINSTDOC)
	cp -f ./RELEASE $(PATHINSTDOC)
	chmod -R 644 $(PATHINSTDOC)*

# Remove all installed files
uninstall:
	@rm -rf $(PATHINSTBIN)
	@rm -rf $(PATHINSTDOC)
	@rm -rf $(PATHINSTLOG)

# --- PACKAGING ---

# Build the RPM package for RedHat-like Linux distributions
rpm:
	@rm -rf $(PATHRPMPKG)
	rpmbuild \
	--define "_topdir $(PATHRPMPKG)" \
	--define "_package $(PKGNAME)" \
	--define "_version $(VERSION)" \
	--define "_release $(RELEASE)" \
	--define "_current_directory $(CURRENTDIR)" \
	--define "_servicepath /$(SERVICEPATH)" \
	--define "_docpath /$(DOCPATH)" \
	--define "_configpath /$(CONFIGPATH)" \
	--define "_logpath /$(LOGPATH)" -bb resources/rpm/rpm.spec
