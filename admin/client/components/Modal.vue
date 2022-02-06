<template>
	<div class="container" @click.self="$emit('cancel')">
		<article class="modal">
			<div class="body">
				<slot>
					Are you sure?
				</slot>
			</div>
			<div class="buttons">
				<button class="confirm" @click="$emit('confirm')">Confirm</button>
				<button class="cancel" @click="$emit('cancel')">Cancel</button>
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

	button {
		margin-top: 1rem;
		font-size: 120%;
		padding: 0.2em 0.6em;

		&.confirm {
			color: red;
		}
	}
}
</style>
