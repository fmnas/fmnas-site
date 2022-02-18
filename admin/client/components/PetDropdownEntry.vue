<template>
  <div class="entry">
    <div class="img">
      <img :src="`/api/raw/cached/${pet.photo?.key}_64.jpg`" alt="">
    </div>
    <div class="details">
      <span class="id">{{ pet.id }}</span>
      <span class="name">{{ pet.name }}</span>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue';
import {Pet} from '../types';

export default defineComponent({
  name: 'PetDropdownEntry',
  props: {
    pet: {
      type: Object as PropType<Pet>,
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

    &:hover {
      background: var(--background-color-3);
    }

    &:active {
      color: var(--active-color);
    }

    > div {
      display: inline-block;
      height: 100%;
      box-sizing: border-box;
      padding: $padding;
      vertical-align: middle;

      &.img {
        width: $image-width;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        > img {
          max-width: 100%;
          max-height: 100%;
        }
      }

      &.details {
        > span {
          display: block;

          &.id {
            font-style: italic;
            font-size: 90%;
          }

          &.name {
            font-weight: bold;
          }
        }
      }
    }
  }
</style>
