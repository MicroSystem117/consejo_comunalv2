Project structure for consejo_comunalv2.0.0

- index.php — main frontend entry (contains combined login/register UI)
- assets/ — static assets (images, fonts)
- public/ — vendor JS/CSS (local Bootstrap, icons)
- config/ — configuration helpers
  - DataBaseManager.php — DB connection helper
  - constants.php, security.php
- src/ — application source
  - controllers/ — HTTP endpoints (auth.php, password_reset.php, etc.)
    - legacy/ — preserved legacy handlers (e.g. legacy/login.php)
  - models/ — data layer helpers and SQL dumps (dbuser.php, credentials.sql)
  - views/ — server-rendered partials (if any)
- migrations/ — SQL migration files (apply in order to the `credentials` DB)
- scripts/ — development scripts and CLI helpers (auth_cli.php, password_reset_cli.php, migration helpers)
- backups/ — DB dumps and backups

Guidelines
- Add server-side endpoints under `src/controllers`.
- Place reusable DB/model helpers in `src/models`.
- SQL schema or dumps live in `src/models` or `migrations` depending on use:
  - `migrations/` for repeatable ALTER/CREATE statements to apply to the live DB
  - `src/models/credentials.sql` for a canonical dump
- Development helpers (one-off scripts) go in `scripts/` and should be CLI-only.
- Keep third-party vendor assets in `public/vendor/` (no CDN).

Notes
- `auth.php` is the canonical auth endpoint (handles register/login/save_questions).
- `src/controllers/login.php` is deprecated and returns an informational JSON. The legacy copy is preserved in `src/controllers/legacy/login.php`.
- `SecQuestion.AnswerOne/Two` columns are VARCHAR(255) to store hashed answers.
- `user.id_level` default is 3 (Expectador).

Recent Changes (2024)
- Fixed modal dialogs not showing (duplicate modal HTML removed from footer)
- Fixed database connection errors in CSRF validation (controllers now use correct DB)
- Fixed duplicate cedula validation when adding persons
- Simplified JavaScript (app.js) with dynamic modal creation
- CSRF validation temporarily disabled on all CRUD controllers for testing
- All controllers return JSON responses consistently
- Added validation for required fields and data formats
