<?php
/*
 * Copyright 2022 Google LLC
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

require_once __DIR__ . "/../vendor/autoload.php";

function logHeaders(): void {
	if (!ob_get_length() && is_callable('fastcgi_finish_request')) {
		fastcgi_finish_request();
	}
	$dbname = 'analytics_' . date("F");
	$secrets = __DIR__ . "/../secrets";
	@unlink("$secrets/analytics.db");
	@symlink("$secrets/$dbname.db", "$secrets/analytics.db");
	$db = new SQLite3("$secrets/$dbname.db");
	$db->busyTimeout(1000); // TODO: Increase busy timeout
	$db->query("
	CREATE TABLE IF NOT EXISTS requests (start INTEGER, end INTEGER, uri TEXT, ip TEXT, host TEXT, agent TEXT, 
	description TEXT, browser TEXT, major INTEGER, minor TEXT, os TEXT, version TEXT, edition TEXT, ua TEXT, 
	mobile TEXT, platform TEXT, width TEXT, memory TEXT, downlink TEXT, accept TEXT, encoding TEXT, language TEXT,
	referer TEXT)
	");
	$s =
			$db->prepare("
			INSERT INTO requests VALUES (:start, :end, :uri, :ip, :host, :agent, :description, :browser, :major, :minor, 
			                             :os, :version, :edition, :ua, :mobile, :platform, :width, :memory, :downlink, 
			                             :accept, :encoding, :language, :referer)
			");
	if (!$s) {
		return;
	}
	$headers = getallheaders();
	$parsed = new WhichBrowser\Parser($headers);
	$s->bindValue(':start', $_SERVER['REQUEST_TIME'] ?? null);
	$s->bindValue(':end', time());
	$s->bindValue(':uri', $_SERVER['REQUEST_URI'] ?? null);
	$s->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? null);
	$s->bindValue(':host', $_SERVER['REMOTE_HOST'] ?? null);
	$s->bindValue(':agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
	$s->bindValue(':description', $parsed->toString());
	$s->bindValue(':browser', $parsed->browser?->name ?? null);
	$s->bindValue(':major', $parsed->browser?->version?->getMajor() ?? null);
	$s->bindValue(':minor', $parsed->browser?->version?->value ?? null);
	$s->bindValue(':os', $parsed->os?->getName() ?? null);
	$s->bindValue(':version', $parsed->os?->getVersion() ?? null);
	$s->bindValue(':edition', $parsed->os?->edition ?? null);
	$s->bindValue(':ua', $headers['Sec-CH-UA'] ?? null);
	$s->bindValue(':mobile', $headers['Sec-CH-UA-Mobile'] ?? null);
	$s->bindValue(':platform', $headers['Sec-CH-UA-Platform'] ?? null);
	$s->bindValue(':width', $headers['Viewport_Width'] ?? null);
	$s->bindValue(':memory', $headers['Device-Memory'] ?? null);
	$s->bindValue(':downlink', $headers['Downlink'] ?? null);
	$s->bindValue(':encoding', $headers['Accept-Encoding'] ?? null);
	$s->bindValue(':accept', $headers['Accept'] ?? null);
	$s->bindValue(':language', $headers['Accept-Language'] ?? null);
	$s->bindValue(':referer', $_SERVER['HTTP_REFERER'] ?? null);
	@$s->execute();
	$db->close();
}

