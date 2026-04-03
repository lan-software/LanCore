# Software User Manual (SUM)

**Document Identifier:** LanCore-SUM-001
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

This Software User Manual (SUM) provides instructions for end users of the **LanCore** system — a LAN Party & BYOD Event Management Platform.

### 1.2 System Overview

LanCore is a web-based application accessible via modern web browsers. It serves two primary user groups: **attendees** (event participants) and **administrators** (event organizers).

---

## 2. Referenced Documents

- [OCD](OCD.md) — Operational Concept Description
- [SRS](SRS.md) — Software Requirements Specification

---

## 3. Software Summary

### 3.1 Purpose

LanCore enables LAN party and BYOD event organizers to manage all aspects of their events through a single platform, and enables attendees to discover events, purchase tickets, and participate in the event community.

### 3.2 Capabilities

| Feature | Description |
|---------|-------------|
| Event Browsing | View published events with details, dates, and venues |
| Ticket Purchase | Buy tickets via Stripe or register for on-site payment |
| User Profile | Manage personal information, password, and 2FA |
| Notifications | Receive email and push notifications for news and announcements |
| News | Read articles, comment, and vote on comments |
| Announcements | View real-time event announcements |
| Achievements | Track and view earned badges |
| Programs | View event schedules and subscribe to time slot reminders |

### 3.3 System Requirements

| Requirement | Specification |
|-------------|--------------|
| Browser | Chrome 90+, Firefox 90+, Safari 15+, Edge 90+ |
| JavaScript | Must be enabled |
| Network | Internet connection required |
| Screen | Responsive design supports mobile, tablet, and desktop |

---

## 4. Getting Started

### 4.1 Accessing the Application

Open your web browser and navigate to the LanCore instance URL provided by your event organizer.

### 4.2 Creating an Account

1. Click **Register** on the welcome page
2. Enter your name, email address, and choose a password (minimum 8 characters)
3. Click **Register**
4. Check your email for a verification link
5. Click the verification link to activate your account

### 4.3 Logging In

1. Click **Log in** on the welcome page
2. Enter your email and password
3. If two-factor authentication is enabled, enter the 6-digit code from your authenticator app
4. Click **Log in**

### 4.4 Forgot Password

1. Click **Forgot your password?** on the login page
2. Enter your email address
3. Click **Email Password Reset Link**
4. Check your email and click the reset link
5. Enter and confirm your new password

---

## 5. Usage Procedures

### 5.1 Browsing Events

1. Navigate to the **Events** section from the welcome page
2. View the list of upcoming published events
3. Click on an event to see details including:
   - Event dates and description
   - Venue information with address
   - Available ticket types and pricing
   - Event program/schedule

### 5.2 Purchasing Tickets

1. Navigate to an event's **Shop** page
2. Browse available ticket types and add-ons
3. Select desired quantities and click **Add to Cart**
4. Review your cart
5. Apply a voucher code if you have one
6. Acknowledge any purchase requirements (terms, conditions)
7. Choose a payment method:
   - **Stripe:** Redirects to secure Stripe Checkout page
   - **On-site:** Reserves tickets for payment at the event venue
8. Complete payment
9. View your tickets under **My Tickets**

### 5.3 Managing Tickets

1. Navigate to **Tickets** in the sidebar
2. View all your purchased tickets with:
   - Ticket type and event
   - Status (active, checked-in)
   - Unique validation ID (for check-in)
3. Tickets with add-ons show purchased extras

#### 5.3.1 Group Tickets

Group tickets allow a single ticket to grant access to multiple users:

1. View a group ticket to see the list of assigned users and their check-in status
2. As the ticket owner or manager, click **Add User** to assign additional users (up to the ticket's limit)
3. Enter the user's email address to assign them
4. Click **Remove** next to a user to unassign them
5. Each assigned user can view the ticket in their own **Tickets** page under "Assigned Tickets"
6. At check-in:
   - **Individual mode:** Each user checks in separately with the ticket's validation ID
   - **Group mode:** All assigned users are checked in together in a single scan

### 5.4 Reading News

1. Navigate to **News** from the welcome page or dashboard
2. Browse published articles
3. Click an article to read the full content
4. Scroll down to the comments section to:
   - Write a comment
   - Upvote or downvote existing comments

### 5.5 Viewing Announcements

1. Announcements appear as banners when you log in
2. Priority levels indicate urgency:
   - **Low** — Informational
   - **Normal** — Standard update
   - **High** — Important notice
   - **Critical** — Urgent action required
3. Click **Dismiss** to hide an announcement
4. View all announcements in the **Announcements** section

### 5.6 Viewing Programs

1. Navigate to the event's **Program** section
2. View the schedule with time slots
3. Click the bell icon to subscribe to a time slot notification
4. You will receive a reminder before the time slot begins

### 5.7 Viewing Achievements

1. Navigate to **Settings > Achievements**
2. View all available achievements and your progress
3. Earned achievements show the date they were unlocked

### 5.8 Managing Your Profile

#### 5.8.1 Update Profile Information

1. Navigate to **Settings > Profile**
2. Update your name or email address
3. Fill in your address (street, city, ZIP code, country) and phone number
4. Click **Save**
5. If you changed your email, you will need to verify the new address

> **Note:** A complete profile (address and at least one contact method) is required before you can add items to your shopping cart.

#### 5.8.2 Change Password

1. Navigate to **Settings > Security**
2. Enter your current password
3. Enter and confirm your new password
4. Click **Update Password**

#### 5.8.3 Enable Two-Factor Authentication

1. Navigate to **Settings > Security**
2. Click **Enable Two-Factor Authentication**
3. Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.)
4. Enter the 6-digit code from your app to confirm
5. Save your recovery codes in a secure location

#### 5.8.4 Notification Preferences

1. Navigate to **Settings > Notifications**
2. Toggle email and push notifications for each category:
   - News articles
   - Events
   - Comments on your posts
   - Program schedule
   - Announcements
3. Click **Save**

#### 5.8.5 Enable Push Notifications

1. Navigate to **Settings > Notifications**
2. Click **Enable Push Notifications**
3. Allow the browser notification permission when prompted
4. You will now receive push notifications for enabled categories

#### 5.8.6 Ticket Discovery

1. Navigate to **Settings > Ticket Discovery**
2. Choose whether other users can find you by name
3. Optionally set a custom display name for discovery
4. Click **Save**

#### 5.8.7 Appearance

1. Navigate to **Settings > Appearance**
2. Choose your preferred theme (light, dark, or system)
3. Changes apply immediately

### 5.9 Sidebar Navigation

1. The sidebar shows quick links to your most-used sections
2. Click the star icon next to any page to add it to your favorites
3. Favorites appear at the top of the sidebar for quick access

---

## 6. Administrator Guide

### 6.1 Event Management

1. Navigate to **Events > Create**
2. Fill in event details: name, description, start date, end date
3. Upload banner images
4. Save as draft to continue editing later
5. When ready, click **Publish** to make the event visible

### 6.2 Venue Management

1. Navigate to **Venues > Create**
2. Enter venue name, description
3. Add address details (street, city, state, country)
4. Upload venue images with alt text
5. Assign the venue to an event

### 6.3 Ticket Type Configuration

1. Navigate to **Ticket Types > Create** within an event context
2. Configure: name, price, quota, category, group
3. Optionally set: max per user, sale start/end dates, seating flag
4. Create add-ons for optional extras

#### 6.3.1 Configuring Group Tickets

1. Set **Max Users per Ticket** to a value greater than 1 (e.g., 4 for a 4-person group ticket)
2. When max users > 1, a **Check-in Mode** dropdown appears:
   - **Individual:** Each assigned user checks in separately
   - **Group:** All assigned users are checked in with a single scan
3. Seat capacity is calculated as `seats per ticket × max users per ticket` — for example, 2 seats × 4 users = 8 seats reserved per ticket sold
4. Once tickets have been sold (ticket type is locked), group settings cannot be changed

### 6.4 Program Scheduling

1. Navigate to **Programs > Create** within an event context
2. Set program name, description, and visibility
3. Add time slots with start/end times and sort order

### 6.5 News Publishing

1. Navigate to **News > Create**
2. Write content using the rich text editor
3. Set visibility (public, members only, or draft)
4. Optionally configure SEO title and description
5. Enable "Notify Users" to send notifications on publish
6. Click **Publish**

### 6.6 Webhook Configuration

1. Navigate to **Webhooks > Create**
2. Enter the delivery URL
3. Select event types to subscribe to
4. A signing secret is generated automatically
5. Share the secret with the receiving application for signature verification

### 6.7 Integration App Management

1. Navigate to **Integrations > Create**
2. Enter app name, description, and callback URL
3. Configure webhook event subscriptions
4. Generate API tokens for the integration
5. Share tokens securely with the integration developer

### 6.8 User Management

1. Navigate to **Users**
2. Search for users by name or email
3. Click a user to view their profile
4. Assign or remove roles: Admin, Superadmin, SponsorManager

---

## 7. Troubleshooting

| Problem | Solution |
|---------|----------|
| Cannot log in | Verify email and password; check for 2FA requirement |
| Email not received | Check spam/junk folder; contact organizer |
| Push notifications not working | Ensure browser permissions are granted; re-subscribe in settings |
| Page not loading | Clear browser cache; try a different browser |
| Payment failed | Check card details; try a different payment method |
| Ticket not showing | Refresh the page; check under My Tickets |

---

## 8. General Information

### 8.1 Glossary

| Term | Definition |
|------|-----------|
| Add-on | An optional extra item purchasable with a ticket |
| Group Ticket | A ticket type that allows multiple users to be assigned, purchased by one owner |
| Voucher | A discount code for ticket purchases |
| 2FA | Two-Factor Authentication using a time-based code |
| Push Notification | A browser notification delivered even when the app is not open |
| Seat Plan | A visual layout of physical seating positions |
| Validation ID | A unique code on each ticket used for event check-in |
