# Computer Programming Manual (CPM)

**Document Identifier:** LanCore-CPM-001
**Version:** 0.1.0
**Date:** 2026-04-02
**Status:** Draft — Scaffolded
**Classification:** Unclassified

### Author

| Role | Name |
|------|------|
| Project Lead | Markus Kohn |

---

## 1. Scope

### 1.1 Identification

This Computer Programming Manual (CPM) provides instructions for developing and extending the **LanCore** software.

### 1.2 System Overview

LanCore is an open-source PHP/Vue.js application. This manual guides developers on the conventions, patterns, and procedures for contributing code.

---

## 2. Referenced Documents

- [SDD](SDD.md) — Software Design Description
- [SDP](SDP.md) — Software Development Plan
- [STP](STP.md) — Software Test Plan

---

## 3. Development Environment Setup

### 3.1 Prerequisites

| Tool | Version |
|------|---------|
| Docker | 20+ |
| Git | 2.30+ |
| (Optional) IDE | PhpStorm, VS Code with Intelephense |

### 3.2 Initial Setup

```bash
git clone <repository-url>
cd LanCore
cp .env.example .env
docker run --rm -v $(pwd):/app composer install
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
vendor/bin/sail npm install
vendor/bin/sail npm run dev
```

---

## 4. Coding Conventions

### 4.1 PHP Conventions

- PSR-12 code style enforced by Laravel Pint
- PHP 8.5 constructor property promotion
- Explicit return type declarations
- PHPDoc blocks for complex methods
- No inline comments unless logic is exceptionally complex

### 4.2 Domain-Driven Design

- Business logic in `app/Domain/{DomainName}/Actions/`
- One action per operation (CreateEvent, UpdateEvent, etc.)
- Controllers are thin — delegate to Actions
- Form Requests for validation
- Policies for authorization

### 4.3 Frontend Conventions

- Vue 3 Composition API with `<script setup>`
- TypeScript for type safety
- Tailwind CSS v4 for styling
- reka-ui for headless components

### 4.4 Testing

- Pest PHP for all tests
- Feature tests for HTTP flows
- Unit tests for isolated logic
- Factories for test data

---

## 5. Common Development Tasks

### 5.1 Adding a New Domain Feature

TBD — Step-by-step guide for creating a new domain module with model, migration, action, controller, policy, request, and tests.

### 5.2 Adding an API Endpoint

TBD — Guide for adding stateless API routes with token authentication.

### 5.3 Adding a Webhook Event

TBD — Guide for adding a new webhook event type with payload definition.

### 5.4 Adding a Vue Page

TBD — Guide for creating a new Inertia page with proper props, layout, and routing.

---

## 6. Artisan Command Reference

```bash
# Create model with all related files
vendor/bin/sail artisan make:model ModelName -mfsc --no-interaction

# Create controller
vendor/bin/sail artisan make:controller Domain/ControllerName --no-interaction

# Create form request
vendor/bin/sail artisan make:request Domain/RequestName --no-interaction

# Create policy
vendor/bin/sail artisan make:policy ModelPolicy --model=Model --no-interaction

# Create test
vendor/bin/sail artisan make:test --pest Feature/DomainTest --no-interaction

# Run Wayfinder generation
vendor/bin/sail artisan wayfinder:generate
```

---

## 7. Notes

This document will be expanded with detailed development guides, architectural decision records, and contribution guidelines as the project matures.
