Box Idiomes
===========

A Symfony 7.4 LTS project to manage [Box Idiomes](https://www.boxidiomes.cat/admin) website content with custom ERP integrated functionalities.

---

#### Installation requirements

* Docker Engine (Docker + Docker Composer)

#### Installation instructions

```bash
$ git clone git@github.com:Flexible-User-Experience/box-idiomes-4.git box-idiomes-4
$ cd box-idiomes-4
$ make install
```

At the end, it will ask for your `sudo` password to add the required hosts to `/etc/hosts`.

Once finished, access this URL and accept the self-signed certificate: [https://box-idiomes.test](https://box-idiomes.test)

#### Make commands

`make symfony/messenger-consume`: starts Symfony's messenger consumer.

`make startd`: starts all services in background.

`make stop`: stops all services.

`make start`: starts all services in foreground.

`make restart`: stops all services and starts all services in foreground.

`make restartd`: stops all services and starts all services in background.

`make rebuild`: destroys all containers and recreates them again.

`make code-style/fix`: executes PHP CS Fixer for all files in project

`make it`: simulates the execution of a pipeline

`make test`: executes all test suites

`make test/controller`: executes `controller` test suite

`make doctrine/migration-generate`: generates new migration by performing a diff.

`make doctrine/migration-execute`: executes all pending migrations in dev database.

`make doctrine/db-fixtures`: recreates dev database.

`make doctrine-test/db-fixtures`: recreates test database.

`make bash`: Opens a terminal in PHP's container.

#### Developer important notes

* For now there is a problem with Fullcalendar v6 ES6 modules loading that makes impossible to execute `importmap:update` command

#### Messenger queues

In a production environment remember to properly configure Messenger queue consumers handled by a Supervisor instance. Read [these](https://symfony.com/doc/current/messenger.html#messenger-supervisor) instructions.

#### Code Style notes

Execute following link to be sure that php-cs-fixer will be applied automatically before each Git commit.

```bash
$ ln -s ../../scripts/githooks/pre-commit .git/hooks/pre-commit
```

#### Useful tips

`docker image prune -a -f`: Remove unused Docker images.

`docker builder prune -a -f`: Clean up Docker's build cache.

`docker system prune -a -f`: Clean up various Docker unused resources.

`docker compose down -v --rmi all --remove-orphans`: Removes all for services not defined in `compose.yaml`.

## URLs and credentials

- Extranet
    - [https://box-idiomes.test](https://neopro-extranet.test)
    - User: `super_admin@email.com`
    - Password: `12345678`
- Mailpit:
    - [http://localhost:8026/](http://localhost:8026/)
- MySQL:
    - User: `root`
    - Password: `root`
    - Host port: `4036`
    - Database: `neopro_extranet_db`
