#!/bin/bash
set -o nounset

if [ "$#" -ne 1 ] ; then
  echo "Usage: $0 include_directory" >&2
  exit 1
fi


DBPos=0.10-positive
DBNeg=0.10-negative
shopt -s nullglob
for filename in $1/*; do
    newname=`sha1sum $filename | awk '{print $1;}'`
    ret=$(./valid_script $filename 2)
    if [ $ret -eq 1 ]; then
        cp $filename $DBPos/$newname
    else
        cp $filename $DBNeg/$newname
    fi
done

minimize() {
    DBTMP=.$1-tmp
    afl-cmin -i $1 -o $DBTMP ./valid_script @@ 0
    for filename in $DBTMP/*; do
        afl-tmin -i $filename -o $filename ./valid_script @@ 0
    done
    rm $1/*
    for filename in $DBTMP/*; do
        ./valid_script $filename 3 > $1/`basename $filename`
    done
    rm -rf $DBTMP
    for filename in $1/*; do
        newname=`sha1sum $filename | awk '{print $1;}'`
        mv $filename $1/$newname
    done
}

minimize $DBPos
minimize $DBNeg
