function r404(path) {
  window.location.href = `/404.php?p=${encodeURIComponent(path)}`;
}

const getPathForPet = (pet, config) =>
  `${config['species']?.[pet['species']]?.['plural']}/${pet['id']}${pet['name']?.split(' ').join('')}`;

const globals = {
  methods: {
    getPathForPet,
  },
};