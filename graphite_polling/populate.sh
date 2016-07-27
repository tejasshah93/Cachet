#!/bin/bash

FILE=$1

if [ -f $FILE ]; then
  php $FILE >> populate.log
fi

