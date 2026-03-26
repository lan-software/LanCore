<p align="center">
  <img src="resources/images/logo-icon.png" alt="LanCore Logo" width="120" />
</p>

# LanCore

> LAN party & BYOD event management platform — the modern successor to eventula-manager. Inspired by Byceps and 10 years of Lan-Party experience at [sxlan.de](sxlan.de)

[![Tests](https://github.com/lan-software/LanCore/actions/workflows/tests.yml/badge.svg)](https://github.com/lan-software/LanCore/actions/workflows/tests.yml)
[![Frontend Tests](https://github.com/lan-software/LanCore/actions/workflows/frontend-tests.yml/badge.svg)](https://github.com/lan-software/LanCore/actions/workflows/frontend-tests.yml)
[![Linter](https://github.com/lan-software/LanCore/actions/workflows/lint.yml/badge.svg)](https://github.com/lan-software/LanCore/actions/workflows/lint.yml)
[![Docker](https://github.com/lan-software/LanCore/actions/workflows/docker-publish.yml/badge.svg)](https://github.com/lan-software/LanCore/actions/workflows/docker-publish.yml)
[![Coverage](https://codecov.io/gh/lan-software/LanCore/graph/badge.svg)](https://codecov.io/gh/lan-software/LanCore)

[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](composer.json)
[![Laravel](https://img.shields.io/badge/Laravel-%5E13.0-FF2D20?logo=laravel&logoColor=white)](composer.json)
[![Vue.js](https://img.shields.io/badge/Vue.js-%5E3.5-42b883?logo=vue.js&logoColor=white)](package.json)
[![Stripe](https://img.shields.io/badge/Stripe-supported-635BFF?logo=stripe&logoColor=white)](composer.json)
[![PayPal](https://img.shields.io/badge/PayPal-planned-003087?logo=paypal&logoColor=white)](#features)

---

## About

LanCore is a fresh-start rewrite built on **Laravel 13 + Inertia.js + Vue 3**. It organizes LAN parties and BYOD events with a clean domain-driven architecture, replacing the old eventula-manager with a maintainable, extensible foundation.

The initial goal was just tech-demo for "What could be done in 3 Days". To be honest, it escalated quickly into a full fledged frenzy.

> **Status:** Proof of Concept — core domains are functional, some areas are still in progress.  

**DO NOT USE THIS UNTIL RELEASE OF 1.0**

---

## Features

| Feature | Status |
|---|---|
| CI | Under Construction |
| Mobile & Desktop Friendly UI | ✅ |
| User Authentication & Roles | (✅)* |
| Event Management | ✅ |
| Venue Management | ✅ |
| Ticketing (types, assignment) | ✅ |
| Canvas-Based Seating Plans | ✅ |
| Sponsor Integration | ✅ |
| Program Plans | ✅ |
| Announcements & News | ✅ |
| Notifications System with Web Push, Mail | ⚖️ In Testing |
| Shop | 🚧 In Progress |
| Tournament Management | 🚧 In Progress |
| Stripe Integration | 🚧 In Progress |
| Game Server Management with Pelican Panel | Planned |
| Tournament Match Management with TMT2 | Planned |
| Eco-System with other App | 1/3 Apps functional |

*Action and Policy based architecture implemented, currently no focus on any reasonable checks as roles and permissions are not in the scope of the poc see [PoC.md](docs/poc/PoC.md)

Beyond LanCore there is already a bigger ecosystem of apps planned, partially developed. Soon following apps will join the lan-software team:
| Name | Scope | Current status |
| --- | --- | --- |
| LanBrackets | Basically a replacement for Challonge with its own laravel-package, framework agnostic bracket and tournament builder | Requirments Ready, Architecture WIP |
| LanShout | Shoutbox tailored for Lan-Events | Demo Ready since SX30, fully integrated with LanCores Integration Feature Domain |
| LanEntrance | Entrance Client, for orgas to validate, checkin and guide arriving guests | Requirements Ready, Architecture layed out | 
| LanHelp | Minified HelpDesk for Issues, FAQ and knowledge base across your lan events | Requirements Ready | 
| LanVote | Minified Voting Site | Requierements Ready |
| LanOrder | Ordering Takeaway Food as groups in the name of the organizer | Open |
| LanDisplay | Minified Application to show Webpages that can be used as OBS Websource | Open |

---

## Local Development

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) & Docker Compose

### Getting Started

```bash
# 1. Clone the repository
git clone https://github.com/lan-software/LanCore.git
cd LanCore

# 2. Install PHP dependencies (using Sail's PHP container)
docker run --rm -v "$(pwd)":/opt -w /opt laravelsail/php85-composer:latest composer install --ignore-platform-reqs

# 3. Copy environment file and generate app key
cp .env.example .env
vendor/bin/sail artisan key:generate

# 4. Start containers
vendor/bin/sail up -d

# 5. Run database migrations
vendor/bin/sail artisan migrate --seed

# 6. Install JS dependencies and start Vite dev server
vendor/bin/sail npm install
vendor/bin/sail npm run dev
```

The app will be available at **http://localhost**.

### Useful Commands

```bash
# Run tests
vendor/bin/sail artisan test --compact

# Format PHP (Pint)
vendor/bin/sail bin pint

# Format & lint JS
vendor/bin/sail npm run format
vendor/bin/sail npm run lint

# Build frontend assets
vendor/bin/sail npm run build

# Tail application logs
vendor/bin/sail artisan pail

# Open in browser
vendor/bin/sail open
```

---

## CI/CD

Performance/Load Testing is planned to use K6.

---

## Tech Stack

- **Backend:** PHP 8.5, Laravel 13, Laravel Octane (FrankenPHP)
- **Frontend:** Vue 3, Inertia.js v2, Tailwind CSS v4
- **Auth:** Laravel Fortify
- **Payments:** Laravel Cashier (Stripe)
- **Testing:** Pest v4
- **Containers:** Docker / Laravel Sail
