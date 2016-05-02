#!/usr/bin/env bash

# Generation the extractions
php bin/console salesforce:extraction companies
php bin/console salesforce:extraction borrowers
php bin/console salesforce:extraction projects
php bin/console salesforce:extraction lenders

# send the extractions to dataloader
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=preteurs
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=companies
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=emprunteurs
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=projects