Box Idiomes
===========

A Symfony 7.4 LTS project to manage [Box Idiomes](https://www.boxidiomes.cat/admin) website content with custom ERP integrated functionalities.

---

#### Installation requirements

* PHP 8.4
* MySQL 8.0
* Git 2.0
* Composer 2.0
* set php.ini config max_input_vars > 10.000

#### Installation instructions

```bash
$ git clone git@github.com:Flexible-User-Experience/box-idiomes-4.git box-idiomes-4
$ cd box-idiomes-4
$ cp env.dist .env
$ nano .env
$ composer install
$ php bin/console messenger:consume async --env=prod
```

Remember to edit `.env` config file according to your system environment needs.

#### Testing suite commands

```bash
$ ./scripts/developer-tools/test-database-reset.sh
$ ./scripts/developer-tools/run-test.sh
```

#### Developer important notes

* Read about how to start a local web server instance [here](https://symfony.com/doc/current/setup/symfony_server.html)
* For now there is a problem with Fullcalendar v6 ES6 modules loading that makes impossible to execute `importmap:update` command

#### Messenger queues

In a production environment remember to properly configure Messenger queue consumers handled by a Supervisor instance. Read [these](https://symfony.com/doc/current/messenger.html#messenger-supervisor) instructions.

#### Code Style notes

Execute following link to be sure that php-cs-fixer will be applied automatically before each Git commit.

```bash
$ ln -s ../../scripts/githooks/pre-commit .git/hooks/pre-commit
```
