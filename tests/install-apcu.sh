#!/bin/bash

if [ "$TRAVIS_PHP_VERSION" == "5.5" ] ; then
    sudo apt-get install autoconf
    APCU=4.0.2
    wget http://pecl.php.net/get/apcu-$APCU.tgz
    tar zxvf apcu-$APCU.tgz
    cd "apcu-${APCU}"
    phpize && ./configure && make install && echo "Installed ext/apcu-${APCU}"
else
    exit 0
fi
