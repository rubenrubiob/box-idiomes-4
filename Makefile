.DEFAULT_GOAL := help

hosts_file := "/etc/hosts"
hosts_line := "127.0.0.1 box-idiomes.test"

# App
app/super-admin-password:
	@docker exec box-idiomes-php sh -c "bin/console app:user:change-password super_admin 12345678"

# PHP
php/lint:
	@docker exec box-idiomes-php sh -c "phplint --configuration=.phplint.yml"

# Composer
composer/install:
	@docker exec box-idiomes-php sh -c "composer install"

composer/validate:
	@docker exec box-idiomes-php sh -c "composer validate --strict"

composer/outdated:
	@docker exec box-idiomes-php sh -c "composer outdated --minor-only --direct --strict"

# Xdebug
xdebug/enable:
	@docker exec box-idiomes-php sh -c "cp .docker/php/xdebug-enabled.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"
	@docker restart box-idiomes-php
	@docker restart box-idiomes-nginx

xdebug/disable:
	@docker exec box-idiomes-php sh -c "cp .docker/php/xdebug-disabled.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"
	@docker restart box-idiomes-php
	@docker restart box-idiomes-nginx

# Symfony
symfony/cache-clear:
	@docker exec box-idiomes-php sh -c "bin/console cache:clear"

symfony-test/cache-clear:
	@docker exec box-idiomes-php sh -c "bin/console cache:clear --env=test"

symfony/lint-container:
	@docker exec box-idiomes-php sh -c "bin/console lint:container"

symfony/lint-yaml:
	@docker exec box-idiomes-php sh -c "bin/console lint:yaml config src"

symfony/lint-twig:
	@docker exec box-idiomes-php sh -c "bin/console lint:twig templates"

symfony/messenger-consume:
	@docker compose --profile consumer up

code-style/fix:
	@docker exec box-idiomes-php sh -c "vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --verbose"

code-style/fix-file:
	@docker exec box-idiomes-php sh -c "vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --verbose"

# Doctrine
doctrine/migration-generate:
	@docker exec box-idiomes-php sh -c "bin/console doctrine:migrations:diff"

doctrine/migration-execute:
	@docker exec box-idiomes-php sh -c "bin/console doctrine:migrations:migrate --no-interaction"

doctrine/schema-validate:
	@docker exec box-idiomes-php sh -c "bin/console doctrine:schema:validate --skip-sync"

doctrine/db-drop:
	@docker exec box-idiomes-php sh -c "bin/console doctrine:database:drop --force --if-exists"

doctrine/db-create:
	@docker exec box-idiomes-php sh -c "bin/console doctrine:database:create --if-not-exists"

doctrine/db-create-schema:
	@docker exec box-idiomes-php sh -c "bin/console doctrine:schema:create --quiet"

doctrine/db-fixtures: doctrine/db-recreate
	@docker exec box-idiomes-php sh -c "bin/console hautelook:fixtures:load --no-interaction"

doctrine/db-recreate: \
	doctrine/db-drop \
	doctrine/db-create \
	doctrine/db-create-schema

# Doctrine test db
doctrine-test/db-drop:
	@docker exec box-idiomes-php sh -c "bin/console doctrine:database:drop --force --if-exists --env=test"

doctrine-test/db-create:
	@docker exec box-idiomes-php sh -c "bin/console doctrine:database:create --if-not-exists --env=test"

doctrine-test/db-create-schema:
	@docker exec box-idiomes-php sh -c "bin/console doctrine:schema:create --quiet --env=test"

doctrine-test/db-fixtures: doctrine-test/db-recreate
	@docker exec box-idiomes-php sh -c "bin/console hautelook:fixtures:load --no-interaction --env=test"

doctrine-test/db-recreate: \
	doctrine-test/db-drop \
	doctrine-test/db-create \
	doctrine-test/db-create-schema

# Test
test/controller: doctrine-test/db-fixtures symfony-test/cache-clear
	@docker exec box-idiomes-php sh -c "vendor/bin/phpunit -c phpunit.dist.xml --testsuite Controller"

test: test/controller

# Local
local-server/hosts-line:
	grep -qF $(hosts_line) $(hosts_file) || echo $(hosts_line) | sudo tee -a $(hosts_file)

local-server/login-info:
	$(info **********************************)
	$(info Local server is running)
	$(info URL: https://box-idiomes.test)
	$(info User: super_admin@email.com)
	$(info Password: 12345678)
	$(info **********************************)

git/install-hooks:
	ln -sf ../../scripts/githooks/pre-commit .git/hooks/pre-commit

install: \
	rebuild \
	xdebug/disable \
	composer/install \
	symfony/cache-clear \
	doctrine/db-fixtures \
	doctrine-test/db-recreate \
	app/super-admin-password \
	git/install-hooks \
	local-server/hosts-line \
	local-server/login-info

it: \
	test/controller \
	composer/validate \
	doctrine/schema-validate \
	php/lint \
	symfony/lint-container \
	symfony/lint-yaml \
	symfony/lint-twig

# Docker
start: CMD=up
startd: CMD=up -d
stop: CMD=stop
destroy: CMD=down

start startd stop destroy:
	@docker compose --profile dev $(CMD)

rebuild:
	make destroy
	COMPOSE_BAKE=true docker compose --profile dev build --pull --force-rm --no-cache
	COMPOSE_BAKE=true docker compose --profile consumer build --pull --force-rm
	make startd

restart: stop start

restartd: stop startd

bash:
	@docker exec -it box-idiomes-php bash
