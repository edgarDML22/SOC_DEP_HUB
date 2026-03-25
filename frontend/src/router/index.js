import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'
import ResetPasswordView from '../views/ResetPasswordView.vue'

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
      component: () => import('../views/AdminDashboard.vue'),
      // Metadatos para proteger la ruta
      meta: { requiresAuth: true, allowedRoles: ['gerente', 'subgerente'] }
    },
    {
      path: '/socio/home',
      name: 'socio-home',
      component: () => import('../views/SocioHome.vue'),
      meta: { requiresAuth: true, allowedRoles: ['socio_titular', 'miembro_familiar'] }
    },
    {
      path: '/instructor/home',
      name: 'instructor-home',
      component: () => import('../views/InstructorHome.vue'),
      meta: { requiresAuth: true, allowedRoles: ['instructor'] }
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('../views/ForgotPasswordView.vue')
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('../views/ResetPasswordView.vue')
    }
  ]
})

// Global Navigation Guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token');
  const userData = JSON.parse(localStorage.getItem('user_data'));

  // 1. Si la ruta a la que quiere ir requiere autenticación
  if (to.meta.requiresAuth) {

    // Si no tiene token, patada de regreso al login
    if (!token || !userData) {
      return next('/login');
    }

    // Si tiene token pero su rol NO está en la lista de permitidos para esa vista
    if (to.meta.allowedRoles && !to.meta.allowedRoles.includes(userData.rol)) {
      // Lo mandamos a su propia vista por metiche
      switch (userData.rol) {
        case 'gerente':
        case 'subgerente':
          return next('/admin/dashboard');
        case 'socio_titular':
        case 'miembro_familiar':
          return next('/socio/home');
        case 'instructor':
          return next('/instructor/home');
        default:
          return next('/login');
      }
    }
  }

  if (to.path === '/login' && token && userData) {
    switch (userData.rol) {
      case 'gerente':
      case 'subgerente':
        return next('/admin/dashboard');
      case 'socio_titular':
      case 'miembro_familiar':
        return next('/socio/home');
      case 'instructor':
        return next('/instructor/home');
      default:
        return next();
    }
  }

  next();
});

export default router