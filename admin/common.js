function r404(path) {
  window.location.href = `/404.php?p=${encodeURIComponent(path)}`;
}

const globalsPromise = fetch('/api/config', {
  method: 'get',
}).then(res => {
  if (!res.ok) throw res;
  return res.json();
}).then((config) => {
  return {
    methods: {
      getPathForPet: (pet) =>
        `${config['species']?.[pet['species']]?.['plural']}/${pet['id']}${pet['name']?.split(' ').join('')}`,
    },
    data() {
      return {
        config,
      };
    },
  };
});

// Dummy definitions for PhpStorm:
const config = {};
const getPathForPet = (pet) => {};