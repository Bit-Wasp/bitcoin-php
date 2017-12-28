#!/bin/bash
for i in examples/*.php; do
    php $i > /dev/null
    if [ $? -ne 0 ]; then
        echo "Error running example code: $i";
        exit -1
    fi;
done
