/* Copyright 2025 Google LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

let helpers = function () {
};

function capitalizeFirstLetter(str) {
	return str.charAt(0).toUpperCase() + str.slice(1);
}

helpers.register = function (Handlebars) {
	Handlebars.registerHelper('currentYear', function () {
		return new Date().toLocaleDateString('en-US', {
			year: 'numeric',
		});
	});

	Handlebars.registerHelper('localeDate', function (isoDateString) {
		return new Date(isoDateString).toLocaleDateString('en-US', {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
		});
	});

	Handlebars.registerHelper('pluralWithYoung', function (species) {
		if (!species.young) {
			return capitalizeFirstLetter(species.plural);
		}
		return capitalizeFirstLetter(species.plural) + ' & ' + capitalizeFirstLetter(species.young_plural);
	});
};

module.exports = helpers;
