<!--
Copyright 2022 Google LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

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
	// TODO [$61f4c71115395d0009dba031]: Redesign the admin site nav.
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
