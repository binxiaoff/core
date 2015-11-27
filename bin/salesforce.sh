#!/usr/bin/env bash

# Generation the extractions
php-cgi-5.3 /home/unilend/www/bin/cron.php -d Unilend\\data -c SalesForce -f extractLenders -s SalesforceLenders -t 10
php-cgi-5.3 /home/unilend/www/bin/cron.php -d Unilend\\data -c SalesForce -f extractCompanies -s SalesforceCompanies -t 10
php-cgi-5.3 /home/unilend/www/bin/cron.php -d Unilend\\data -c SalesForce -f extractBorrowers -s SalesforceBorrowers -t 10
php-cgi-5.3 /home/unilend/www/bin/cron.php -d Unilend\\data -c SalesForce -f extractProjects -s SalesforceProjects -t 10

# send the extractions to dataloader
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=preteurs
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=companies
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=emprunteurs
java -cp /home/unilend/dataloader/targetdataloader-26.0.0-uber.jar -Dsalesforce.config.dir=/home/unilend/www/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=projects