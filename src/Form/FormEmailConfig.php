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

namespace fmnas\Form;

use Closure;
use DOMDocument;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * The configuration for a single email sent after form processing.
 */
class FormEmailConfig {

	/**
	 * The emails for the Reply-To header.
	 * In most cases, this iterable should be an array.
	 * If it doesn't generate any values, the Reply-To header will be omitted.
	 * @var iterable<EmailAddress>
	 */
	public iterable $replyTo;
	/**
	 * The emails for the Cc header.
	 * In most cases, this iterable should be an array.
	 * If it doesn't generate any values, the Cc header will be omitted.
	 * @var iterable<EmailAddress>
	 */
	public iterable $cc;
	/**
	 * The emails for the Bcc header.
	 * In most cases, this iterable should be an array.
	 * If it doesn't generate any values, the Bcc header will be omitted.
	 * @var iterable<EmailAddress>
	 */
	public iterable $bcc;
	/**
	 * If non-empty, a directory on the server to which to output uploaded files.
	 * Can also be a closure that takes the file metadata array.
	 * @param array file metadata array
	 * @return string
	 */
	public string|Closure $fileDir;
	/**
	 * Whether to attach an uploaded file to the email.
	 * Can also be a closure that takes the file metadata array.
	 * @param array file metadata array
	 * @return bool
	 */
	public bool|Closure $attachFiles;
	/**
	 * Uploaded filenames can be replaced with a hash of the file.
	 * (This is desirable for security if $fileDir is a publicly accessible directory.)
	 * Can also be a closure that takes the file metadata array
	 * @param array file metadata array
	 * @return HashOptions
	 */
	public HashOptions|Closure $hashFilenames;
	/**
	 * Converter applied to each file before hashFilenames.
	 * @param array &$metadata file metadata array
	 * @return void
	 */
	public ?Closure $fileConverter;
	/**
	 * Converter applied to all files before fileConverter and hashFilenames are applied to each file.
	 * @param array &$metadata array of file metadata arrays
	 * @return void
	 */
	public ?Closure $filesConverter;
	/**
	 * Whether to apply $fileConverter to $_FILES instead of $files.
	 */
	public bool $globalConversion;
	/**
	 * If non-empty, a path on the server to which to save the rendered form as an HTML file in lieu of
	 * emailing it.
	 */
	public string $saveFile;
	/**
	 * Transformer applied immediately before rendering and sending the email.
	 * @param DOMDocument &$dom The DOM immediately before rendering.
	 * @param array<AttachmentInfo> &$attachments The email attachments.
	 * @return null|string - if returned, used as HTML instead of $dom
	 */
	public ?Closure $emailTransformation;

	/**
	 * FormEmailConfig constructor.
	 * @param EmailAddress|null $from The email for the From header, or null to dump HTML rather than emailing.
	 * @param iterable<EmailAddress> $to The emails for the To header.
	 * @param string $subject The value for the Subject header.
	 * @param array|null $values An optional array of arbitrary data accessible
	 * when rendering the email. If an element in the form has a data-value
	 * attribute, its value will be used as a key when accessing this array.
	 * For example, <input type="hidden" data-value="foo"> will be rendered as
	 * <span>{{$values['foo']}}</span>
	 */
	public function __construct(
			public ?EmailAddress $from,
			public iterable $to,
			public string $subject,
			public SMTPConfig $smtp,
			public ?array $values = []) {
		$this->replyTo = [];
		$this->cc = [];
		$this->bcc = [];
		$this->fileDir = "";
		$this->attachFiles = true;
		$this->hashFilenames = HashOptions::NO;
		$this->fileConverter = null;
		$this->filesConverter = null;
		$this->globalConversion = false;
		$this->saveFile = "";
		$this->emailTransformation = null;
	}

	/**
	 * Send an email.
	 * @param RenderedEmail $renderedEmail The rendered email.
	 * @throws Exception
	 */
	public function send(RenderedEmail $renderedEmail): void {
		$emailBody = $renderedEmail->html;
		$attachments = $renderedEmail->attachments;

		if ($this->from === null || $this->saveFile) {
			$html = $emailBody;
			foreach ($attachments as $attachment) {
				$html .= "\n<!-- attach $attachment->path as $attachment->filename with type $attachment->type -->";
			}
			if ($this->saveFile) {
				file_put_contents($this->saveFile, $html);
				return;
			}
			echo $html;
			return;
		}

		$mailer = new PHPMailer(true);
		$mailer->IsSMTP();
		$mailer->Host = $this->smtp->host;
		$mailer->Port = $this->smtp->port;
		$mailer->SMTPAuth = $this->smtp->auth;
		$mailer->SMTPSecure = $this->smtp->security;
		$mailer->Username = $this->smtp->user;
		$mailer->Password = $this->smtp->password;
		$mailer->From = $this->from->address;
		$mailer->CharSet = 'UTF-8';
		$mailer->Encoding = 'base64';
		if ($this->from->name) {
			$mailer->FromName = $this->from->name;
		}
		foreach ($this->to as $to) {
			/** @var $to EmailAddress */
			$mailer->AddAddress($to->address, $to->name ?: '');
		}
		foreach ($this->replyTo as $replyTo) {
			/** @var $replyTo EmailAddress */
			$mailer->AddReplyTo($replyTo->address, $replyTo->name ?: '');
		}
		foreach ($this->cc as $cc) {
			/** @var $cc EmailAddress */
			$mailer->AddCc($cc->address, $cc->name ?: '');
		}
		foreach ($this->bcc as $bcc) {
			/** @var $bcc EmailAddress */
			$mailer->AddBcc($bcc->address, $bcc->name ?: '');
		}
		foreach ($attachments as $attachment) {
			/** @var $attachment AttachmentInfo */
			$mailer->addAttachment($attachment->path, $attachment->filename,
					startsWith($attachment->type, "text/") ? "quoted-printable" : "base64", $attachment->type);
		}
		$mailer->IsHTML(true);
		$mailer->Subject = $this->subject;
		$mailer->Body = $emailBody;
		$mailer->Send();

		foreach($attachments as $attachment) {
			/** @var $attachment AttachmentInfo */
			if ($attachment->delete) {
				unlink($attachment->path);
			}
		}
	}
}
