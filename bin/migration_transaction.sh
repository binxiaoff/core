#!/usr/bin/env bash

php /data/sites/unilend/bin/console dev:migrate:create_wallet 100
php /data/sites/unilend/bin/console dev:migrate:transactions 20000
