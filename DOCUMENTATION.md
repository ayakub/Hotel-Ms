# Hotel Reservation Management System
### University Semester Final Project — Documentation

---

## 1. Introduction
The Hotel Reservation Management System is a web-based application that automates a hotel's
day-to-day operations: guest registration, room and room-type management, availability
checking, multi-room bookings, check-in/check-out, payments, additional services, invoicing,
and role-based staff access.

## 2. Problem Statement
Manual, paper/spreadsheet-based hotel operations are slow and error-prone: double bookings,
lost guest records, inconsistent pricing, and manual, error-prone bill calculation.

## 3. Objectives
- Automate booking, check-in/check-out, and billing.
- Prevent double-booking of a room for overlapping date ranges.
- Provide role-based access (Admin, Receptionist, Housekeeping).
- Generate accurate, itemised invoices automatically.
- Provide management reports (revenue, occupancy, service usage).

## 4. Scope
In scope: guest, room, room-type, staff, booking, payment, service management; reporting;
authentication. Out of scope: online payment gateway integration, channel-manager/OTA sync
(can be listed as future work).

## 5. Existing System vs Proposed System
**Existing (manual) system:** registers kept on paper/Excel, manual availability checks,
manual bill calculation, no audit trail, no role separation.
**Proposed system:** centralised MySQL database, automatic overlap-based availability check,
automatic bill/invoice calculation, session-based role access, full audit trail via timestamps.

---

## 6. Functional Requirements
| # | Requirement |
|---|---|
| FR1 | System shall authenticate users via username/password (hashed with bcrypt). |
| FR2 | System shall restrict pages by role (Admin / Receptionist / Housekeeping). |
| FR3 | Receptionist shall create/update/cancel bookings for one or more rooms. |
| FR4 | System shall prevent booking a room for overlapping date ranges. |
| FR5 | System shall record payments and compute paid/due automatically. |
| FR6 | System shall let receptionist attach services to a booking. |
| FR7 | System shall generate a printable invoice per booking. |
| FR8 | Admin shall manage staff, users, room types, and services. |
| FR9 | Housekeeping shall update room status (Cleaning/Maintenance/Available). |
| FR10 | Admin shall view revenue, booking, occupancy, and service-usage reports. |

## 7. Non-Functional Requirements
- **Security:** bcrypt password hashing, PDO prepared statements (SQL-injection safe),
  server-side input validation, session-based auth, role checks on every protected page.
- **Usability:** responsive sidebar dashboard layout, clear status badges, confirmation
  prompts on destructive actions.
- **Maintainability:** one responsibility per PHP file, shared `functions.php` for business
  logic, shared header/sidebar/footer includes.
- **Portability:** runs on any standard LAMP/XAMPP/WAMP stack (PHP 8+, MySQL 8+).

---

## 8. Use Case Specification

**Actors:** Admin, Receptionist, Housekeeping, Guest (indirect — served by Receptionist).

| Use case | Primary actor | Description |
|---|---|---|
| Login / Logout | All | Authenticate with username & password |
| Manage Guests | Receptionist, Admin | Add/edit/delete/search guest records |
| Check Room Availability | Receptionist | Search free rooms by date range & type |
| Create Booking | Receptionist | Select guest + rooms, create reservation |
| Cancel Booking | Receptionist | Cancel a Pending/Confirmed booking |
| Check-in Guest | Receptionist | Mark booking Checked-In, rooms → Occupied |
| Check-out Guest | Receptionist | Mark booking Checked-Out, rooms → Cleaning |
| Record Payment | Receptionist | Add a payment against a booking's due amount |
| Add Service to Booking | Receptionist | Attach a hotel service (breakfast, laundry, ...) |
| Generate Invoice | Receptionist | View/print itemised bill |
| Manage Rooms | Admin | Add rooms, assign room type |
| Update Room Status | Housekeeping | Available / Cleaning / Maintenance |
| Manage Room Types | Admin | Add/edit/delete room categories & pricing |
| Manage Staff | Admin | Add staff records |
| Manage User Accounts | Admin | Create login accounts linked to staff, assign role |
| Manage Services | Admin | Add/delete hotel services and pricing |
| View Reports | Admin | Revenue, booking, occupancy, service-usage reports |

**Use-case relationship notes:** "Create Booking" includes "Check Room Availability".
"Check-out" and "Cancel Booking" both release rooms back to availability (different states:
Cleaning vs Available). "Record Payment" and "Add Service" both extend "Generate Invoice"
since they change the computed bill.

---

## 9. EER Diagram Specification
(See the rendered ER diagram delivered separately in the chat, and the relational schema below.)

**Strong entities:** Guest, Staff, RoomType, Room, Booking, Payment, Service, Users.
**Weak/junction (identifying) entities:** BookingRoom (PK = BookingID+RoomID),
BookingService (PK = BookingID+ServiceID) — both are weak because their identity depends
entirely on the two entities they connect.

**Relationships & cardinality:**
- Guest **(1)** — makes — **(N)** Booking
- Staff **(1)** — handles — **(N)** Booking
- RoomType **(1)** — categorizes — **(N)** Room
- Booking **(N)** — includes — **(N)** Room, resolved via BookingRoom
- Booking **(1)** — receives — **(N)** Payment
- Booking **(N)** — uses — **(N)** Service, resolved via BookingService
- Staff **(1)** — logs in as — **(0..1)** Users (optional, partial participation)

**EER specialization (optional, shown via `Position` attribute in implementation instead of
separate tables, to keep the schema simple):**
```
Staff (generalization)
 ├── Manager        (disjoint, total specialization on Position)
 ├── Receptionist
 └── Housekeeping
```
Discriminator attribute: `Staff.Position`. This is implemented as an ENUM column rather than
three subclass tables — a common, defensible simplification for a student project; the report
should explain that a full EER implementation would instead use one table per subtype with a
shared `StaffID` foreign key to a base `Staff` table (total, disjoint specialization).

**Participation constraints:**
- Total participation: every Booking must have a Guest (mandatory FK), every BookingRoom row
  requires an existing Booking and Room.
- Partial participation: not every Room has an active Booking at a given time; not every
  Staff member has a Users login account.

---

## 10. Relational Schema (mapped from EER)

```
Staff(StaffID PK, Name, Position, Phone, Salary, HireDate)

Users(UserID PK, Username, PasswordHash, Role, StaffID FK -> Staff)

Guest(GuestID PK, Name, Phone, Email, Address, NID)

RoomType(RoomTypeID PK, TypeName, PricePerNight, Capacity, Description)

Room(RoomID PK, RoomNumber, FloorNo, Status, RoomTypeID FK -> RoomType)

Booking(BookingID PK, GuestID FK -> Guest, StaffID FK -> Staff,
        BookingDate, CheckInDate, CheckOutDate, ActualCheckIn, ActualCheckOut, Status)

BookingRoom(BookingID FK -> Booking, RoomID FK -> Room, PricePerNight,
            PRIMARY KEY(BookingID, RoomID))

Payment(PaymentID PK, BookingID FK -> Booking, Amount, PaymentDate,
        PaymentMethod, PaymentStatus, TransactionID)

Service(ServiceID PK, ServiceName, Price)

BookingService(BookingID FK -> Booking, ServiceID FK -> Service, Quantity,
               PRIMARY KEY(BookingID, ServiceID))
```

Mapping rules applied:
1. Every strong entity → its own table, entity's simple attributes → columns.
2. 1:N relationship (Guest–Booking, Staff–Booking, RoomType–Room, Booking–Payment) → FK
   placed on the "many" side.
3. M:N relationship (Booking–Room, Booking–Service) → new junction table with a composite
   primary key made of the two participating FKs.
4. `BookingRoom.PricePerNight` is a **relationship attribute** (the nightly rate *at the time
   of booking*), stored on the junction table rather than looked up live from RoomType — this
   is a deliberate normalization trade-off explained below.

---

## 11. Normalization

**1NF (atomic values, no repeating groups):**
All columns hold a single atomic value (e.g. `Phone` is one string, not a list of numbers).
No table has a repeating group of columns (e.g. Room1, Room2, Room3 inside Booking) — instead
multiple rooms per booking are modeled as multiple rows in BookingRoom. ✅ Satisfied.

**2NF (no partial dependency on a composite key):**
Only composite-key tables need checking: BookingRoom(BookingID, RoomID) and
BookingService(BookingID, ServiceID).
- In BookingRoom, `PricePerNight` depends on the *combination* (which room, in which booking
  it was priced) — not on BookingID alone or RoomID alone — so it is fully, not partially,
  dependent on the whole key. ✅ Satisfied.
- In BookingService, `Quantity` similarly depends on the full (BookingID, ServiceID) pair.
  ✅ Satisfied.

**3NF (no transitive dependency):**
- In `Room`, `RoomTypeID` is a foreign key; the type's name/price/capacity are **not**
  duplicated into Room — they live only in `RoomType` and are reached via join. This removes
  the transitive dependency Room → RoomTypeID → PricePerNight. ✅ Satisfied.
- In `Booking`, Guest details (Name/Phone/Email) are **not** duplicated — only `GuestID` is
  stored, details live in `Guest`. ✅ Satisfied.
- `BookingRoom.PricePerNight` is a deliberate, documented exception: it is not "transitively
  dependent" on RoomID because it represents a historical snapshot (the rate this booking was
  charged), which can legitimately differ from RoomType's *current* price if prices change
  later. This is standard practice for order/line-item tables (compare to an e-commerce
  order storing the price paid, not a live lookup) and should be defended as such in the viva.

**Conclusion:** the schema is normalized to 3NF, with one intentional, justified denormalized
snapshot column for billing integrity.

---

## 12. System Architecture
```
Browser (HTML/CSS/JS)
      |
      v
PHP application (MVC-ish, procedural)
  includes/   -> shared layout + business-logic helpers (functions.php)
  auth/       -> login/logout, session-based authentication
  guests/, rooms/, roomtypes/, staff/, users/, services/ -> CRUD modules
  bookings/   -> availability search, booking creation, check-in/out, payments, services
  invoice/    -> printable invoice generator
  reports/    -> admin reporting
      |
      v
MySQL (PDO, prepared statements) -> hotel_reservation database
```
Each page: (1) checks role via `require_role()`, (2) handles POST actions with prepared
statements, (3) queries data, (4) renders shared header/sidebar + page content + footer.

---

## 13. Implementation Notes
- **Auth:** `password_hash()` / `password_verify()`, PHP `$_SESSION`. See
  `database/generate_password.php` to generate real bcrypt hashes for the seed `Users` rows
  (the SQL file ships with placeholder hashes that must be replaced before first login).
- **SQL injection defense:** every query uses PDO prepared statements with bound parameters —
  no string-concatenated SQL anywhere in the codebase.
- **Availability logic:** `available_rooms()` in `includes/functions.php` excludes any room
  that has an active (`Pending`/`Confirmed`/`Checked-In`) booking whose date range overlaps
  the requested range, using the standard interval-overlap condition
  `existing.CheckIn < new.CheckOut AND existing.CheckOut > new.CheckIn`.
- **Booking creation** re-validates availability inside a transaction immediately before
  insert, to reduce (not eliminate, without table locking/`SELECT ... FOR UPDATE`) race
  conditions between two receptionists booking the same room simultaneously — a good viva
  talking point on limitations.
- **Billing:** `booking_bill()` computes nights × room price + Σ(service qty × price), and
  paid/due from the `Payment` table — single source of truth used by both the booking page and
  the invoice.

## 14. Setup Instructions
1. Copy the `hotel-reservation/` folder into your XAMPP/WAMP `htdocs/` directory.
2. Start Apache + MySQL.
3. Open phpMyAdmin → SQL tab → run `database/schema.sql`.
4. Run `php database/generate_password.php password123` (or open it in a browser) to get a
   real bcrypt hash, then in phpMyAdmin run:
   `UPDATE Users SET PasswordHash='<hash>' WHERE Username IN ('admin','rafiq','sultana');`
5. Visit `http://localhost/hotel-reservation/` and log in (e.g. `admin` / `password123`).
6. If deployed under a different folder name, update `base_url()` in `includes/functions.php`.

---

## 15. Testing Plan

| Test case | Steps | Expected result |
|---|---|---|
| Login validation | Enter wrong password | "Invalid username or password" shown, no session created |
| Role restriction | Log in as Housekeeping, visit `/staff/list.php` directly | 403 access-denied page |
| Guest registration | Add guest with only Name filled | Guest saved; optional fields blank |
| Room creation | Admin adds new room with a RoomType | Room appears in Rooms list with correct type/price |
| Room availability | Search for dates that overlap an existing Confirmed booking on Room 101 | Room 101 excluded from results |
| Overlap edge case | Search a date range that starts exactly on another booking's check-out date | Room IS available (checkout day is free) |
| Multiple room booking | Select 2 rooms, 1 guest, submit | One Booking row + 2 BookingRoom rows created |
| Booking cancellation | Cancel a Confirmed booking | Status becomes Cancelled; room reappears in future availability searches |
| Check-in | Check in a Confirmed booking | Status → Checked-In; room Status → Occupied |
| Check-out | Check out a Checked-In booking | Status → Checked-Out; room Status → Cleaning |
| Payment | Pay less than total | PaymentStatus = Partial, Due > 0 |
| Overpayment guard | Try to pay more than Due | Error shown, no row inserted |
| Service booking | Add "Breakfast" x2 to a booking | Bill total increases by 2×price; appears on invoice |
| Invoice generation | Open invoice for a booking with rooms + services + partial payment | All line items and correct due amount shown |
| Unauthorized access | Log out, visit `/dashboard.php` directly | Redirected to login page |

---

## 16. Report Structure (for submission)
1. Title page
2. Introduction
3. Problem Statement
4. Objectives
5. Scope
6. Existing System
7. Proposed System
8. Functional Requirements
9. Non-Functional Requirements
10. Use Case Diagram (+ specification, Section 8 above)
11. EER Diagram (+ specification, Section 9 above)
12. Relational Schema (Section 10)
13. Normalization (Section 11)
14. Database Design (table screenshots from phpMyAdmin)
15. System Architecture (Section 12)
16. UI Screenshots (Login, Dashboard, Booking flow, Invoice)
17. Implementation (Section 13)
18. Testing (Section 15)
19. Limitations
20. Future Improvements
21. Conclusion

## 17. Limitations
- No online payment gateway (cash/card/mobile banking are recorded manually by staff).
- Race condition on simultaneous booking of the same room is reduced, not fully eliminated
  (would need `SELECT ... FOR UPDATE` row locking for production-grade concurrency safety).
- No email/SMS confirmation to guests.
- Staff specialization implemented via an ENUM column rather than full EER subclass tables.

## 18. Future Improvements
- Payment gateway integration (SSLCommerz/Stripe).
- Email/SMS booking confirmations and reminders.
- AJAX-based live availability search (no page reload).
- Guest self-service portal for online booking.
- Room-locking (`SELECT ... FOR UPDATE`) for full concurrency safety.
- Multi-branch/hotel support.

---

## 19. Viva — Likely Questions & Answers

**Q1: Why is BookingRoom a separate table instead of a RoomID column in Booking?**
A: Because one booking can include multiple rooms — that's an M:N relationship between
Booking and Room, which relational databases resolve with a junction table holding a
composite primary key (BookingID, RoomID).

**Q2: Why store PricePerNight again in BookingRoom when RoomType already has it?**
A: It's a historical snapshot of the rate charged at booking time, not a duplicate of live
data — if the hotel later changes RoomType prices, old invoices must stay unaffected. This is
intentional and doesn't violate 3NF because it's not functionally dependent on RoomID alone
in the same way — it's an attribute of the *relationship instance* (this room, in this
specific booking).

**Q3: How do you prevent double-booking a room?**
A: The `available_rooms()` query excludes any room with an active booking whose date range
overlaps the requested range, using `existing.CheckIn < new.CheckOut AND existing.CheckOut >
new.CheckIn`. The booking-creation step also re-checks availability inside a transaction
immediately before insert.

**Q4: What normal form is your database in, and why?**
A: 3NF — every non-key attribute is fully functionally dependent on its table's primary key
and not transitively dependent through another non-key attribute. See Section 11.

**Q5: How is SQL injection prevented?**
A: Every query uses PDO prepared statements with bound parameters — user input is never
concatenated directly into SQL strings.

**Q6: How are passwords protected?**
A: Passwords are hashed with `password_hash()` (bcrypt) before storage and verified with
`password_verify()`; plaintext passwords are never stored or logged.

**Q7: How does role-based access control work?**
A: Every protected page calls `require_role([...])` at the top, which checks
`$_SESSION['user']['Role']` against an allow-list and shows a 403 page otherwise. The sidebar
menu also only renders links the current role is allowed to use.

**Q8: What happens to a room's status through the booking lifecycle?**
A: Available → (booking made, but status only changes at check-in) → Occupied at check-in →
Cleaning at check-out → Available again once housekeeping marks it clean. Cancelled bookings
never occupy a room since availability is computed by date-overlap, not by a static flag.

**Q9: Why use ENUM for Staff.Position instead of a separate specialization table?**
A: It's a simplification appropriate for project scope — EER specialization would normally
use one subclass table per role linked to a base Staff table (total, disjoint), but with only
a few extra attributes per role, an ENUM discriminator keeps the schema simpler while still
conceptually representing the specialization, which is explained in the EER section.

**Q10: How would you scale this to multiple hotel branches?**
A: Add a `Branch` entity and a `BranchID` foreign key to `Room` (and optionally `Staff`), then
scope all queries (availability, reports) by branch — everything else stays largely the same.
