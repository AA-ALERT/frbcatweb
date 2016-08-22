#!/bin/bash

# The setenv.sh script must contain a setting of the environemnt for the csv_to_vo_table
# script to work. This means that it has to set an environemnt in which the
# astropy python package is available
source setenv.sh

./csv_to_vo_table.py $1 $2 2>&1
