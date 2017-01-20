#!/bin/bash
for i in examples/*.php; do
    php $i
    if [ $? != 0 ]; then
        echo "Error running example code";
        exit -1
    fi;
done
