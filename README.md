<!--
  - SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# WebAuthn second factor provider for Nextcloud

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/twofactor_webauthn)](https://api.reuse.software/info/github.com/nextcloud/twofactor_webauthn)

## Requirements

In order to use this app for authentication, you have to use a browser that supports the WebAuthn standard.

## Migration from Two-Factor U2F

It is possible to migrate U2F device registrations to WebAuthn devices registrations. For the migratation, you need command line access to run [occ](https://docs.nextcloud.com/server/stable/admin_manual/configuration_server/occ_command.html).

```shell
# View options – you can run this for all or only specific users
php occ twofactor_webauthn:migrate-u2f --help

# Migrate all users
php occ twofactor_webauthn:migrate-u2f --all

# Disable the U2F app
php occ app:disable twofactor_u2f

# Clean up any U2F registrations
php occ twofactorauth:cleanup u2f
```

## Login with external apps

Once you enable WebAuthn with Two Factor WebAuthn, your applications (for example your GNOME app) will need to login using device passwords. Find out more about this in the [user documentation](https://docs.nextcloud.com/server/stable/user_manual/en/user_2fa.html#using-client-applications-with-two-factor-authentication).

## Development Setup

This app uses [composer](https://getcomposer.org/) and [npm](https://www.npmjs.com/) to manage dependencies. Use

```bash
composer install
npm install
npm run build
```

to set up a development version of this app.
