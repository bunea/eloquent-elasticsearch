#!/bin/sh

PROJECT=`php -r "echo dirname(realpath('$0'));"`
STAGED_FILES_CMD=`git diff --name-only --diff-filter=ACMR HEAD | grep \\\\.php`

# Determine if a file list is passed
if [ "$STAGED_FILES_CMD" != "" ]
then
	oIFS=$IFS
	IFS='
	'
	SFILES="$1"
	IFS=$oIFS
else
    SFILES=`find app -type f -follow -print | grep \\\\.php`
fi

SFILES=${SFILES:-$STAGED_FILES_CMD}

echo "Checking PHP Lint..."
for FILE in $SFILES
do
	php -l -d display_errors=0 $PROJECT/$FILE
	if [ $? != 0 ]
	then
		echo "Fix the error before commit."
		exit 1
	fi
	FILES="$FILES $PROJECT/$FILE"
done

if [ "$FILES" != "" ]
then
	echo "Running Code Sniffer..."
	./vendor/bin/phpcbf --standard=$PROJECT/ruleset.xml --encoding=utf-8 -n -p $FILES
	./vendor/bin/phpcs --standard=$PROJECT/ruleset.xml --encoding=utf-8 -n -p $FILES
	if [ $? != 0 ]
	then
		echo "Fix the error before commit."
		exit 1
	fi
fi


echo "Running Static Analysis tool..."
./vendor/bin/phpstan analyse -l 5 -c $PROJECT/scripts/phpstan.neon src
if [ $? != 0 ]
then
    echo "Fix the errors before commit."
    exit 1
fi

echo "Running unit tests..."
./vendor/bin/phpunit
if [ $? != 0 ]
then
    echo "Fix the errors before commit."
    exit 1
fi

exit $?
