#!/usr/bin/env bash

# Generation the extractions
php app/console salesforce:extraction companies
php app/console salesforce:extraction borrowers
php app/console salesforce:extraction projects
php app/console salesforce:extraction lenders

# send the extractions to dataloader
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/app/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=preteurs
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/app/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=companies
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/app/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=emprunteurs
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/app/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=projects