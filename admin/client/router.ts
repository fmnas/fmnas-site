import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import Index from './views/Home.vue'
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
    component: () => import('./views/Listing.vue'),
  },
  {
    path: '/:species?',
    component: Index,
  },
  {
    path: '/:species/:pet',
    component: () => import('./views/Listing.vue'),
  },
  // {
  //   path: '/:pathMatch(.*)',
  //   redirect: (to) => r404(to.fullPath),
  // },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
