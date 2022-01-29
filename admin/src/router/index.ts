/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import Index from '../views/Index.vue'
// import {r404} from '@/common'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'Home',
    component: Index
  },
  {
    name: 'new',
    path: '/new',
    component: () => import('../views/Listing.vue'),
  },
  {
    path: '/:species?',
    component: Index,
  },
  {
    path: '/:species/:pet',
    component: () => import('../views/Listing.vue'),
  },
  // {
  //   path: '/:pathMatch(.*)',
  //   redirect: (to) => r404(to.fullPath),
  // },
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

export default router
