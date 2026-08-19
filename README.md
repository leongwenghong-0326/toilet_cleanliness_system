# ClearCheck

College-wide toilet cleanliness monitoring built with PHP, MySQL, and device-camera evidence.

## Local setup

1. Start Apache and MySQL in XAMPP.
2. Copy `.env.example` to `.env` and set `DB_USER`/`DB_PASS` to the credentials for the local MySQL account (`config.php` reads these; `.env` is git-ignored).
3. Visit `http://localhost/toilet_cleanliness/setup.php` once and select **Initialize database**.
4. Sign in at `http://localhost/toilet_cleanliness/`.

Demo accounts:

- Admin: `admin@clearcheck.test` / `admin123`
- Student: `ali@student.test` / `student123`
- Student: `maya@student.test` / `student123`

## Workflow

Students choose an assigned toilet, open the device camera, capture up to three before photos, and submit a check-in note. A check-out is only accepted when the current user has an active check-in. Check-out also accepts up to three camera-captured after photos. Completed sessions are retained in `toilet_sessions` with their evidence in `session_photos` and appear in the shared admin history.

The app intentionally has no file input or photo picker. Images are captured through `navigator.mediaDevices.getUserMedia`, converted to camera snapshots in the browser, and validated as image data on the server.

For production, serve the app over HTTPS so browser camera permissions work on mobile devices, use a non-root MySQL user, and add a scheduled cleanup policy for orphaned files if needed.

## Admin tools

- **Overview** (`admin.php`) — stats, shared history with filters (toilet, status, date, student name), pagination, an overdue badge for active check-ins past 2 hours, a photo viewer per visit, and CSV export.
- **Toilets** (`admin_toilets.php`) — add toilets and change their status (available / needs attention / closed).
- **Students** (`admin_users.php`) — create student accounts, deactivate/reactivate them, and assign which toilets each student is responsible for.

Toilet status also updates automatically: a check-in or check-out comment containing words like "dirty", "wet", "rubbish", "broken", etc. marks the toilet as **needs attention**; a clean check-out comment reverts it to **available** (an admin-set **closed** status is never overridden).

## Account security

- Failed sign-ins are logged in `login_attempts`; after 5 failed attempts for an email within 15 minutes, sign-in is locked out for that window.
- `forgot.php` / `reset.php` provide a token-based password reset (`password_resets` table, 30-minute expiry). No mail server is configured, so the reset link is shown directly on screen — wire up a mailer before using this in production.
