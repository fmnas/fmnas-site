<template>
  <div class="entry">
    <div class="img">
      <img :src="pet.base64 ? `data:${pet.type};base64,${pet.base64}` : `/api/raw/cached/${pet.photo?.key}_64.jpg`"
          alt="">
    </div>
    <div class="details">
      <span class="id">{{ pet.id }}</span>
      <span class="name">{{ pet.name }}</span>
      <span v-if="pet.pending !== undefined" class="import">Import from ASM</span>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue';
import {ImportablePet, Pet} from '../types';

export default defineComponent({
  name: 'PetDropdownEntry',
  props: {
    pet: {
      type: Object as PropType<Pet | ImportablePet>,
      required: true,
    },
  },
});
</script>

<style scoped lang="scss">
div.entry {
  $height: 64px;
  $image-width: $height * 2 / 3;
  $padding: 0.2em;
  height: $height;
  text-align: left;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;

  &:hover {
    background: var(--background-color-3);
  }

  &:active {
    color: var(--active-color);
  }

  > div {
    display: inline-block;
    box-sizing: border-box;
    padding: $padding;

    &.img {
      width: $image-width;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;

      > img {
        max-width: 100%;
        max-height: 100%;
      }
    }

    &.details {
      flex-shrink: 1;
      overflow: hidden;

      > span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;

        &.id {
          font-style: italic;
          font-size: 90%;
        }

        &.name {
          font-weight: bold;
        }

        &.import {
          font-size: 75%;
          opacity: 60%;
          font-style: italic;
        }
      }
    }
  }
}
</style>
