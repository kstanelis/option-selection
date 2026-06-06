# Option Selection API

A Symfony 8 application exposing parameter options via an API Platform REST API, built as a foundation for the VisionGroup contact-lens option-selection challenge.

## Overview

This project is the first phase of a three-task implementation (see `Symfony Assesment Task.pdf` for details). The foundation scaffolds:

- **API Platform 4.3** with Doctrine ORM for data management
- Custom State Provider (`ParameterOptionsProvider`) to handle query parameter validation and option resolution
- Resolver interface (`ParameterOptionResolverInterface`) as a seam for different filtering strategies
- Stub resolver (`StaticParameterOptionResolver`) returning the full unfiltered dataset

## Architecture

```
GET /parameter
  ├─ Receives: query params ?parameter1=A&parameter2=X (optional)
  ├─ Validates: unknown names/values → 400 BadRequest
  ├─ Delegates to: ParameterOptionResolverInterface
  └─ Returns: JSON { "parameter1": ["A","B","C"], "parameter2": ["X","Y","Z"] }
```

Subsequent task branches will implement the resolver interface with real filtering logic:
- **Variant A** (`variant-a-constraint-list`): List-of-forbidden-combinations approach
- **Variant B** (`variant-b-valid-combination-table`): Valid-SKU-table approach

## Setup

### Prerequisites
- Docker and Docker Compose

### Run

```bash
# Start containers
docker compose up -d --wait

# Install dependencies and run migrations (if needed)
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# Access the API
curl -X GET 'https://localhost/api/parameter'
```

### API Documentation

Interactive Swagger UI available at:
```
https://localhost/api/docs
```

The `/api/docs` endpoint includes all query parameters with enum validation, so you can test requests directly from the UI.

## API Endpoints

### GET /parameter

Returns available options for given parameter selections.

**Query Parameters:**
- `parameter1` (optional): One of `A`, `B`, `C`
- `parameter2` (optional): One of `X`, `Y`, `Z`

**Example Requests:**

```bash
# No filters: return all options
curl -X GET 'https://localhost/api/parameter'

# With parameter1 selected
curl -X GET 'https://localhost/parameter?parameter1=A'

# With both parameters selected
curl -X GET 'https://localhost/parameter?parameter1=B&parameter2=Y'

# Invalid value: 400 Bad Request
curl -X GET 'https://localhost/parameter?parameter1=D'
```

**Response Format:**

```json
{
  "parameter1": ["A", "B", "C"],
  "parameter2": ["X", "Y", "Z"]
}
```

## Design Decisions

See `docs/tradeoffs.md` for discussion of the two solution variants and why Variant A was chosen.

## Quality Assurance

- **Unit Tests**: `tests/Unit/`
- **Integration Tests**: `tests/Integration/`
- **Functional Tests**: `tests/Playwright/`

Run all tests:
```bash
make phpunit
```

## Development

### Code Standards

- PSR-12 formatting (checked via `make phpcs`)
- Static analysis with PHPStan (`make phpstan-analyse`)
- Type hints and strict mode required

### Working with Doctrine

```bash
# Create a new migration after changing entities
docker compose exec php bin/console doctrine:migrations:diff

# Review the migration file in config/migrations/ then:
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

## References

- [API Platform Documentation](https://api-platform.com/)
- [Symfony 8 Documentation](https://symfony.com/doc/8.1/)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/current/)
