/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Encode an array of bytes as a base64 string.
 *
 * @param {Uint8Array} a An array of bytes
 * @return {string} A base64 string
 */
export function arrayToBase64String(a) {
	return btoa(String.fromCharCode(...a))
}

/**
 * Decode a base64 string to an array of bytes.
 *
 * @param {string} s A base64 string
 * @return {Uint8Array} An array of bytes
 */
export function base64StringToArray(s) {
	return Uint8Array.from(atob(s), c => c.charCodeAt(0))
}

/**
 * Convert a base64url string to a base64 string.
 *
 * @param {string} input A base64url string
 * @return {string} A base64 string
 */
export function base64url2base64(input) {
	input = input
		.replace(/=/g, '')
		.replace(/-/g, '+')
		.replace(/_/g, '/')

	const pad = input.length % 4
	if (pad) {
		if (pad === 1) {
			throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding')
		}
		input += new Array(5 - pad).join('=')
	}

	return input
}
