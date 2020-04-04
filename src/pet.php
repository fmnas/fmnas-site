<?php
require_once "common.php";
require_once "assets.php";

class Species {
	private array $values = array();
//	private string $key;
//	private string $name;
//	private string $plural;
//	private string $young;
//	private string $young_plural;
//	private string $old;
//	private string $old_plural;
//	private int $age_unit_cutoff; // number of months after which age shall be reported in years
//	private int $young_cutoff; // below this number of months, exclusive, use "young name"
//	private int $old_cutoff; // above this number of months, inclusive, use "old name"

	/**
	 * Get an appropriate name for this species depending on age and plurality
	 * such as "cat" or "kittens"
	 * @param string|null $dob Date of birth of the animal in months
	 * @param bool $plural Whether the word should be plural
	 * @return string
	 */
	public function nameGivenDob(string $dob = null, bool $plural = false): string {
		$age = null;
		if ($dob != null) {
			try {
				$age = (new DateTime())->diff(new DateTime($dob))->m;
			} catch (Exception $e) {
				log_err("Exception when converting $dob to DateTime: " . $e->getMessage());
			}
		}
		$age ??= $this->__get("young_cutoff") ?? 36;
		if ($age < ($this->__get("young_cutoff") ?? 0)) {
			return $plural ? ($this->__get("young_plural") ?? $this->__get("plural")) : ($this->__get("young") ?? $this->__get("name"));
		}
		if ($age >= ($this->__get("old_cutoff") ?? PHP_INT_MAX)) {
			return $plural ? ($this->__get("old_plural") ?? $this->__get("plural")) : ($this->__get("old") ?? $this->__get("name"));
		}
		return $plural ? $this->__get("plural") : $this->__get("name");
	}

	/**
	 * Get a printable version of the age such as "11 years" or "3 months"
	 * @param string $dob Date of birth of the animal
	 * @return string Printable version of the age
	 */
	public function age(?string $dob): string {
		if (!$dob) {
			return "&nbsp;";
		}
		try {
			$interval = (new DateTime())->diff(new DateTime($dob));
			$months   = $interval->y * 12 + $interval->m;
			if ($months < 4) {
				return "DOB " . (new DateTime($dob))->format("n/j/y");
			}
			if ($months == 0) {
				return $interval->d . " day" . ($interval->d === 1 ? "" : "s") . " old";
			}
			if ($this->__get("age_unit_cutoff") ?: 12 > $interval->m) {
				return $interval->y . " year" . ($interval->y === 1 ? "" : "s") . " old";
			}
			return $months . " month" . ($months === 1 ? "" : "s") . " old";
		} catch (Exception $e) {
			log_err("Exception when converting $dob to DateTime: " . $e->getMessage());
			return null;
		}
	}

	public function __get($key) {
		return isset($this->values[$key]) ? $this->values[$key] : null;
	}

	public function __set($key, $val) {
		if ($val === null) {
			unset($this->values[$key]);
		} else {
			$this->values[$key] = $val;
		}
	}

	public function setAll(array $arr) {
		$this->values = $arr;
	}

	public function __toString() {
		return strval($this->values["name"]);
	}

	public function plural() {
		return $this->__get("plural") ?: $this->__get("name") . 's';
	}

	public function pluralWithYoung() {
		return ucfirst($this->plural()) . ucfirst(
			(($this->__get("young") ?: $this->__get("name")) === $this->__get("name")) ?
				"" :
				" &amp; " . ($this->__get("young_plural") ?: $this->__get("young") . 's')
			);
	}
}

class Sex {
	public int $key;
	public string $name;

	public function __toString() {
		return $this->name;
	}
}

class Status {
	public int $key;
	public string $name;
	public ?bool $displayStatus; // Display the status in lieu of the adoption fee?
	public bool $listed; // Display this animal in the adoptable animal listings?
	public bool $deleted; // If true, this animal will not be shown in admin view
	public ?string $description; // Optional explanatory description, where $listed is true and $displayStatus is true

	public function __toString() {
		return $this->name;
	}
}

class Pet {
	// Properties corresponding to database fields
	public string $id;
	public string $name;
	public ?Species $species;
	public ?string $breed; // breed or other description
	public ?string $dob; // date of birth
	public ?Sex $sex;
	public ?string $fee; // adoption fee
	public ?Asset $photo; // profile picture asset
	public ?array $photos; // photo assets (array of Assets)
	public ?Asset $description; // description asset
	public Status $status;
	public ?bool $plural;

	public function age(): string {
		return $this->species->age($this->dob);
	}

	public function listed(): bool {
		return ($this->description !== null && strlen(trim($this->description->fetch())) > 0) || ($this->photos !== null && count($this->photos) > 0);
	}

	public function species(): string {
		if (!isset($this->species) || $this->species === null) {
			return null;
		}
		return $this->species->nameGivenDob($this->dob, $this->plural);
	}
}