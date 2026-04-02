# Computer Operation Manual (COM)

**Document Identifier:** LanCore-COM-001
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

This Computer Operation Manual (COM) provides instructions for operating the computer systems that host the **LanCore** software.

### 1.2 System Overview

LanCore runs within Docker containers on Linux hosts. This manual covers the host-level operations needed to support the application.

---

## 2. Referenced Documents

- [SCOM](SCOM.md) — Software Center Operator Manual
- [SIP](SIP.md) — Software Installation Plan

---

## 3. Computer System Description

### 3.1 Minimum Hardware

| Component | Requirement |
|-----------|------------|
| CPU | 2 cores (x86_64 or ARM64) |
| RAM | 4 GB |
| Storage | 10 GB SSD |
| Network | 100 Mbps with public IP |

### 3.2 Operating System

| OS | Versions |
|----|----------|
| Ubuntu | 22.04 LTS, 24.04 LTS |
| Debian | 12+ |
| Any Linux | With Docker Engine 20+ support |

### 3.3 Required Software

| Software | Version | Purpose |
|----------|---------|---------|
| Docker Engine | 20+ | Container runtime |
| Docker Compose | v2+ | Service orchestration |
| (Optional) nginx/Caddy | Latest | Reverse proxy with TLS |

---

## 4. System Operations

### 4.1 Docker Management

TBD — To be detailed with container lifecycle, resource limits, and health monitoring.

### 4.2 Network Configuration

TBD — To be detailed with firewall rules, port mappings, and TLS termination.

### 4.3 Storage Management

TBD — To be detailed with disk monitoring, log rotation, and volume management.

### 4.4 Security Updates

TBD — To be detailed with host OS patching, Docker image updates, and vulnerability scanning.

---

## 5. Notes

This document will be expanded as deployment and operations patterns are established. For application-level operations, see [SCOM](SCOM.md).
