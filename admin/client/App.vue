<template>
  <nav>
    <router-link to="/">Home</router-link> |
    <router-link to="/cats">Cats</router-link> |
	  <router-link to="/dogs">Dogs</router-link>
  </nav>
  <router-view/>
</template>

<script>
export default {
	name: 'FMNAS Admin',
};
</script>

<style lang="scss">
#app {
  text-align: center;
	font-size: 12pt;
	font-family: Arial, sans-serif;
}

nav {
	// TODO: Redesign the admin site nav.
  padding: 30px;

  a {
    font-weight: bold;
    color: #2c3e50;

    &.router-link-exact-active {
      color: #42b983;
    }
  }
}
</style>
