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

import { arrayToBase64String, base64StringToArray, base64url2base64 } from '../../utils/base64.js'

describe('utils/base64', () => {
	it('should convert byte arrays to base64 strings', () => {
		expect(arrayToBase64String(new Uint8Array([4, 2]))).to.equal('BAI=')
		expect(arrayToBase64String(new Uint8Array([4]))).to.equal('BA==')
		expect(arrayToBase64String(new Uint8Array([1, 2, 3, 4, 5, 6]))).to.equal('AQIDBAUG')
		expect(arrayToBase64String(new Uint8Array([]))).to.equal('')
	})

	it('should convert base64 strings to byte arrays', () => {
		expect(base64StringToArray('BAI=')).to.deep.equal(new Uint8Array([4, 2]))
		expect(base64StringToArray('BA==')).to.deep.equal(new Uint8Array([4]))
		expect(base64StringToArray('AQIDBAUG')).to.deep.equal(new Uint8Array([1, 2, 3, 4, 5, 6]))
		expect(base64StringToArray('')).to.deep.equal(new Uint8Array([]))
	})

	it('should convert base64url strings to base64 strings', () => {
		expect(base64url2base64('ajEyMw')).to.equal('ajEyMw==')
		expect(base64url2base64('srurs_akpa_Gys')).to.equal('srurs/akpa/Gys==')
		expect(base64url2base64('sadf-asdf-')).to.equal('sadf+asdf+==')
	})
})
