# System/Subsystem Specification (SSS)

**Document Identifier:** LanCore-SSS-001
**Version:** 0.1.0
**Date:** 2026-04-02
**Status:** Draft
**Classification:** Unclassified

### Author

| Role | Name |
|------|------|
| Project Lead | Markus Kohn |

---

## 1. Scope

### 1.1 Identification

This System/Subsystem Specification (SSS) specifies the requirements for the **LanCore** system — a LAN Party & BYOD Event Management Platform.

### 1.2 System Overview

LanCore is a self-hosted web application built on Laravel 13, Vue.js 3, and Inertia.js v2 that provides comprehensive event management for LAN parties and BYOD events. The system encompasses event creation, ticketing, payments, seating, scheduling, news, notifications, integrations, and user management.

### 1.3 Document Overview

This document specifies the system-level requirements for LanCore, organized by capability, interface, performance, security, and environmental constraints.

---

## 2. Referenced Documents

- [OCD](OCD.md) — Operational Concept Description
- [SRS](SRS.md) — Software Requirements Specification
- [IRS](IRS.md) — Interface Requirements Specification
- [SDD](SDD.md) — Software Design Description

---

## 3. Requirements

### 3.1 Required States and Modes

| State | Description | Transitions |
|-------|-------------|-------------|
| Normal | Full operation, all features available | To Maintenance, To Queue-Only |
| Maintenance | Application returns 503, scheduled work paused | To Normal |
| Queue-Only | Web interface disabled, queue workers process jobs | To Normal |
| Development | Debug features enabled, Telescope active | To Normal |

### 3.2 System Capability Requirements

#### 3.2.1 Event Management (CAP-EVT)

| Req ID | Requirement |
|--------|------------|
| CAP-EVT-001 | The system shall allow admins to create events with name, description, dates, and banner images |
| CAP-EVT-002 | The system shall support event statuses: draft and published |
| CAP-EVT-003 | The system shall allow assignment of venues with addresses and images to events |
| CAP-EVT-004 | The system shall emit `EventPublished` events and dispatch webhooks on publication |
| CAP-EVT-005 | The system shall support seat capacity configuration per event |
| CAP-EVT-006 | The system shall maintain audit trails for event modifications |

#### 3.2.2 Ticketing (CAP-TKT)

| Req ID | Requirement |
|--------|------------|
| CAP-TKT-001 | The system shall support multiple ticket types per event with individual pricing and quotas |
| CAP-TKT-002 | The system shall support ticket categories and groups for logical organization |
| CAP-TKT-003 | The system shall enforce per-user ticket purchase limits |
| CAP-TKT-004 | The system shall support ticket add-ons (purchasable extras attached to tickets) |
| CAP-TKT-005 | The system shall track ticket statuses: available, assigned, used, checked_in |
| CAP-TKT-006 | The system shall generate unique validation IDs for each ticket |
| CAP-TKT-007 | The system shall support ticket assignment (owner, manager, user roles per ticket) |
| CAP-TKT-008 | ~~The system shall support seating row tickets linked to seat plans~~ **(Deprecated — superseded by CAP-TKT-011)** |
| CAP-TKT-009 | The system shall support purchase windows (start/end dates for ticket sales) |
| CAP-TKT-010 | The system shall support ticket locking mechanisms |
| CAP-TKT-011 | The system shall support group tickets with configurable max users per ticket, where seat capacity is calculated as seats_per_ticket × max_users_per_ticket |
| CAP-TKT-012 | The system shall support configurable check-in modes per ticket type: individual (per-user) or group (all users at once) |

#### 3.2.3 Shop and Payments (CAP-SHP)

| Req ID | Requirement |
|--------|------------|
| CAP-SHP-001 | The system shall provide a shopping cart per user per event |
| CAP-SHP-002 | The system shall support Stripe Checkout as a payment provider |
| CAP-SHP-003 | The system shall support on-site (cash/manual) payment collection |
| CAP-SHP-004 | The system shall track order statuses: pending, confirmed, failed, refunded, cancelled |
| CAP-SHP-005 | The system shall support voucher codes with fixed-amount or percentage discounts |
| CAP-SHP-006 | The system shall support configurable purchase requirements (terms acceptance, conditions) |
| CAP-SHP-007 | The system shall support global purchase conditions and payment-provider-specific conditions |
| CAP-SHP-008 | The system shall track checkout acknowledgements per user |
| CAP-SHP-009 | The system shall fulfill orders asynchronously via Stripe `checkout.session.completed` webhook when users do not return to the checkout success URL |
| CAP-SHP-010 | The system shall ensure order fulfillment is idempotent — both the success URL redirect and webhook may trigger fulfillment, but tickets shall only be created once |

#### 3.2.4 Programs and Scheduling (CAP-PRG)

| Req ID | Requirement |
|--------|------------|
| CAP-PRG-001 | The system shall support programs with multiple time slots per event |
| CAP-PRG-002 | The system shall support program visibility levels: public, registered_only, hidden |
| CAP-PRG-003 | The system shall support time slot ordering and visibility control |
| CAP-PRG-004 | The system shall emit notifications when time slots are approaching |
| CAP-PRG-005 | The system shall allow users to subscribe to program notifications |

#### 3.2.5 Seating (CAP-SET)

| Req ID | Requirement |
|--------|------------|
| CAP-SET-001 | The system shall provide a canvas-based seat plan editor |
| CAP-SET-002 | The system shall store seat plans as JSONB data for flexibility |
| CAP-SET-003 | The system shall link seat plans to events |
| CAP-SET-004 | The system shall maintain audit trails for seat plan modifications |

#### 3.2.6 Sponsors (CAP-SPO)

| Req ID | Requirement |
|--------|------------|
| CAP-SPO-001 | The system shall support sponsor profiles with name, logo, and link |
| CAP-SPO-002 | The system shall support sponsor tier levels (e.g., Gold, Silver, Bronze) |
| CAP-SPO-003 | The system shall support sponsor assignment to events, programs, and time slots |
| CAP-SPO-004 | The system shall support sponsor manager user assignments |

#### 3.2.7 News (CAP-NWS)

| Req ID | Requirement |
|--------|------------|
| CAP-NWS-001 | The system shall support rich-text news articles with SEO metadata |
| CAP-NWS-002 | The system shall support article visibility: public, draft, members_only, archived |
| CAP-NWS-003 | The system shall support user comments on articles |
| CAP-NWS-004 | The system shall support comment voting (upvote/downvote) |
| CAP-NWS-005 | The system shall send notifications to users on article publication |

#### 3.2.8 Announcements (CAP-ANN)

| Req ID | Requirement |
|--------|------------|
| CAP-ANN-001 | The system shall support announcements with priority levels: low, normal, high, critical |
| CAP-ANN-002 | The system shall support event-scoped and global announcements |
| CAP-ANN-003 | The system shall track per-user announcement dismissals |
| CAP-ANN-004 | The system shall deliver push notifications for announcements |

#### 3.2.9 Achievements (CAP-ACH)

| Req ID | Requirement |
|--------|------------|
| CAP-ACH-001 | The system shall support achievement definitions with event-based triggers |
| CAP-ACH-002 | The system shall automatically grant achievements when trigger conditions are met |
| CAP-ACH-003 | The system shall track achievement progress and earned timestamps per user |
| CAP-ACH-004 | The system shall notify users when achievements are earned |

#### 3.2.10 Notifications (CAP-NTF)

| Req ID | Requirement |
|--------|------------|
| CAP-NTF-001 | The system shall support per-user notification preferences for email and push channels |
| CAP-NTF-002 | The system shall support Web Push notifications via service workers |
| CAP-NTF-003 | The system shall support notification categories: news, events, comments, programs, announcements |
| CAP-NTF-004 | The system shall support notification archiving |

#### 3.2.11 Integrations (CAP-INT)

| Req ID | Requirement |
|--------|------------|
| CAP-INT-001 | The system shall support registration of third-party integration apps |
| CAP-INT-002 | The system shall provide API token authentication with rotation and revocation |
| CAP-INT-003 | The system shall provide OAuth2-like SSO with authorization codes |
| CAP-INT-004 | The system shall support webhook event subscriptions per integration |
| CAP-INT-005 | The system shall support navigation hints for registered integrations |
| CAP-INT-006 | The system shall log integration access events |

#### 3.2.12 Webhooks (CAP-WHK)

| Req ID | Requirement |
|--------|------------|
| CAP-WHK-001 | The system shall support webhook registration with URL and event subscriptions |
| CAP-WHK-002 | The system shall sign webhook payloads with HMAC secrets |
| CAP-WHK-003 | The system shall track webhook delivery status, response codes, and duration |
| CAP-WHK-004 | The system shall support 8 webhook event types: NewsArticlePublished, AnnouncementPublished, EventPublished, UserRegistered, UserRolesUpdated, ProfileUpdated, TicketPurchased, IntegrationAccessed |

#### 3.2.13 Games (CAP-GAM)

| Req ID | Requirement |
|--------|------------|
| CAP-GAM-001 | The system shall maintain a game catalog with descriptions |
| CAP-GAM-002 | The system shall support game modes with team sizes and parameters |

#### 3.2.14 User Management (CAP-USR)

| Req ID | Requirement |
|--------|------------|
| CAP-USR-001 | The system shall support user registration with email verification |
| CAP-USR-002 | The system shall support role-based access: Admin, Superadmin, SponsorManager |
| CAP-USR-003 | The system shall support two-factor authentication (TOTP) |
| CAP-USR-004 | The system shall support password reset via email |
| CAP-USR-005 | The system shall support user profile management (name, email, phone, address) |
| CAP-USR-009 | The system shall require a complete profile (address and at least one contact method) before allowing purchases |
| CAP-USR-006 | The system shall support ticket discovery settings (user visibility control) |
| CAP-USR-007 | The system shall support sidebar favorites for navigation customization |
| CAP-USR-008 | The system shall support appearance settings (theme preferences) |

### 3.3 System External Interface Requirements

#### 3.3.1 External Interfaces

| Interface | Type | Direction | Protocol |
|-----------|------|-----------|----------|
| Stripe API | Payment processing | Bidirectional | HTTPS/REST |
| S3/Minio Storage | File storage | Bidirectional | HTTPS/S3 Protocol |
| SMTP Server | Email delivery | Outbound | SMTP/TLS |
| Web Push Service | Push notifications | Outbound | HTTPS (RFC 8030) |
| Integration API | Third-party apps | Bidirectional | HTTPS/REST |
| Webhook Endpoints | Event notification | Outbound | HTTPS/POST |

See [IRS](IRS.md) for detailed interface requirements.

### 3.4 System Internal Interface Requirements

| Interface | Components | Protocol |
|-----------|-----------|----------|
| Application ↔ Database | Laravel ↔ PostgreSQL | TCP/SQL |
| Application ↔ Cache | Laravel ↔ Redis | TCP/RESP |
| Application ↔ Queue | Laravel ↔ Database/Redis | TCP |
| Web Server ↔ Application | FrankenPHP ↔ Laravel Octane | In-process |
| Frontend ↔ Backend | Vue.js ↔ Inertia.js | HTTPS/JSON |

### 3.5 System Internal Data Requirements

- Session data stored in database with configurable lifetime (120 minutes default)
- Cache stored in Redis with tag-based invalidation
- Queue jobs stored in database with batch tracking and failure logging
- Audit trail records maintained for all auditable entities
- File uploads stored in S3-compatible storage with signed URLs

### 3.6 Security and Privacy Requirements

| Req ID | Requirement |
|--------|------------|
| SEC-001 | All user passwords shall be hashed using bcrypt with configurable rounds (default: 12) |
| SEC-002 | The system shall support TOTP-based two-factor authentication with recovery codes |
| SEC-003 | All sessions shall be encrypted and stored server-side |
| SEC-004 | API tokens shall be hashed before storage |
| SEC-005 | Webhook payloads shall be signed with HMAC-SHA256 |
| SEC-006 | CSRF protection shall be enforced on all state-changing requests |
| SEC-007 | Authorization shall be enforced via Laravel Policies on all domain entities |
| SEC-008 | The system shall support role-based access control with 24 authorization policies |
| SEC-009 | Integration API routes shall use stateless Bearer token authentication |
| SEC-010 | The system shall track unique request IDs for audit and debugging |

### 3.7 System Environment Requirements

#### 3.7.1 Hardware Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 2 cores | 4+ cores |
| RAM | 2 GB | 4+ GB |
| Storage | 10 GB | 50+ GB (dependent on file uploads) |
| Network | 100 Mbps | 1 Gbps |

#### 3.7.2 Software Requirements

| Component | Requirement |
|-----------|------------|
| Container Runtime | Docker Engine 20+ |
| PHP | 8.5 |
| Database | PostgreSQL 15+ |
| Cache/Queue | Redis 7+ |
| Object Storage | S3-compatible (AWS S3, Minio, Garage) |
| HTTP Server | FrankenPHP (via Laravel Octane) |

### 3.8 Design and Implementation Constraints

| Constraint | Description |
|-----------|------------|
| CON-001 | Must be implemented using Laravel 13 framework |
| CON-002 | Frontend must use Vue.js 3 with Inertia.js v2 |
| CON-003 | Must run within Docker containers via Laravel Sail |
| CON-004 | Must follow domain-driven design with domains under `app/Domain/` |
| CON-005 | Must use Actions pattern for business logic (not in controllers) |
| CON-006 | Must use Eloquent ORM; raw queries only for complex operations |
| CON-007 | Must use Form Request classes for validation |
| CON-008 | Must support Laravel Octane for high-performance operation |

### 3.9 Software Quality Factors

| Factor | Requirement |
|--------|------------|
| Reliability | The system shall recover gracefully from failed queue jobs with retry mechanisms |
| Maintainability | Domain-driven architecture shall isolate business logic for independent evolution |
| Testability | All domains shall be testable via automated Pest tests |
| Portability | The system shall run on any Docker-capable host (Linux, macOS, Windows via WSL) |
| Scalability | The system shall support horizontal scaling via Octane worker processes |
| Auditability | The system shall maintain change audit trails for all critical entities |

---

## 4. Qualification Provisions

| Req Category | Verification Method |
|-------------|-------------------|
| Capability Requirements | Feature tests (Pest), E2E tests (Playwright) |
| Interface Requirements | Integration tests, manual verification |
| Security Requirements | Security audit, automated tests |
| Performance Requirements | Load testing, monitoring via Pulse/Prometheus |
| Environment Requirements | Docker deployment validation |

---

## 5. Requirements Traceability

Requirements in this document trace to:

- **Upstream:** Operational scenarios in [OCD](OCD.md)
- **Downstream:** Detailed CSCI requirements in [SRS](SRS.md)
- **Downstream:** Interface requirements in [IRS](IRS.md)
- **Downstream:** Test cases in [STD](STD.md)

---

## 6. Notes

### 6.1 Acronyms

| Term | Definition |
|------|-----------|
| CAP | Capability |
| CON | Constraint |
| SEC | Security |
| HMAC | Hash-based Message Authentication Code |
| RESP | Redis Serialization Protocol |
| CSRF | Cross-Site Request Forgery |
| TOTP | Time-based One-Time Password |
