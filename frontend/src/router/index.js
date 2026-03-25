import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/login'
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView
    },
    {
      path: '/admin/dashboard',
      name: 'gerencia-dashboard',
      component: () => import('../views/AdminDashboard.vue')
    },
    {
      path: '/socio/home',
      name: 'socio-home',
      component: () => import('../views/SocioHome.vue')
    },
    {
      path: '/instructor/scanner',
      name: 'instructor-scanner',
      component: () => import('../views/InstructorHome.vue')
    },
    {
      path: '/instructor/home',
      name: 'instructor-home',
      component: () => import('../views/InstructorHome.vue')
    },
    {
      path: '/socio/profile',
      name: 'perfil-socio',
      component: () => import('../views/ProfileView.vue')
    }
  ]
})

export default router