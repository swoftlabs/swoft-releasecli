# ref https://gist.github.com/inhere/c98df2b096ee3ccc3d36ec61923c9fc9
.DEFAULT_GOAL := help
.PHONY: all update help addrmt fpush release

##There are some make command for the project
##

TAG=$(tag)

help:
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | sed -e 's/: / /'

##Available Commands:

  update:	## Update current project code to latest by git pull
update:
	git checkout . && git pull
	chmod a+x bin/releasecli

  sami:		## Gen classes docs by sami.phar
classdoc:
# rm -rf docs/classes-docs
	rm -rf docs/classes-docs
# gen docs
	php sami.phar update ./script/sami.doc.inc

  all:		## Run update, addrmt, fpush and release
all: update

