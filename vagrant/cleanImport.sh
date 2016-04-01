#!/usr/bin/env bash

# Execute this script in the directory containing all gziped SQL dumps
# Files other than data dumps will be deleted
# Excluded and backup tables are deleted
# Files will be unzipped
# Insert command will be exploded into several commands in order to be able to import it in CLI
#
# Argument passed to script must be the name of the directory containing last database dump

echo "Delete backup tables"
rm -f *backup*

for file in $PWD/*
do
    if [[ $file == *\.sql\.gz ]];
    then
        table=${file/*\/unilend./}
        table=${table/.sql.gz/}

        echo "Extract table $table"
        gunzip $file
        file=${file/.gz/}
        perl -p -e "s/\),\n/\);\nINSERT INTO \`$table\` VALUES /" $file > "$table.sql"
    fi

    if [ -f $file ] && ! [[ $file =~ schemas\.sql$ ]];
    then
        rm $file
    fi
done
