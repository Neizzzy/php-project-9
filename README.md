### Hexlet tests and linter status:
[![Actions Status](https://github.com/Neizzzy/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Neizzzy/php-project-9/actions)

### SonarQube Maintainability:
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=Neizzzy_php-project-9&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=Neizzzy_php-project-9)

[![PHP CI](https://github.com/Neizzzy/php-project-9/actions/workflows/ci.yml/badge.svg)](https://github.com/Neizzzy/php-project-9/actions/workflows/ci.yml)

# Page Analyser
___

## Requirements:
___
- PHP >= 8.3
- Composer
- PostgreSQL
- Make

## Usage
___

### 1. Create .env file and add DATABASE_URL variable
Add your database connection (example):
```
DATABASE_URL=postgresql://username:password@localhost:5432/your_database_name
```

### 2. Run commands:
```
make install
```
```
make db-prepare
```
```
make start
```
or
```
make start-local
```
___
Demo: https://neizzzy-page-analyser.onrender.com/
