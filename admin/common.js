function r404(path) {
  window.location.href = `/404.php?p=${encodeURIComponent(path)}`;
}

const globalsPromise = fetch('/api/config', {
  method: 'GET',
}).then(res => {
  if (!res.ok) throw res;
  return res.json();
}).then((config) => {
  const getPathForPet = (pet) => `${pet['id']}${pet['name']?.split(' ').join('')}`;
  const getFullPathForPet = (pet) => `${config['species']?.[pet['species']]?.['plural']}/${getPathForPet(pet)}`;
  return {
    methods: {
      getPathForPet,
      getFullPathForPet,
    },
    data() {
      return {
        config,
      };
    },
  };
});
