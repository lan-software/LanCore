# Proof of Concept – LSCore

LSCore is in a very early state and is therefore considered a Proof of Concept (PoC).  
This document defines the scope of the PoC, provides background context and focuses on requirements and architectural direction.

---

## Vision

LSCore is created out of the need to move away from a dated and increasingly hard-to-maintain system.

The previous system (eventula-manager) accumulated architectural limitations that made further development inefficient and error-prone. Instead of continuing to patch these issues, LSCore represents a fresh start with a strong focus on:

- clear domain separation  
- maintainable architecture  
- modern development workflows  
- long-term extensibility  

LSCore is not a rewrite of eventula-manager, but a **successor in terms of ideas and experience**.

---

## Scope

The LSCore PoC is primarily an **architectural and technical demonstration**.

It aims to show:
- how to structure a Laravel application using domain-driven concepts  
- how to separate concerns between public, user and admin functionality  
- how to model core LAN event features in a clean and extensible way  
- how to build a maintainable foundation for future development  

The PoC is **not intended to be feature-complete** and will likely never evolve into a full product in its current form.

### In Scope

- Public pages (Landing, About, Event Overview)  
- User authentication (register, login)  
- Basic role system (user, admin)  
- Event management (create, edit, publish)  
- Venue management  
- Ticket type management  
- Ticket assignment / reservation  
- Canvas-based seating plan  
- Seat assignment with category validation  

### Out of Scope

- Payment integration  
- Checkout and invoicing  
- Email/notification systems  
- Check-in / badge systems  
- Full tournament management  
- Game server management  
- Multi-tenancy  
- Plugin system  
- Advanced permission systems  
- Complex rule engines  

---

## Personas

LSCore defines four primary personas that interact with the system. These personas represent the core user types for the PoC and will be extended in the future if needed.

### Guest

A non-authenticated visitor of the platform.

**Goals:**
- understand what the organization does  
- discover upcoming events  
- evaluate whether participation is interesting  

**Capabilities:**
- view landing page  
- view public event information  
- view ticket options  
- view seating plan (read-only)  

---

### User (Attendee)

A registered user who participates in events.

**Goals:**
- attend an event  
- obtain a ticket  
- select a seat  

**Capabilities:**
- register and log in  
- view events  
- view assigned tickets  
- select or view seating  

---

### Admin (Organizer)

A user responsible for organizing events.

**Goals:**
- plan and manage events  
- define ticket structures  
- manage seating and venues  

**Capabilities:**
- create and manage events  
- create venues  
- create ticket types  
- create seating plans  
- assign tickets and seats  

---

### Superadmin (Platform Operator)

A user with full control over the system.

**Goals:**
- manage platform access  
- ensure system operability  

**Capabilities:**
- manage users and roles  
- full access to all resources  

---

## Domains

To keep LSCore maintainable and extensible, the system is divided into **domain-based modules**.  
Each domain encapsulates a specific part of the business logic.

### Access
Handles authentication and authorization.

- Users  
- Roles  

---

### Public
Handles all publicly visible content.

- Landing page  
- About page  
- Public event views  

---

### Event
Represents a concrete LAN event.

- Event lifecycle (draft, published)  
- Date and time  
- Description  
- Venue reference  

---

### Venue
Represents physical locations.

- Address  
- Description  
- Metadata about location  

---

### Ticketing
Handles participation and access control.

- Ticket types  
- Tickets  
- Pricing  
- Quantity limits  
- Category restrictions  

---

### Seating
Handles spatial planning and seat allocation.

- Seating plans (canvas-based)  
- Seats and objects  
- Seat categories  
- Seat assignments  

---

### Program (prepared, not fully implemented in PoC)
Handles scheduling.

- Timetables  
- Time slots  

---

### Gaming / Tournaments (prepared, not fully implemented in PoC)
Handles LAN-specific activities.

- Games  
- Tournaments  
- Tournament structures  

---

### Sponsoring (prepared)
Handles sponsors and partnerships.

- Sponsors  
- Sponsor assignments  

---

## Functional Requirements

### Access

- The system must allow users to register and log in  
- The system must support at least two roles: user and admin  

---

### Public

- The system must provide a landing page  
- The system must provide an about page  
- The system must display published events  
- The system must highlight the next upcoming event  
- The system must display ticket types and prices  
- The system must provide a read-only seating preview  

---

### Event

- Admins must be able to create and edit events  
- Events must support at least `draft` and `published` states  
- Events must include:
  - title  
  - description  
  - start date  
  - end date  
  - venue reference  

---

### Venue

- Admins must be able to create and edit venues  
- Venues must support address and descriptive information  

---

### Ticketing

- Admins must be able to create ticket types  
- Ticket types must include:
  - name  
  - price  
  - quantity  
- Tickets must be assignable to users  
- Ticket types must be restrictable to seat categories  

---

### Seating

- Admins must be able to create seating plans  
- Seating plans must support free placement (canvas)  
- The system must support:
  - seats  
  - non-seat objects  
- Seats must support categories  
- Seats must be assignable to users or tickets  
- A seat must not be assigned more than once  
- Seat assignment must respect ticket category restrictions  

---

## Non-Functional Requirements

### Maintainability
The system must be structured by domain to keep the codebase understandable and maintainable.

---

### Separation of Concerns
Public, authenticated user and admin functionality must be clearly separated.

---

### Testability
Core business logic must be testable and covered by automated tests.

---

### Framework Leverage
The system should prefer Laravel core features and well-supported Laravel ecosystem packages wherever they provide a suitable, maintainable and well-integrated solution.

Custom implementations should only be introduced when:
- no suitable Laravel-native solution exists
- the package would introduce unnecessary complexity or lock-in
- business requirements cannot be met otherwise

---

### Simplicity
The PoC must favor simplicity over completeness.

---

### Extensibility
The architecture must allow adding new domains (e.g. tournaments, sponsors) without major refactoring.

---

### Security
Administrative functionality must be restricted to authorized users.

---

### Usability
The PoC must provide a usable end-to-end flow:
Guest → User → Ticket → Seat

---

## Acceptance Criteria

The PoC is considered successful if the following conditions are met:

- A guest can view a landing page and an upcoming event  
- A user can register and log in  
- An admin can create and publish an event  
- An admin can create a venue and assign it to an event  
- An admin can create ticket types  
- An admin can create a seating plan with freely placed seats  
- A user can view a seating plan  
- A ticket can be assigned to a user  
- A seat can be assigned exactly once  
- Seat assignment respects ticket category restrictions  
- Core flows are covered by automated tests  