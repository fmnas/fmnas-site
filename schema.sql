SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE assets
(
	id   int(11) NOT NULL,
	path varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	data text COLLATE utf8mb4_unicode_ci,
	type varchar(255) COLLATE utf8mb4_unicode_ci  DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE config
(
	config_key   varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
	config_value text COLLATE utf8mb4_unicode_ci
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='Global configuration values; these are cached by the backend';

-- TODO [#67]: Replace schema.sql with schema.sql.hbs
INSERT INTO config
VALUES ('address', '49&nbsp;W&nbsp;Curlew&nbsp;Lake&nbsp;Rd\nRepublic&nbsp;WA&nbsp;99166‑8742'),
       ('admin_domain', 'admin.fmnas.org'),
       ('default_email_user', 'adopt'),
       ('fax', '208-410-8200'),
       ('longname', 'Forget Me Not Animal Shelter of Ferry County'),
       ('phone', '(509)&nbsp;775-2308'),
       ('phone_intl', '+15097752308'),
       ('public_domain', 'fmnas.org'),
       ('shortname', 'Forget Me Not Animal Shelter'),
       ('transport_date', '2021-12-06');

CREATE TABLE pets
(
	id          varchar(15) COLLATE utf8mb4_unicode_ci  NOT NULL,
	name        varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	species     tinyint(4)                                       DEFAULT NULL,
	breed       varchar(1023) COLLATE utf8mb4_unicode_ci         DEFAULT NULL COMMENT 'or other description',
	dob         date                                             DEFAULT NULL,
	sex         tinyint(4)                                       DEFAULT NULL,
	fee         varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
	photo       int(11)                                          DEFAULT NULL,
	description int(11)                                          DEFAULT NULL,
	status      smallint(6)                             NOT NULL DEFAULT '1',
	plural      tinyint(1)                                       DEFAULT '0',
	legacy_path varchar(270) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`id`, `name`)) VIRTUAL,
	path        varchar(270) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`id`, replace(`name`, ' ', ''))) VIRTUAL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE photos
(
	pet   varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	photo int(11)                                 NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE sexes
(
	id   tinyint(4)                              NOT NULL,
	name varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO sexes
VALUES (1, 'male'),
       (2, 'female');

CREATE TABLE species
(
	id              tinyint(4)                              NOT NULL,
	name            varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
	plural          varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	young           varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	young_plural    varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	old             varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	old_plural      varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	age_unit_cutoff smallint(6)                             DEFAULT NULL COMMENT 'in months',
	young_cutoff    smallint(6)                             DEFAULT NULL COMMENT 'in months',
	old_cutoff      smallint(6)                             DEFAULT NULL COMMENT 'in months'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO species
VALUES (1, 'cat', 'cats', 'kitten', 'kittens', 'senior cat', 'senior cats', 12, 6, 96),
       (2, 'dog', 'dogs', 'puppy', 'puppies', 'senior dog', 'senior dogs', 12, 6, 96);

CREATE TABLE statuses
(
	id          smallint(6)                             NOT NULL,
	name        varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	display     tinyint(1)                                       DEFAULT NULL,
	listed      tinyint(1)                              NOT NULL DEFAULT '1',
	description text COLLATE utf8mb4_unicode_ci
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO statuses
VALUES (1, 'Adoptable', 0, 1, ''),
       (2, 'Adopted', NULL, 0, ''),
       (3, 'Adoption Pending', 1, 1,
        'We either have so many applications we are confident of finding the pet\'s new home from among them, OR the pet has been offered to an applicant who has accepted placement, and we will be delivering the pet on the next Seattle or Spokane trip.\r\n\r\nYou can submit an application for one of these pets if you\'d like to be a \"backup home\" should anything not work out with the prior applicants, but it\'s a longshot.'),
       (4, 'Applications Closed', 1, 1,
        'We have received a fairly large number of applications in a fairly short period of time, and need a chance to review them to see if any will be a great match to the particular pet. If the right match is not found in the applications already received, we will REOPEN applications.\r\n\r\nYou may still submit an application for one of these pets, and we will review it right away if the right match is not found first.');


ALTER TABLE assets
	ADD PRIMARY KEY (id),
	ADD KEY path (path(768));

ALTER TABLE config
	ADD PRIMARY KEY (config_key);

ALTER TABLE pets
	ADD PRIMARY KEY (id),
	ADD UNIQUE KEY legacy_path (legacy_path),
	ADD UNIQUE KEY path (path),
	ADD KEY name (name),
	ADD KEY description (description),
	ADD KEY photo (photo),
	ADD KEY sex (sex),
	ADD KEY species (species),
	ADD KEY status (status);

ALTER TABLE photos
	ADD PRIMARY KEY (pet, photo),
	ADD KEY pet (pet),
	ADD KEY photo (photo);

ALTER TABLE sexes
	ADD PRIMARY KEY (id);

ALTER TABLE species
	ADD PRIMARY KEY (id);

ALTER TABLE statuses
	ADD PRIMARY KEY (id);


ALTER TABLE assets
	MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE sexes
	MODIFY id tinyint(4) NOT NULL AUTO_INCREMENT,
	AUTO_INCREMENT = 3;

ALTER TABLE species
	MODIFY id tinyint(4) NOT NULL AUTO_INCREMENT,
	AUTO_INCREMENT = 3;

ALTER TABLE statuses
	MODIFY id smallint(6) NOT NULL AUTO_INCREMENT,
	AUTO_INCREMENT = 6;


ALTER TABLE pets
	ADD CONSTRAINT pets_ibfk_1 FOREIGN KEY (description) REFERENCES assets (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ADD CONSTRAINT pets_ibfk_2 FOREIGN KEY (photo) REFERENCES assets (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ADD CONSTRAINT pets_ibfk_3 FOREIGN KEY (sex) REFERENCES sexes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ADD CONSTRAINT pets_ibfk_4 FOREIGN KEY (species) REFERENCES species (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ADD CONSTRAINT pets_ibfk_5 FOREIGN KEY (status) REFERENCES statuses (id) ON UPDATE CASCADE;

ALTER TABLE photos
	ADD CONSTRAINT photos_ibfk_1 FOREIGN KEY (photo) REFERENCES assets (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD CONSTRAINT photos_ibfk_2 FOREIGN KEY (pet) REFERENCES pets (id) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
