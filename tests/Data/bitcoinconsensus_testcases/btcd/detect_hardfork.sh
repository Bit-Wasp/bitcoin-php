#!/bin/bash
if [ "$#" -ne 1 ] ; then
  echo "Usage: $0 DIRECTORY" >&2
  exit 1
fi

FILES=$1/*
shopt -s nullglob
for filename in $FILES; do
	ret=$(../valid_script $filename 2)
	if [ $ret -eq 1 ]; then
        goret=$(go run main.go $filename);
        succ=${goret:0:1}
        if [ $succ -ne 1 ]; then
            echo $ret $goret
            echo $filename
        fi

	fi
done
