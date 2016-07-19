#!/usr/bin/env bash

# Generation the extractions
php /data/sites/unilend/bin/console salesforce:extraction companies --env=prod
php /data/sites/unilend/bin/console salesforce:extraction borrowers --env=prod
php /data/sites/unilend/bin/console salesforce:extraction projects --env=prod
php /data/sites/unilend/bin/console salesforce:extraction lenders --env=prod

# send the extractions via Dataloader
java -cp /data/dataloader/target/dataloader-37.0.0-uber.jar -Dsalesforce.config.dir=/data/sites/unilend/app/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=preteurs
java -cp /data/dataloader/target/dataloader-37.0.0-uber.jar -Dsalesforce.config.dir=/data/sites/unilend/app/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=companies
java -cp /data/dataloader/target/dataloader-37.0.0-uber.jar -Dsalesforce.config.dir=/data/sites/unilend/app/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=emprunteurs
java -cp /data/dataloader/target/dataloader-37.0.0-uber.jar -Dsalesforce.config.dir=/data/sites/unilend/app/dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=projects