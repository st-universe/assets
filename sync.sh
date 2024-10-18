#!/bin/sh

UPSTREAM=${1:-'@{u}'}
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse "$UPSTREAM")
BASE=$(git merge-base @ "$UPSTREAM")

send_mail () {
   recipient=$( jq '.game.admin.email' ../core/config.json )
   echo "sending failure email to ${recipient}"
   sendmail $recipient < syncFailure.mail
}


if [ $LOCAL = $REMOTE ]; then
    :
	#echo "git: up-to-date"
elif [ $LOCAL = $BASE ]; then
    echo "git: need to pull"

    git reset --hard HEAD && git pull
    if [ $? -eq 0 ]; then
  	echo "Success: pulled from git"
    else
        echo "Failure: Could not pull from git. Script failed" >&2
		send_mail
        exit 1
    fi

    php generator/building_generator/gen.php
    if [ $? -eq 0 ]; then
        echo "Success: building_generator"
    else
        echo "Failure: building_generator. Script failed" >&2
		send_mail
        exit 1
    fi

    php generator/field_generator/generator.php
    if [ $? -eq 0 ]; then
        echo "Success: field_generator"
    else
        echo "Failure: field_generator. Script failed" >&2
		send_mail
        exit 1
    fi

    exit 0
elif [ $REMOTE = $BASE ]; then
    echo "git: need to push"
else
    echo "git: workspace diverged!"
fi
