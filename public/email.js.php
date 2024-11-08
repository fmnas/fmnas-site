<?php
/*
 * Copyright 2021 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once "../src/common.php";
header('Content-Type: text/javascript');
?>
document.addEventListener('DOMContentLoaded', function () {
	let domain = <?=json_encode(_G_public_domain())?>;
	document.querySelectorAll('a[data-email]').forEach(function (emailLink) {
		let user = emailLink.getAttribute('data-email') || <?=json_encode(_G_default_email_user())?>;
		let addr = `${user}@${domain}`;
		emailLink.innerHTML = emailLink.innerHTML || addr;
		emailLink.setAttribute('href', `mailto:${addr}`);
	});
});
