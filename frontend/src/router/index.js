import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Default Route
    {
      path: "/",
      name: "default",
      component: () => import("@/views/auth/Login.vue"),
    },
    // Auth Routes
    {
      path: "/login",
      name: "login",
      component: () => import("@/views/auth/Login.vue"),
    },
    {
      path: "/forgot-password",
      name: "forgot-password",
      component: () => import("@/views/auth/ForgotPassword.vue"),
    },
    {
      path: "/reset-password",
      name: "reset-password",
      component: () => import("@/views/auth/ResetPassword.vue"),
    },


    // Socio Routes
    {
      path: "/socio",
      component: () => import("@/views/layout/SocioLayout.vue"),
      meta: {
        requiresAuth: true,
        allowedRoles: ["socio_titular", "miembro_familiar"],
      },
      children: [
        {
          path: "home",
          name: "socio-home",
          component: () => import("@/views/socio/SocioHomeView.vue"),
        },

        {
          path: "reservations",
          name: "socio-reservations",
          component: () =>
            import("@/views/reservations/socio/Reservations.vue"),
          meta: {
            requiresAuth: true,
            allowedRoles: ["socio_titular", "miembro_familiar"],
          },
          children: [
            {
              path: "on-demand",
              name: "on-demand",
              component: () =>
                import("@/views/reservations/socio/OnDemand.vue"),
            },
            {
              path: "active-sessions",
              name: "active-sessions",
              component: () =>
                import("@/views/reservations/socio/ActiveSessions.vue"),
            },
            {
              path: "manage",
              name: "manage",
              component: () => import("@/views/reservations/socio/Manage.vue"),
            },
          ],
        },
        {
          path: "reservations/on-demand",
          name: "socio-reservations-on-demand",
          component: () => import("@/views/reservations/socio/OnDemand.vue"),
        },
        {
          path: "reservations/active-sessions",
          name: "socio-reservations-active-sessions",
          component: () =>
            import("@/views/reservations/socio/ActiveSessions.vue"),
        },
        {
          path: "tournaments",
          name: "socio-tournaments",
          component: () => import("@/views/socio/SocioTournamentsView.vue"),
        },
        {
          path: "guests",
          name: "socio-guests",
          component: () => import("@/views/socio/SocioGuestsView.vue"),

          children: [
            {
              path: "",
              redirect: "guests"
            },
            {
              path: "guests",
              name: "guests-guests",
              component: () => import("@/views/guest/guestLists/guestList.vue"),
            },
            {
              path: "family-members",
              name: "guests-family-members",
              component: () => import("@/views/guest/guestLists/familyMembers.vue"),
            },
            {
              path: "friends",
              name: "guests-friends",
              component: { template: "<div></div>" },
            },
            {
              path: "add",
              name: "guests-add",
              component: () => import("@/views/guest/guestLists/addGuest.vue")
            },
          ]


        },
        {
          path: "history",
          name: "socio-history",
          component: () => import("@/views/socio/SocioHistoryView.vue"),
        },
        {
          path: "profile",
          name: "socio-profile",
          component: () => import("../views/socio/SocioProfileView.vue"),
        },
        {
          path: "qr",
          name: "socio-qr",
          component: () => import("../views/socio/SocioQrView.vue"),
        },

      ],
    },

    // Instructor Routes
    {
      path: "/instructor/home",
      name: "instructor-home",
      component: () => import("../views/instructor/InstructorHomeView.vue"),
      meta: { requiresAuth: true, allowedRoles: ["instructor"] },
    },
    {
      path: '/instructor/scanner',
      name: 'instructor-scanner',
      component: () => import('../views/instructor/ScannerView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['instructor'] }
    },
    {
      path: '/instructor/profile',
      name: 'instructor-profile',
      component: () => import('../views/instructor/InstructorProfileView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['instructor'] }
    },
    {
      path: '/instructor/agenda',
      name: 'instructor-agenda',
      component: () => import('../views/instructor/InstructorAgendaView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['instructor'] }
    },
    {
      path: '/instructor/sessions',
      name: 'instructor-sessions',
      component: () => import('../views/instructor/InstructorSessionsView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['instructor'] }
    },

    // Admin Routes
    {
      path: '/admin',
      component: () => import('@/views/layout/GerenteLayout.vue'),
      meta: { requiresAuth: true, allowedRoles: ['gerente', 'subgerente'] },
      children: [
        {
          path: 'dashboard',
          component: () => import('@/views/admin/Dashboard.vue'),
        },
        {
          path: 'tournaments',
          component: () => import('@/views/admin/TournamentForm.vue'),
        },
        {
          path: 'reservations',
          component: () => import('@/views/admin/Reservation.vue'),
        },
        {
          path: 'spaces',
          component: () => import('@/views/admin/Spaces.vue'),
        },
        {
          path: 'instructors',
          component: () => import('@/views/admin/Instructors.vue'),
        },
        {
          path: 'ludoteca',
          component: () => import('@/views/admin/Ludoteca.vue'),
        },
        {
          path: 'reports',
          component: () => import('@/views/admin/Reports.vue'),
        },
        {
          path: 'tournaments/create',
          component: () => import('@/views/admin/CreateTournament.vue'),
        },
        {
          path: '/tournaments/details',
          component: () => import('@/views/admin/DetailsTournament.vue'),
        },
        {
          path: 'categories/create',
          component: () => import('@/views/admin/CreateCategories.vue'),
        }


      ]
    },
  ],
});

// Global Navigation Guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("auth_token");
  const userData = JSON.parse(localStorage.getItem("user_data"));

  // 1. Si la ruta a la que quiere ir requiere autenticación
  if (to.meta.requiresAuth) {
    if (!token || !userData) {
      return next("/login");
    }

    if (to.meta.allowedRoles && !to.meta.allowedRoles.includes(userData.rol)) {
      switch (userData.rol) {
        case "gerente":
        case "subgerente":
          return next("/admin/dashboard");
        case "socio_titular":
        case "miembro_familiar":
          return next("/socio/home");
        case "instructor":
          return next("/instructor/home");
        default:
          return next("/login");
      }
    }
  }

  // 2. Si ya está logueado y quiere ir al login, lo redirigimos a su dashboard correspondiente
  if (to.path === "/login" && token && userData) {
    switch (userData.rol) {
      case "gerente":
      case "subgerente":
        return next("/admin/dashboard");
      case "socio_titular":
      case "miembro_familiar":
        return next("/socio/home");
      case "instructor":
        return next("/instructor/home");
      default:
        return next();
    }
  }

  next();
});

export default router;
