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
  const petAge = (pet) => 'pet age needs implementation';
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
