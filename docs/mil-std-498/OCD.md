# Operational Concept Description (OCD)

**Document Identifier:** LanCore-OCD-001
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

This Operational Concept Description (OCD) applies to the **LanCore** system — a LAN Party & BYOD Event Management Platform.

### 1.2 System Overview

LanCore is a self-hosted web application that enables LAN party organizers to plan, promote, and manage events. It handles the full event lifecycle from creation through ticket sales, check-in, scheduling, news publishing, and post-event activities.

### 1.3 Document Overview

This document describes the operational concept for LanCore, including the user classes, operational scenarios, and the system's role in the broader event management context.

---

## 2. Referenced Documents

- [SRS](SRS.md) — Software Requirements Specification
- [SSS](SSS.md) — System/Subsystem Specification
- [SUM](SUM.md) — Software User Manual

---

## 3. Current System or Situation

### 3.1 Background and Objectives

LAN party organizers currently rely on a combination of fragmented tools (spreadsheets, manual ticket tracking, separate CMS for news, third-party ticketing platforms) or legacy software (eventula-manager) that has become difficult to maintain.

LanCore aims to provide a single, integrated platform that:

- Eliminates dependency on multiple disconnected tools
- Provides a modern, responsive user experience
- Supports self-hosting for data sovereignty
- Enables extensibility through integrations and webhooks

### 3.2 Operational Policies and Constraints

- The system must be self-hostable on commodity hardware or cloud infrastructure
- User data must remain under the control of the event organizer
- The system must support both online (Stripe) and on-site payment methods
- The system must be accessible on mobile devices

---

## 4. Justification for and Nature of Changes

### 4.1 Justification

- Legacy eventula-manager is built on outdated technology and is difficult to extend
- Modern LAN events require real-time features (push notifications, live announcements)
- Integration with companion apps (tournament brackets, check-in, food ordering) requires a robust API
- Domain-driven architecture enables parallel development of isolated features

### 4.2 Description of Desired Changes

LanCore is a ground-up rewrite providing:

- Domain-driven architecture for maintainability
- Real-time notifications via Web Push
- Webhook-driven integration ecosystem
- Canvas-based seating plan editor
- Flexible ticketing with quotas, categories, groups, and add-ons
- Achievement/badge system for attendee engagement

---

## 5. Concepts for the Proposed System

### 5.1 User Classes and Roles

#### 5.1.1 Attendee (Unauthenticated Visitor)

- Browse published events and news
- View public announcements
- Register an account

#### 5.1.2 Registered User (Authenticated)

- Purchase tickets for events
- View and manage their own tickets
- Manage profile and security settings (including 2FA)
- Customize notification preferences (email, web push)
- Comment on and vote on news articles
- Dismiss announcements
- View earned achievements
- Subscribe to program schedule notifications
- Configure ticket discovery (visibility to other users)

#### 5.1.3 Sponsor Manager

- All Registered User capabilities
- Manage assigned sponsor profiles
- View sponsorship assignments

#### 5.1.4 Admin

- All Registered User capabilities
- Create, edit, publish, and unpublish events
- Manage venues and addresses
- Create and manage ticket types, categories, groups, and add-ons
- Configure seating plans
- Create and manage programs and time slots
- Publish news articles and announcements
- Manage sponsors and sponsor levels
- Define achievements and grant them to users
- Configure webhooks and integration apps
- Manage orders and vouchers
- View audit trails for all entities
- Manage users and assign roles
- Configure purchase requirements and checkout conditions

#### 5.1.5 Superadmin

- All Admin capabilities
- Full system access with no policy restrictions
- User role management including admin promotion

### 5.2 Operational Scenarios

#### 5.2.1 Event Creation and Publication

1. Admin creates a new event with name, dates, and description
2. Admin assigns or creates a venue with address and images
3. Admin creates a primary program with time slots
4. Admin configures ticket types with pricing and quotas
5. Admin optionally creates a seating plan
6. Admin assigns sponsors to the event
7. Admin publishes the event — triggers webhook notifications to integrations
8. Registered users can now browse and purchase tickets

#### 5.2.2 Ticket Purchase Flow

1. User browses published event and available ticket types
2. User adds tickets and optional add-ons to cart
3. System validates quotas, per-user limits, and availability
4. User proceeds to checkout, acknowledges purchase requirements
5. User selects payment method (Stripe or on-site)
6. For Stripe: redirected to Stripe Checkout, then back on success
7. For on-site: order marked pending, fulfilled manually by admin
8. System generates ticket with unique validation ID
9. Ticket purchase event triggers webhooks and notifications

#### 5.2.2a Group Ticket Purchase and Assignment

1. Admin creates a group ticket type with max users per ticket (e.g., 4) and a check-in mode (individual or group)
2. User purchases a group ticket — seat capacity is reserved based on `seats_per_user × max_users_per_ticket`
3. Purchaser (owner) can assign additional users to the ticket up to the configured limit
4. Each assigned user appears in the ticket's user list with their own check-in status
5. Owner or manager can add/remove assigned users before the event
6. At check-in:
   - **Individual mode:** Each user checks in independently; the ticket is fully checked in when all users have checked in
   - **Group mode:** A single check-in action marks all assigned users as checked in simultaneously
7. Companion apps (LanEntrance) can query per-user check-in status via the Integration API

#### 5.2.3 News Publishing

1. Admin creates a news article with rich text content (TipTap editor)
2. Admin sets visibility (public, members-only, draft)
3. Admin publishes the article
4. System sends notifications to users with matching preferences (email, push)
5. System dispatches webhooks to subscribed integration apps
6. Users can comment on articles and vote on comments

#### 5.2.4 Real-Time Announcements

1. Admin creates an announcement with priority level (low, normal, high, critical)
2. Admin targets announcement to a specific event or all users
3. System delivers push notifications to subscribed users
4. System dispatches webhooks to subscribed integrations
5. Users can dismiss announcements; dismissals are tracked per-user

#### 5.2.5 Check-In at Event

1. Attendee arrives at venue with ticket validation ID
2. Door staff uses companion app (LanEntrance) connected via Integration API
3. Companion app validates ticket via API and marks as checked-in
4. System updates ticket status from "active" to "checked_in"
5. For group tickets with individual check-in: each user checks in separately; ticket status becomes "checked_in" when all users have checked in
6. For group tickets with group check-in: a single scan checks in all assigned users simultaneously

#### 5.2.6 Third-Party Integration

1. Admin registers an integration app with name, callback URL, and webhook subscriptions
2. System generates API tokens for the integration
3. Integration authenticates via Bearer token on stateless API routes
4. Integration receives webhook events for subscribed event types
5. Integration can initiate SSO flow for user authentication
6. Integration appears in navigation for users who have authorized it

#### 5.2.7 Achievement Tracking

1. Admin defines achievements with criteria (grantable events)
2. System listens for qualifying events (ticket purchase, profile update, etc.)
3. When criteria met, system grants achievement to user
4. User receives notification of earned achievement
5. User can view their achievements in settings

### 5.3 System Context

```
                    +-------------------+
                    |   Web Browser     |
                    |  (Attendee/Admin) |
                    +--------+----------+
                             |
                    +--------v----------+
                    |    LanCore        |
                    |  (Laravel/Vue)    |
                    +--+----+----+------+
                       |    |    |
          +------------+    |    +-------------+
          |                 |                  |
+---------v---+   +---------v---+   +----------v--------+
| PostgreSQL  |   |    Redis    |   |  S3/Minio Storage  |
| (Database)  |   | (Cache/     |   |  (Files/Images)    |
+-------------+   |  Sessions)  |   +-------------------+
                  +-------------+
                             |
                    +--------v----------+
                    | External Services |
                    | - Stripe API      |
                    | - SMTP Mail       |
                    | - Web Push        |
                    | - Integration Apps|
                    +-------------------+
```

### 5.4 Modes of Operation

| Mode | Description |
|------|-------------|
| Normal Operation | All services running, full feature set available |
| Maintenance Mode | Application returns maintenance page; admin can still access via allowed IPs |
| Development Mode | Debug toolbar, Telescope, detailed error pages enabled |
| Queue Processing | Horizon manages background jobs (webhooks, notifications, email) |

---

## 6. Operational and Organizational Impacts

### 6.1 Operational Impacts

- Event organizers consolidate from multiple tools to a single platform
- Real-time notifications reduce the need for manual attendee communication
- Integration API enables ecosystem of companion apps
- Self-hosting ensures data sovereignty and reduces recurring SaaS costs

### 6.2 Organizational Impacts

- Organizers need basic Docker/server administration skills for self-hosting
- Staff roles map to application roles (Admin, Sponsor Manager)
- Companion app developers interact via documented Integration API

---

## 7. Notes

### 7.1 Glossary

| Term | Definition |
|------|-----------|
| LAN Party | A social gathering where participants bring personal computers to play multiplayer games together on a local network |
| BYOD | Bring Your Own Device — events where attendees bring their own computing equipment |
| Seating Plan | A visual layout of physical seat positions at a venue |
| Voucher | A discount code that can be applied during ticket checkout |
| Add-on | An additional purchasable item attached to a ticket (e.g., t-shirt, meal) |
| Group Ticket | A ticket that allows multiple users to gain entry, purchased by one owner and assignable to multiple attendees |
| Webhook | An HTTP callback that delivers event notifications to external systems |
| Integration App | A third-party application registered with LanCore for API access and SSO |
