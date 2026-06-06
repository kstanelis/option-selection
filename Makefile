.DEFAULT_GOAL := help

DOCKER_COMP = docker compose
PHP_EXEC    = $(DOCKER_COMP) exec -T php
NODE_EXEC   = $(DOCKER_COMP) exec -T node

## —— Help ——————————————————————————————————————————————————————————————
.PHONY: help
help: ## Show this help
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-20s\033[0m %s\n", $$1, $$2}'

## —— Tests & quality gates ————————————————————————————————————————————————
.PHONY: phpunit
phpunit: ## Run PHPUnit. Single suite: make phpunit args="--testsuite Unit"
	$(PHP_EXEC) vendor/bin/phpunit $(args)

.PHONY: phpstan
phpstan: ## Run PHPStan static analysis
	$(PHP_EXEC) vendor/bin/phpstan analyse src tests --memory-limit=2G

.PHONY: phpcs
phpcs: ## Run PHP_CodeSniffer (PSR-12)
	$(PHP_EXEC) vendor/bin/phpcs src tests --standard=PSR12

.PHONY: phpcbf
phpcbf: ## Auto-fix PHP_CodeSniffer violations (PSR-12)
	$(PHP_EXEC) vendor/bin/phpcbf src tests --standard=PSR12

## —— Frontend assets ——————————————————————————————————————————————————————
.PHONY: assets-install
assets-install: ## Install node dependencies
	$(NODE_EXEC) npm install

.PHONY: assets-dev
assets-dev: ## Start the asset watcher
	$(NODE_EXEC) npm run dev

.PHONY: assets-build
assets-build: ## Production asset build
	$(NODE_EXEC) npm run build
