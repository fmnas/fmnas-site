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
	<div class="container" @click.self="$emit('cancel')">
		<article class="modal">
			<div class="body">
				<slot>
					Are you sure?
				</slot>
			</div>
			<div class="modal buttons">
        <slot name="buttons">
          <button class="danger" @click="$emit('confirm')">Confirm</button>
          <button @click="$emit('cancel')">Cancel</button>
        </slot>
			</div>
		</article>
	</div>
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
	name: 'Modal',
	emits: ['cancel', 'confirm'],
});
// TODO [#172]: Add transition to modal.
// TODO [#173]: Add Esc and Enter keybindings to modal.
</script>

<style scoped lang="scss">
div.container {
	position: fixed;
	top: 0;
	left: 0;
	z-index: 10;
	width: 100vw;
	height: 100vh;
	background-color: #0003;
	display: flex;
	justify-content: center;
	align-items: center;
}

article.modal {
	background-color: #fffffff6;
	padding: 1rem;
	border-radius: 0.5rem;
	border: 1px solid red;
	max-width: 95vw;
	max-height: 95vh;
}

div.buttons {
	display: flex;
	justify-content: space-evenly;
}
</style>

<style lang="scss">
// Scoped styles don't apply in slot
div.modal.buttons {
  button {
    margin-top: 1rem;
    font-size: 120%;
    padding: 0.2em 0.6em;

    &.danger {
      color: red;
    }
  }
}
</style>
