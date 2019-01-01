#! /bin/bash

#
# Call: ./simple.sh <Controller>/<action>
# E.g.  ./simple.sh Homepage/index
#

export APP_ENV="local"
export DB_NAME="casino"
export DB_HOST="localhost"
export DB_USER="casino"
export DB_PASS="casino"

# run appc.php
bin=$(dirname $0)
appc="$bin/cli/appc.php"
php $appc ${@:1}
status=$?

exit $status
