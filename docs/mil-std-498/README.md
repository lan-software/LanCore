# MIL-STD-498 Documentation — LanCore

## Overview

This directory contains the software development and documentation artifacts for the **LanCore** project, structured in accordance with **MIL-STD-498** (Military Standard for Software Development and Documentation, 8 November 1994).

MIL-STD-498 defines 22 Data Item Descriptions (DIDs) that standardize the recording of software development and support processes. Each document below follows its respective DID section structure.

**Project:** LanCore — LAN Party & BYOD Event Management Platform
**Status:** Proof of Concept (pre-v1.0)
**Date:** 2026-04-02

### Author

| Role | Name |
|------|------|
| Project Lead | Markus Kohn |

---

## Document Index

### Plans

| DID | Document | Status |
|-----|----------|--------|
| [SDP](SDP.md) | Software Development Plan | Populated |
| [SIP](SIP.md) | Software Installation Plan | Scaffolded |
| [STrP](STrP.md) | Software Transition Plan | Scaffolded |

### Concept & Requirements

| DID | Document | Status |
|-----|----------|--------|
| [OCD](OCD.md) | Operational Concept Description | Populated |
| [SSS](SSS.md) | System/Subsystem Specification | Populated |
| [SRS](SRS.md) | Software Requirements Specification | Populated |
| [IRS](IRS.md) | Interface Requirements Specification | Populated |

### Design

| DID | Document | Status |
|-----|----------|--------|
| [SSDD](SSDD.md) | System/Subsystem Design Description | Scaffolded |
| [SDD](SDD.md) | Software Design Description | Populated |
| [DBDD](DBDD.md) | Database Design Description | Populated |
| [IDD](IDD.md) | Interface Design Description | Populated |

### Qualification Testing

| DID | Document | Status |
|-----|----------|--------|
| [STP](STP.md) | Software Test Plan | Populated |
| [STD](STD.md) | Software Test Description | Populated |
| [STR](STR.md) | Software Test Report | Populated |

### User & Operator Manuals

| DID | Document | Status |
|-----|----------|--------|
| [SUM](SUM.md) | Software User Manual | Populated |
| [SIOM](SIOM.md) | Software Input/Output Manual | Scaffolded |
| [SCOM](SCOM.md) | Software Center Operator Manual | Scaffolded |
| [COM](COM.md) | Computer Operation Manual | Scaffolded |

### Support Manuals

| DID | Document | Status |
|-----|----------|--------|
| [CPM](CPM.md) | Computer Programming Manual | Scaffolded |
| [FSM](FSM.md) | Firmware Support Manual | Scaffolded |

### Software Product Definition

| DID | Document | Status |
|-----|----------|--------|
| [SPS](SPS.md) | Software Product Specification | Populated |
| [SVD](SVD.md) | Software Version Description | Populated |

### Traceability

| DID | Document | Status |
|-----|----------|--------|
| [RTM](RTM.md) | Requirements Traceability Matrix | Populated |

---

## Recent Additions

- **2026-04-30** — Data Lifecycle / GDPR Article 17 feature domain. New requirement IDs `CAP-DL-001..008`, `SEC-DL-001..002`, `DL-F-001..018`. Touches OCD §5.7, SSS §3.2.Z, SRS §3.2.Z, IRS §5.X, SSDD §5.11, SDD §5.9, IDD §3.20, DBDD §5.X, STD §4.31, RTM §23. Article 17 scope statement in OCD §5.6 was lifted (formerly "out of scope"). Implementation domain: `app/Domain/DataLifecycle/`.

## Status Legend

- **Populated** — Document sections filled with current LanCore project data
- **Scaffolded** — MIL-STD-498 section structure in place with TBD markers; to be completed as the project matures

## References

- MIL-STD-498, Military Standard — Software Development and Documentation, 8 November 1994
- [VCTLabs MIL-STD-498 Templates](https://github.com/VCTLabs/MIL-STD-498)
