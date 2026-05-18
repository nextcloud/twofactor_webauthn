<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# AGENTS.md

This file provides guidance to AI coding assistants working with code in this repository.

## Commands

### Setup
```bash
composer install
npm ci
```

### JavaScript
See `package.json` scripts for all available commands (build, dev, watch, lint, lint:fix, test:unit, test:e2e, etc.).

### PHP
Available composer commands:
```bash
composer cs:check                # Check code style
composer cs:fix                  # Fix code style
composer lint                    # PHP syntax check
composer psalm                   # Run static analysis
composer test:unit               # Run unit tests
composer test:integration        # Run integration tests
```
See `composer.json` for all available commands.

## Architecture

### Stack
- **Backend**: PHP (see `appinfo/info.xml` for version requirements), Nextcloud app framework, `web-auth/webauthn-lib` for WebAuthn spec implementation. Namespace: `OCA\TwoFactorWebAuthn\`.
- **Frontend**: Vue 3, Pinia, bundled with webpack into three separate entry points (settings, challenge, login-setup).

### PHP Backend (`lib/`)
Layered: Controllers → Services → DB Mappers.

- **`Controller/`** — Thin HTTP handlers for device registration/removal/activation endpoints.
- **`Service/`** — Core WebAuthn logic (`WebAuthnManager`): orchestrates registration and authentication using `web-auth/webauthn-lib`.
- **`Db/`** — Nextcloud `QBMapper`-based mappers and entity models (`PublicKeyCredentialEntity`, `PublicKeyCredentialEntityMapper`).
- **`Repository/`** — `WebauthnPublicKeyCredentialSourceRepository` adapts the Nextcloud DB layer to the interface expected by `web-auth/webauthn-lib`.
- **`Provider/`** — `WebAuthnProvider` implements the Nextcloud two-factor provider interfaces (`IProvider`, `IProvidesPersonalSettings`, `IDeactivatableByAdmin`, etc.).
- **`Listener/`** — Event listeners for activity logging and two-factor registry updates.
- **`Command/`** — OCC CLI commands: `MigrateU2F` and `CleanUp`.
- **`Migration/`** — Database migrations.
- **`Model/`** — `Device` is an immutable value object representing a registered credential.

### JavaScript Frontend (`src/`)
Three independent webpack bundles, each mounted into a Nextcloud template.

- **`store.js`** — Pinia store for device list and credential request options; seeded via Nextcloud `InitialState`.
- **`services/RegistrationService.js`** — HTTP client calling the PHP REST API.
- **`components/`** — `PersonalSettings.vue` (device list), `AddDeviceDialog.vue` (4-step registration), `Device.vue` (per-device card), `Challenge.vue` (login authentication), `LoginSetup.vue` (first-time setup).

### Key Conventions
- **Registration**: `appinfo/info.xml` declares the app metadata. `AppInfo/Application.php` registers event listeners and services via the Nextcloud bootstrap API (`IBootstrap`).
- **InitialState**: PHP controllers push data to the frontend via `IInitialStateService`; Vue components read it with `loadState()`.
- **Events**: State changes dispatch events from `lib/Event/`; listeners in `lib/Listener/` react to them for activity logging and registry updates.
- **REUSE & SPDX**: Every file requires an SPDX license header. **New files must use `AGPL-3.0-or-later`, never `AGPL-3.0-only`**. Header format:
  ```php
  /**
   * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
   * SPDX-License-Identifier: AGPL-3.0-or-later
   */
  ```

## Testing

### Unit Tests
Located in `tests/Unit/` with structure mirroring `lib/`.

#### Pattern
- Use **arrange-act-assert** structure with blank lines separating each phase (no literal comments)
- Mock dependencies via `$this->createMock(Interface::class)`
- Setup mocks in `setUp()` for common fixtures

#### Running Tests
```bash
composer test:unit                                                    # Run all unit tests
composer test:unit -- tests/Unit/Service/WebAuthnManagerTest.php     # Run specific file
composer test:unit -- --filter="TestClassName"                        # Run tests matching filter
```

### Integration Tests
Located in `tests/Integration/`.

```bash
composer test:integration                                   # Run all integration tests
composer test:integration -- --filter="TestClassName"       # Run tests matching filter
```

### JavaScript Tests
```bash
npm run test:unit                    # Mocha/Chai unit tests
npm run test:unit:watch              # Watch mode
npm run test:e2e                     # Playwright end-to-end tests
npm run test:e2e:ui                  # Playwright E2E with interactive UI
```

## Git Workflow

Do NOT commit changes unless explicitly asked to do so.

After completing code changes:
1. Verify your work is complete and tests pass
2. Never push directly to `main` — always create a feature branch with a descriptive name (e.g. `fix/credential-cleanup`, `feat/device-rename`).
3. Make sure there is no trailing whitespace
4. Leave changes in working directory or staged (do not commit)
5. Provide a summary of what was changed and why
6. Suggest a commit message using Conventional Commits format

### Styling

For all CSS colors, spacing, and dimensions, use the standard Nextcloud CSS variables. Do not leave magic numbers; use `calc(x * var(...))` when more specific control is needed.
