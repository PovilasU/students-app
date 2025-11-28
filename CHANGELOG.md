# Changelog

All notable changes to this project are documented here.

---

## [1.0.0] – Final System Release

### Added
- Full application structure using MVC-like architecture (Controller → Service → Repository → View).
- Complete Lithuanian UI localization (status labels, forms, error messages).
- CSRF protection for all POST forms (login, register, create, edit, reject).
- XSS protection using `htmlspecialchars` across all view templates.
- Login rate limiting (anti‑bruteforce) with lockout logic.
- Session security improvements (`session_regenerate_id` after login).
- Spam protection for draft creation (rate-limited actions via Service layer).
- Additional unit tests:
  - Draft creation validation
  - Status transitions (draft → submitted → approved/rejected)
  - "Max 3 submitted per type" business rule
  - Reject comment validation
  - Draft spam prevention
- In-memory SQLite usage for clean and isolated test environment.

### Improved
- Repository layer now uses only PDO prepared statements.
- Service layer now fully encapsulates business rules.
- Controller endpoints simplified and restricted by user roles.
- Application list, edit, reject views separated out of public scripts.
- Stronger error handling and safer fail states.

### Documentation
- Created detailed README with:
  - Architecture explanation
  - SOLID usage overview
  - Design pattern descriptions
  - Security features summary
  - Test instructions
  - Project structure
  - Deployment instructions
- Generated downloadable README and CHANGELOG files.

---

## [0.1.0] – Initial Prototype
- Basic application CRUD using SQLite.
- Simple login system.
- Minimal routing through `public/` directory.
- No security protections.
- Raw HTML inside controller logic.

---

