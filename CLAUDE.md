# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Testing
- Run all tests: `vendor/bin/phpunit`
- Run tests with Docker: `docker-compose run --rm -w /tmp/ganesha -u ganesha client vendor/bin/phpunit`
- Install dependencies with Docker: `docker-compose run --rm -w /tmp/ganesha -u ganesha client composer install`

### Code Quality
- Run static analysis: `vendor/bin/psalm`
- PHP-CS-Fixer for code formatting: `vendor/bin/php-cs-fixer fix`

### Local Development with Docker
- Start data stores (Redis, Memcached, etc): `docker-compose up`
- The Docker setup provides isolated environments for testing against different storage adapters

## Architecture Overview

### Core Circuit Breaker Pattern
Ganesha is a PHP implementation of the Circuit Breaker pattern with a clean separation of concerns:

- **Main Entry Point**: `Ganesha` class provides the core API (`isAvailable()`, `success()`, `failure()`)
- **Builder Pattern**: Fluent builders for different strategies via `Builder::withRateStrategy()` and `Builder::withCountStrategy()`
- **Strategy Pattern**: Two failure detection strategies (`Rate` and `Count`) implement `StrategyInterface`
- **Storage Adapters**: Multiple adapters (Redis, Memcached, APCu, MongoDB) implement `AdapterInterface`

### Two Circuit Breaker Strategies

#### Rate Strategy
Tracks failure rate percentage over a time window:
- `timeWindow()` - Time period for evaluation
- `failureRateThreshold()` - Percentage threshold (1-100)
- `minimumRequests()` - Minimum requests before triggering
- `intervalToHalfOpen()` - Time before retrying

#### Count Strategy  
Tracks absolute failure count:
- `failureCountThreshold()` - Number of failures before triggering
- `intervalToHalfOpen()` - Time before retrying

### Storage Architecture
Two time window implementations based on storage capabilities:
- **SlidingTimeWindow** (Redis, MongoDB) - Rolling window of exact time periods
- **TumblingTimeWindow** (APCu, Memcached) - Fixed time segments

### HTTP Client Integrations
- **Guzzle Middleware**: `GuzzleMiddleware` for transparent Guzzle integration
- **Symfony HttpClient**: `GaneshaHttpClient` wrapper for Symfony HttpClient
- Service name extraction strategies and failure detection customization

### Key Components
- `Configuration` - Validates and stores strategy parameters
- `Context` - Manages circuit state and transitions
- `Storage` - Abstraction layer over different storage adapters
- `StorageKeys` - Configurable key naming for storage operations

### Events System
The circuit breaker publishes events:
- `EVENT_TRIPPED` - Circuit opened due to failures
- `EVENT_CALMED_DOWN` - Circuit closed after recovery
- `EVENT_STORAGE_ERROR` - Storage backend issues

## Testing Patterns

Tests are organized by component with extensive integration testing for storage adapters. VCR (Video Cassette Recorder) is used for HTTP interaction recording in Guzzle middleware tests.

The test structure mirrors the source structure under `tests/Ackintosh/` with comprehensive coverage of both strategies and all storage adapters.