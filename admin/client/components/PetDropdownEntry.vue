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
  <div class="entry">
    <div class="img">
      <img :src="`/api/raw/stored/${pet.photo?.key}`" alt="">
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
    $height: 0.5in;
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
