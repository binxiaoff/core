#!/usr/bin/env bash

php /data/sites/unilend/bin/console dev:migrate:create_wallet 100 --env=prod
php /data/sites/unilend/bin/console dev:migrate:transactions 20000 --env=prod
