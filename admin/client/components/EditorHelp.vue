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
  <article class="help">
    <h2>Formatting help</h2>
    <section>
      <h3>Text formatting</h3>
      <p>Text can be formatted with GitHub Flavored Markdown and/or HTML.</p>
      <p>Some useful examples include:</p>
      <ul>
        <li>
          <h4>Bold and italic</h4>
          <code>**bold text**, *italic text*,<br>***bold and italic text***</code>
          <p/>
          <div><strong>bold text</strong>, <em>italic text</em>, <strong><em>bold and italic text</em></strong></div>
        </li>
        <li>
          <h4>Links</h4>
          <code>[click for google](<wbr>https://google.com)</code>
          <p/>
          <div><a href="https://google.com">click for google</a></div>
        </li>
        <li>
          <h4>Lists</h4>
          <code v-html="`* List item 1
* List item 2
  * Indented list item

1. List item 1
2. List item 2
  * Indented list item`"/>
          <p>(Indent with two spaces.)</p>
          <div>
            <ul>
              <li>
                List item 1
              </li>
              <li>
                List item 2
                <ul>
                  <li>Indented list item</li>
                </ul>
              </li>
            </ul>
            <ol>
              <li>
                List item 1
              </li>
              <li>
                List item 2
                <ul>
                  <li>Indented list item</li>
                </ul>
              </li>
            </ol>
          </div>
        </li>
        <li>
          <h4>Strikethrough</h4>
          <code>~~strikethrough text~~</code>
          <p/>
          <div><s>strikethrough text</s></div>
        </li>
      </ul>
      <p>For complete references, see:<br>
        <a href="https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax">GFM reference</a><br>
        <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Reference">HTML reference</a>
      </p>
    </section>
    <section>
      <h3>Templates</h3>
      <p>You can include templates within the description for common text and other elements.</p>
      <p>Include a template with: <code v-html="'{{>template_name}}'"/></p>
      <p>You can add additional parameters to templates that support them: <code
          v-html="'{{>template_name parameter=\'value\'}}'"/></p>
      <p>The available templates include:</p>
      <ul>
      <li>
        <h4>Coming Soon</h4>
        <code v-html="'{{>coming_soon}}'"/>
        <p>Must be the first line of the description. This marks the listing as "coming soon" and prevents it from
        being linked from the adoptable page.</p>
        <code v-html="partial('coming_soon').trim()"/>
      </li>
        <li>
          <h4>YouTube Video</h4>
          <code v-html="'{{>youtube id=\'eOTxUmcGXKA\'}}'"/>
          <p>Embeds a YouTube video in the listing. <strong>Requires</strong> the <code>id</code> parameter.</p>
        </li>
      <li>
        <h4>Standard Info</h4>
        <code v-html="'{{>standard_info}}<br>{{>standard_info fee=\'$40\'}}<br>{{>standard_info flavor=\' extra text\'}}'"/>
        <p>This is the standard info included at the bottom of most descriptions.</p>
        <code v-html="partial('standard_info').trim()"/>
      </li>
      <li>
        <h4>Senior Info</h4>
        <code v-html="'{{>senior_info fee=\'$20\'}}'"/>
        <p>This is a special case of <code v-html="'{{>standard_info}}'"/> that includes info about Senior for Senior.
          <strong>Requires</strong> the <code>fee</code> parameter.</p>
        <code v-html="partial('senior_info').trim()"/>
      </li>
      <li>
        <h4>Single Kitten Info</h4>
        <code v-html="'{{>single_kitten}}'"/>
        <code v-html="partial('single_kitten').trim()"/>
      </li>
      <li>
        <h4>Puppy Info</h4>
        <code v-html="'{{>puppy_info}}'"/>
        <code v-html="partial('puppy_info').trim()"/>
      </li>
    </ul>
    </section>
  </article>
</template>

<script lang="ts">
import {defineComponent} from 'vue';
import {partial} from '../common';

export default defineComponent({
  name: 'EditorHelp',
  methods: {
    partial,
  }
});
</script>

<style scoped lang="scss">
h2 {
  display: none;
}

article {
  display: block;
}

section {
  display: inline-block;
  text-align: left;
  width: 50%;
  margin: 0;
  height: calc(95vh - 5em);
  overflow: auto;
  padding: 0 0.5rem;
  box-sizing: border-box;
}

h3 {
  text-align: center;
  margin: 0 0 0.5rem;
  font: var(--heading-font);
  color: var(--accent-color);
}

code {
  white-space: pre-wrap;
  background-color: #f0f0f0;
  font-size: 10pt;
  padding: 0.4em;
  margin: -0.2em 0;
  display: inline-block;
  border-radius: var(--border-radius);
  line-height: 1.1em;
  box-sizing: border-box;
}
section > ul {
  list-style-type: none;
  padding: 0;
  margin: 0;
  ul {
    list-style-type: disc;
  }
  > li {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: min-content min-content max-content;
    grid-column-gap: 1rem;
    align-items: start;
    justify-items: start;
    > h4 {
      grid-column: 1/span 2;
      grid-row: 1;
      text-align: center;
      font: var(--heading-font);
      margin: 0.4rem auto;
      + code {
        grid-column: 1;
        grid-row: 2;
        + p {
          grid-column: 1;
          grid-row: 3;
          font-style: italic;
          font-size: 10pt;
          width: 100%;
          + * {
            grid-column: 2;
            grid-row: 2 / span 3;
          }
          + code {
            font-size: 9pt;
          }
        }
        &.span {
          grid-column-end: span 2;
          margin-left: auto;
          margin-right: auto;
          & + p {
            grid-column-end: span 2;
          }
        }
      }
    }
  }
}
</style>
