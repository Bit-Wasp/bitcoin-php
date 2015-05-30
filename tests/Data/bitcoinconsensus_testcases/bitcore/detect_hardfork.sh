#!/bin/bash
shopt -s nullglob
set -o nounset

if [ "$#" -ne 1 ] ; then
  echo "Usage: $0 DIRECTORY" >&2
  exit 1
fi

FILES=$1/*
for filename in $FILES; do
	ret=$(../valid_script $filename 2)
	if [ $ret -eq 1 ]; then
        bitcoreret=$(node valid_script.js $filename);
        succ=${bitcoreret:0:1}
        if [ $bitcoreret -ne 1 ]; then
            echo $ret $succ
            echo $filename
        fi

	fi
done
