# Usage: just <command>

# Default recipe - show available commands
default:
    @just --list

# Docker Management Commands

# Start all Docker containers
start:
    docker compose up -d

# Stop all Docker containers
stop:
    docker compose stop

# Restart all Docker containers
restart:
    docker compose restart

# Show status of all Docker containers
status:
    docker compose ps

# View logs from all containers
logs:
    docker compose logs -f

# Stop and remove all containers, networks, and volumes
down:
    docker compose down -v

# Rebuild and start containers
rebuild:
    docker compose up -d --build

# Symfony Console Commands

# Run Symfony console
# Usage: just console <command>
console *args:
    docker compose exec php php bin/console {{args}}

# Clear Symfony cache
cache-clear:
    docker compose exec php php bin/console cache:clear

# Run Symfony cache warmup
cache-warmup:
    docker compose exec php php bin/console cache:warmup

# Composer Commands

# Run composer command
# Usage: just composer <args>
composer *args:
    docker compose exec php composer {{args}}

# Install Composer dependencies
composer-install:
    docker compose exec php composer install

# Update Composer dependencies
composer-update:
    docker compose exec php composer update

# Add a Composer package
# Usage: just composer-add <package>
composer-add *package:
    docker compose exec php composer require {{package}}

# Add a Composer package as dev dependency
# Usage: just composer-add-dev <package>
composer-add-dev *package:
    docker compose exec php composer require --dev {{package}}

# Remove a Composer package
# Usage: just composer-remove <package>
composer-remove *package:
    docker compose exec php composer remove {{package}}

# Update Composer autoloader
composer-dump-autoload:
    docker compose exec php composer dump-autoload

# Show outdated packages
composer-outdated:
    docker compose exec php composer outdated

# PHP Commands

# Run PHP script
# Usage: just php-run <script>
php-run *script:
    docker compose exec php php {{script}}

# Run PHP interactive shell
php-shell:
    docker compose exec -it php php -a

# Run PHPUnit tests
test:
    docker compose exec php php bin/phpunit

# Run PHPUnit tests with coverage
test-coverage:
    docker compose exec php php bin/phpunit --coverage-html coverage

# Code Quality Commands

# Run PHP CS Fixer (dry run)
cs-check:
    docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --format=txt --verbose --diff --config=.php-cs-fixer.dist.php --ansi --allow-risky=yes

# Run PHP CS Fixer (fix)
cs-fix:
    docker compose exec php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --ansi --verbose --allow-risky=yes

# Run PHPStan
phpstan:
    docker compose exec php vendor/bin/phpstan analyse

# Utility Commands

# Open shell in PHP container
shell:
    docker compose exec -it php sh

# Show disk usage
du:
    docker compose exec php du -sh .

# Clean up unused Docker resources
docker-prune:
    docker system prune -f

# Quick development setup
dev:
    just start
    just composer-install
    just cache-clear
