<!--
Copyright 2025 Google LLC

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
-->

<script lang="ts">
	import { config } from '$lib/config';
	import { publicUrl } from '$lib/storage';
</script>

<article>
	<section>
		<h1>Utilities</h1>
		<ul>
			<li><a href="/maintenance/resize">Add image size</a></li>
			<li><a href="/api/octetbuster" target="_blank">Fix files without Content-Type</a></li>
			<li><a href="/api/htmbuster" target="_blank">Fix index.htm files</a></li>
			<li><a href="/api/importable" target="_blank">ASM pet metadata</a></li>
			<li><a href="/api/render" target="_blank">Regenerate static pages</a></li>
			<li><a href="/api/listings" target="_blank">Listing metadata</a></li>
		</ul>
	</section>

	<section>
		<h1>Useful links</h1>
		<ul>
			<li><a href="https://admin.google.com/u/2/ac/groups/01opuj5n1qqyk8t/members">Editor permissions</a></li>
			<li><a href="https://console.cloud.google.com/home/dashboard?project={config.project}">Cloud dashboard (project:
				{config.project})</a></li>
			<li><a href="https://console.cloud.google.com/logs/query?project={config.project}">Cloud logs</a></li>
			<li><a href="https://console.cloud.google.com/run?project={config.project}">Cloud Run</a></li>
			<li><a href="https://console.cloud.google.com/firestore/databases/{config.database}">Database
				{config.database}</a></li>
			<li><a href="https://console.cloud.google.com/storage/browser/${config.bucket}">Bucket {config.bucket}</a></li>
			<li><a href="https://console.cloud.google.com/billing?project=${config.project}">Billing</a></li>
			<!-- TODO: Get repo from environment variables -->
			<li><a href="https://github.com/fmnas/fmnas-site/actions">GitHub Actions</a></li>
			<!-- TODO: Migrate ASM to GCP -->
			<li><a href="https://cloud.ramnode.com/44450/openstack/instances/0d6b613b-8944-4e73-9099-47515f267b23#info">ASM
				server management</a></li>
			<li><a href="https://clientarea.ramnode.com/fleioaddcredit.php">ASM billing</a></li>
		</ul>
	</section>

	<section class="config">
		<h1>Config</h1>
		<iframe title="config" src="{publicUrl('config.json')}"></iframe>
	</section>
</article>

<style lang="scss">
	:global(body) {
		height: 100%;
		display: flex;
		flex-direction: column;
	}

	article {
		display: grid;
		flex-grow: 1;
		grid-template-columns: 1fr 1fr;
		grid-template-rows: max-content 1fr;
		grid-gap: 1rem;
		height: 100%;
		width: 100%;
	}

	section.config {
		grid-column: 1/span 2;
		display: flex;
		flex-direction: column;
	}

	iframe {
		width: 100%;
		flex-grow: 1;
		box-sizing: border-box;
	}

	ul {
		list-style-type: none;
		margin: 0;
		padding: 0;
	}

	li {
		margin: 0.3rem;
	}
</style>
