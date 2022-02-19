<template>
  <img :src="localPath ?? (photo ? `/api/raw/cached/${photo.key}_300.jpg` : null)"
      :alt="photo ? 'Edit profile image' : 'Add profile image'"
      :title="photo ? 'Edit profile image' : 'Add profile image'" @click="$refs.input.click()">
  <input type="file" ref="input" @change="consume()" accept="image/*">
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue';
import {Asset} from '../types';
import {uploadFile} from '../common';
import {Md5} from 'ts-md5';

export default defineComponent({
  name: 'ProfilePhoto',
  props: {
    modelValue: {
      type: Object as PropType<Asset>,
      required: false
    },
    promise: {
      type: Object as PropType<Promise<Asset>>,
      required: false
    },
    prefix: {
      type: String,
      required: false
    },
    reset: {
      type: Number,
      required: false,
    },
    base64: {
      type: String,
      required: false,
    },
    type: {
      type: String,
      required: false,
    }
  },
  data() {
    return {
      localPath: null as string | null,
    };
  },
  watch: {
    reset() {
      // Reset local path on parent reset
      this.localPath = null;
    },
    base64() {
      if (this.base64) {
        this.uploadBase64();
      }
    },
  },
  created() {
    if (this.base64) {
      this.uploadBase64();
    }
  },
  emits: ['update:modelValue', 'update:promise', 'update:base64'],
  computed: {
    photo: {
      get(): Asset | undefined {
        return this.modelValue;
      },
      set(value: Asset | undefined): void {
        this.$emit('update:modelValue', value);
      },
    },
    prom: {
      get(): Promise<Asset> | undefined {
        return this.promise;
      },
      set(value: Promise<Asset>): void {
        this.$emit('update:promise', value);
      },
    },
    blob: {
      get(): string | undefined {
        return this.base64;
      },
      set(value: string | undefined): void {
        this.$emit('update:base64', value);
      },
    }
  },
  methods: {
    consume(): void {
      const input = this.$refs.input as HTMLInputElement;
      if (input.files?.[0]) {
        this.upload(input.files[0]);
      }
      input.value = '';
      input.files = null;
    },
    upload(file: File): void {
      console.log('uploading profile photo', file);
      const localPath = URL.createObjectURL(file);
      this.localPath = localPath;
      this.blob = undefined;
      this.prom = uploadFile(file, this.prefix, 300).then((asset) => {
        if (this.localPath === localPath) {
          this.photo = asset;
        }
        return asset;
      });
    },
    async uploadBase64() {
      if (!this.blob) {
        this.localPath = null;
        return;
      }
      const dataURL = `data:${this.type ?? 'image/jpeg'};base64,${this.blob}`;
      const res = await fetch(dataURL);
      const blob = await res.blob();
      this.upload(new File([blob], `${Md5.hashStr(this.blob)}.jpg`, {type: this.type ?? 'image/jpeg'}));
    }
  },
});
</script>

<style scoped lang="scss">
input {
  display: none;
}

/* Make a missing profile image seem like a link */
img {
  vertical-align: center;
  --stripe-1-color: var(--background-color);
  --stripe-2-color: var(--background-color-2);
  --plus-url: url('/plus.svg.php?color=066');

  &:not([src]) {
    line-height: 318px;
    box-sizing: border-box;
    color: var(--link-color);
    font-weight: bold;
    cursor: pointer;
    margin-top: 2px;
    width: 400px !important;
    height: 300px !important;

    &:not(.pair a>img) {
      width: 200px !important;
    }
  }

  &::before {
    display: block;
    width: 100%;
    height: 100%;
  }

  &:not([src]), &::before, &:hover {
    outline: 2px dashed var(--link-color);
    background-image: var(--plus-url), linear-gradient(135deg, var(--stripe-1-color) 25%, var(--stripe-2-color) 25%, var(--stripe-2-color) 50%, var(--stripe-1-color) 50%, var(--stripe-1-color) 75%, var(--stripe-2-color) 75%, var(--stripe-2-color) 100%);
    background-size: 20px 20px;
    background-repeat: no-repeat, repeat;
    background-position: bottom 152px center, center;
    background-clip: padding-box;
  }

  &:hover {
    text-decoration: underline;
  }

  &:active, &:active::before {
    color: var(--active-color);
    outline-color: var(--active-color);
    --plus-url: url('/plus.svg.php?color=f60');
  }
}
</style>
