import { createRouter, createWebHistory } from "vue-router";
import { useProfileStore } from '@/stores/profiles/socioStore'

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
          path: "agenda",
          name: "personal-schedule",
          component: () => import("@/views/reservations/socio/SocioAgendaView.vue"),
        },
        {
          path: "reservations",
          name: "reservation-on-demand",
          component: () => import("@/views/reservations/socio/SpaceReservationsHub.vue"),
          meta: {
            requiresAuth: true,
            allowedRoles: ["socio_titular", "miembro_familiar"],
          },
          beforeEnter: async (to, from) => {
            const profileStore = useProfileStore();

            if (!profileStore.profileData) {
              try {
                await profileStore.fetchProfile();
              } catch (error) {
                console.error("Error cargando el store desde el router", error);
              }
            }

            if (profileStore.isAccountInactive) {
              return "/socio/home";
            }
          },
        },
        {
          path: "classes",
          name: "programmed-activities",
          component: () => import("@/views/reservations/socio/ClassReservationsHub.vue"),
        },


        {
          path: "tournaments",
          name: "socio-tournaments",
          component: () => import("@/views/socio/SocioTournamentsView.vue"),
        },
        {
          path: "socio-ludoteca",
          name: "socio-ludoteca",
          redirect: { name: "ludoteca-list" },
          component: () => import("@/views/ludoteca/LudotecaSocio.vue"),
          children: [
            {
              path: "ludoteca-list",
              name: "ludoteca-list",
              component: () => import("@/views/ludoteca/List/LudotecaList.vue"),
            },
            {
              path: "add-register",
              name: "add-register",
              component: () => import("@/views/ludoteca/AddRegister.vue"),


            },
            {
              path: "ludoteca-list",
              name: "ludoteca-list",
              component: () => import("@/views/ludoteca/List/LudotecaList.vue"),
            },

          ]
        },


        {
          path: "community",
          name: "socio-guests",
          component: () => import("@/views/socio/SocioCommunityView.vue"),
          redirect: '/socio/community/guests-list',
          children: [
            // CRUD GUESTS
            {
              path: "guests-list",
              name: "guests-list",
              component: () => import("@/views/community/GuestList.vue"),
            },
            {
              path: "guests-add",
              name: "guests-add",
              component: () => import("@/views/community/AddGuest.vue"),
            },

            // CRUD FAMILY MEMBERS
            // -- SHOW
            {
              path: "family-members-list",
              name: "family-members-list",
              component: () => import("@/views/community/FamilyMembersList.vue"),
            },
            // -- CREATE
            {
              path: "family-members-add",
              name: "family-members-add",
              component: () => import("@/views/community/AddFamilyMember.vue"),
            },



            // CRUD FRIENDS

            {
              // PENDIENTE
              path: "friends-list",
              name: "friends-list",
              component: () => import("@/views/community/friends/FriendsList.vue"),
            },
            {
              path: "friends-add",
              name: "friends-add",
              component: () => import("@/views/community/friends/FriendsAdd.vue"),
            },
          ],
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
          beforeEnter: async (to, from) => {
            const profileStore = useProfileStore();

            if (!profileStore.profileData) {
              try {
                await profileStore.fetchProfile();
              } catch (error) {
                console.error("Error cargando el store desde el router", error);
              }
            }

            if (profileStore.isAccountInactive) {
              return "/socio/home";
            }
          },
        },
      ],
    },

    // Instructor Routes
    {
      path: '/instructor',
      component: () => import('@/views/layout/InstructorLayout.vue'),
      meta: { requiresAuth: true, allowedRoles: ['instructor'] },
      children: [
        {
          path: "home",
          component: () => import("../views/instructor/InstructorHomeView.vue"),
        },
        {
          path: 'scanner',
          component: () => import('../views/instructor/ScannerView.vue'),
        },
        {
          path: 'profile',
          component: () => import('../views/instructor/InstructorProfileView.vue'),
        },
        {
          path: 'agenda',
          component: () => import('../views/instructor/InstructorAgendaView.vue'),
        },
        {
          path: 'sessions',
          component: () => import('../views/instructor/InstructorSessionsView.vue'),
        },
        {
          path: 'sessions/:id',
          component: () => import('../views/instructor/SessionDetails.vue'),
        },
        {
          path: 'scanner/:id',
          component: () => import('../views/instructor/ScannerView.vue'),
        },
      ]
    },

    // Admin Routes
    {
      path: "/admin",
      component: () => import("@/views/layout/GerenteLayout.vue"),
      meta: { requiresAuth: true, allowedRoles: ["gerente", "subgerente"] },
      children: [
        {
          path: "dashboard",
          component: () => import("@/views/admin/Dashboard.vue"),
        },
        {
          path: "tournaments",
          component: () => import("@/views/admin/TournamentForm.vue"),
        },
        {
          path: "reservations",
          component: () => import("@/views/admin/Reservation.vue"),
        },
        {
          path: "spaces",
          component: () => import("@/views/admin/Spaces.vue"),
        },
        {
          path: "socios",
          component: () => import("@/views/admin/socio/SociosList.vue"),
        },
        {
          path: "socios/:id",
          component: () => import("@/views/admin/socio/SocioDetails.vue"),
        },
        {
          path: "instructors",
          component: () => import("@/views/admin/instructors/Instructors.vue"),
        },
        {
          path: 'instructors/:id',
          component: () => import('@/views/admin/instructors/InstructorsDetails.vue'),
        },
        {
          path: 'instructors/:id/disciplines',
          name: 'instructor-disciplines',
          component: () => import('@/views/admin/instructors/InstructorDisciplines.vue'),
        },
        {
          path: 'instructors/:id/status',
          name: 'instructor-status',
          component: () => import('@/views/admin/instructors/InstructorStatus.vue'),
        },
        {
          path: 'ludoteca',
          component: () => import('@/views/ludoteca/LudotecaAdmin.vue'),
          redirect: { name: 'admin-ludoteca-list' }, // 🔥 ESTO FALTABA
          children: [
            {
              path: 'ludoteca-list',
              name: 'admin-ludoteca-list',
              component: () => import('@/views/ludoteca/List/LudotecaList.vue'),
            },
            {
              path: 'update',
              name: 'ludoteca/update',
              component: () => import('@/views/ludoteca/LudotecaUpdate.vue')
            }
          ]
        },
        {
          path: "reports",
          component: () => import("@/views/admin/Reports.vue"),
        },
        {
          path: "tournaments/create",
          component: () => import("@/views/admin/CreateTournament.vue"),
        },
        {
          path: "/tournaments/details",
          component: () => import("@/views/admin/DetailsTournament.vue"),
        },
        {
          path: "categories/create",
          component: () => import("@/views/admin/CreateCategories.vue"),
        },
      ],
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
