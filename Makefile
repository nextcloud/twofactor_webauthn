# Makefile for building the project
#
# SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
all: install

clean:
	rm -rf vendor
	rm -rf node_modules
	rm -rf js/build

install:
	composer install --dev
	npm install
	npm run build
