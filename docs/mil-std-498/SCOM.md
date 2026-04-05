# Software Center Operator Manual (SCOM)

**Document Identifier:** LanCore-SCOM-001
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

This Software Center Operator Manual (SCOM) provides instructions for operating the **LanCore** system in a hosted environment.

### 1.2 System Overview

LanCore runs as a Docker container stack. The "center operator" is the system administrator responsible for hosting and maintaining the LanCore installation.

---

## 2. Referenced Documents

- [SIP](SIP.md) — Software Installation Plan
- [SSDD](SSDD.md) — System/Subsystem Design Description
- [SUM](SUM.md) — Software User Manual

---

## 3. Operational Procedures

### 3.1 Starting the System

```bash
# Start all services
docker compose up -d

# Verify services are running
docker compose ps
```

### 3.2 Stopping the System

```bash
# Graceful shutdown
docker compose stop

# Full shutdown and remove containers
docker compose down
```

### 3.3 Maintenance Mode

```bash
# Enable maintenance mode
docker compose exec app php artisan down

# Disable maintenance mode
docker compose exec app php artisan up
```

### 3.4 Database Operations

```bash
# Run pending migrations
docker compose exec app php artisan migrate --force

# Check migration status
docker compose exec app php artisan migrate:status

# Database backup (PostgreSQL — development with shared infrastructure)
docker exec infrastructure-pgsql pg_dump -U sail lancore > backup.sql

# Database backup (PostgreSQL — production)
docker compose exec pgsql pg_dump -U lancore lancore > backup.sql
```

### 3.5 Queue Management

```bash
# View Horizon dashboard
# Navigate to /horizon in browser (admin access required)

# Restart queue workers
docker compose exec app php artisan horizon:terminate

# Clear failed jobs
docker compose exec app php artisan queue:flush
```

### 3.6 Cache Management

```bash
# Clear application cache
docker compose exec app php artisan cache:clear

# Clear configuration cache
docker compose exec app php artisan config:clear

# Clear route cache
docker compose exec app php artisan route:clear

# Optimize for production
docker compose exec app php artisan optimize
```

---

## 4. Monitoring

### 4.1 Laravel Pulse

TBD — Dashboard access and key metrics to monitor.

### 4.2 Prometheus Metrics

TBD — Metric endpoints, Grafana dashboard setup, and alerting rules.

### 4.3 Log Management

```bash
# View application logs
docker compose exec app tail -f storage/logs/laravel.log

# View container logs
docker compose logs -f app
```

---

## 5. Troubleshooting

| Symptom | Possible Cause | Resolution |
|---------|---------------|------------|
| 500 Server Error | Missing .env, failed migration | Check logs, run migrations |
| Slow performance | Missing cache, N+1 queries | Run `artisan optimize`, check Telescope |
| Queue jobs not processing | Horizon not running | Restart Horizon workers |
| Storage errors | S3/Minio not accessible | Check storage credentials and connectivity |
| Session errors | Redis not running | Check Redis container status |

---

## 6. Backup and Recovery

TBD — To be detailed with backup schedules, retention policies, and recovery procedures.

---

## 7. Notes

This document will be expanded with detailed operational procedures as deployment patterns are established.
