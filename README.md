# WebAuthn second factor provider for Nextcloud

# Requirements
In order to use this app for authentication, you have to use a browser that supports the WebAuthn standard.

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
