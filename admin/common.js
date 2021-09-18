function r404(path) {
  window.location.href = `/404.php?p=${encodeURIComponent(path)}`;
}

const ucfirst = (str = '') => str.charAt(0).toUpperCase() + str.slice(1);

// Dummy definitions for PhpStorm
const config = {};
const petAge = (pet) => '';

const globalsPromise = fetch('/api/config', {
  method: 'GET',
}).then(res => {
  if (!res.ok) throw res;
  return res.json();
}).then((config) => {
  const getPathForPet = (pet) => `${pet['id']}${pet['name']?.split(' ').join('')}`;
  const getFullPathForPet = (pet) => `${config['species']?.[pet['species']]?.['plural']}/${getPathForPet(pet)}`;
  const petAge = (pet) => {
    const dob = pet['dob'];
    if (!dob) {
      return '&nbsp;';
    }
    try {
      const species = config['species']?.[pet['species']];
      const startDate = new Date(dob);
      const endDate = new Date();
      const yearDiff = endDate.getFullYear() - startDate.getFullYear();
      const monthDiff = endDate.getMonth() - startDate.getMonth();
      const dayDiff = endDate.getDate() - startDate.getDate();

      let years = yearDiff;
      if (monthDiff < 0) {
        years -= 1;
      }

      let months = yearDiff * 12 + monthDiff;
      if (dayDiff < 0) {
        months -= 1;
      }

      if (months < 4) {
        return `DOB ${startDate.getMonth() + 1}/${startDate.getDate() + 1}/${startDate.getFullYear()}`;
      }
      if (months > (species?.['age_unit_cutoff'] || 12)) {
        return `${years} years old`;
      }
      return `${months} months old`;
    } catch (e) {
      console.error('Error when calculating age', pet);
      return `DOB ${dob}`;
    }
  };
  return {
    methods: {
      ucfirst,
      getPathForPet,
      getFullPathForPet,
      petAge,
    },
    data() {
      return {
        config,
      };
    },
  };
});
