// router/index.js - Defines all application routes and navigation guards

import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Layout from '@/components/Layout.vue'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { requiresGuest: true }  // Only guests can access this page
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/Register.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/',
    // All routes under '/' will use the Layout component as a wrapper
    component: Layout,
    meta: { requiresAuth: true },  // Authentication required
    children: [
      {
        path: '',          // Empty path means the default child route at '/'
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue')
      },
      {
        path: '/students',
        name: 'Students',
        component: () => import('@/views/students/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/teachers',
        name: 'Teachers',
        component: () => import('@/views/teachers/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/grades',
        name: 'Grades',
        component: () => import('@/views/grades/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/sections',
        name: 'Sections',
        component: () => import('@/views/sections/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/subjects',
        name: 'Subjects',
        component: () => import('@/views/subjects/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/teacher-assignments',
        name: 'TeacherAssignments',
        component: () => import('@/views/teacher-assignments/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/schedules',
        name: 'Schedules',
        component: () => import('@/views/schedules/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/periods',
        name: 'Periods',
        component: () => import('@/views/periods/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/attendance/mark',
        name: 'MarkAttendance',
        component: () => import('@/views/attendance/Mark.vue'),
        meta: { requiresAuth: true, roles: [2] }, // teacher only
      },
      {
        path: '/attendance/history/:studentId?',
        name: 'AttendanceHistory',
        component: () => import('@/views/attendance/History.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/reports/attendance',
        name: 'AttendanceReport',
        component: () => import('@/views/reports/Attendance.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/notifications',
        name: 'Notifications',
        component: () => import('@/views/notifications/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/notifications/send',
        name: 'SendNotification',
        component: () => import('@/views/notifications/Send.vue'),
        meta: { requiresAuth: true, adminOnly: true },
      },
      {
        path: '/calendar',
        name: 'Calendar',
        component: () => import('@/views/calendar/Index.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/guardians',
        name: 'Guardians',
        component: () => import('@/views/guardians/Index.vue'),
        meta: { requiresAuth: true, adminOnly: true },
      },
      {
        path: '/my-children',
        name: 'MyChildren',
        component: () => import('@/views/guardians/Children.vue'),
        meta: { requiresAuth: true, parentOnly: true },
      },
      {
        path: '/guardians/:guardianId/children',
        name: 'GuardianChildren',
        component: () => import('@/views/guardians/Children.vue'),
        meta: { requiresAuth: true },
        props: true
      },
      {
        path: '/profile',
        name: 'Profile',
        component: () => import('@/views/Profile.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: '/settings',
        name: 'Settings',
        component: () => import('@/views/admin/Settings.vue'),
        meta: { requiresAuth: true, adminOnly: true }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard – runs before every route change
router.beforeEach((to, from) => {
  const authStore = useAuthStore()
  const isAuthenticated = authStore.isAuthenticated

  if (to.meta.requiresAuth && !isAuthenticated) return '/login'
  if (to.meta.requiresGuest && isAuthenticated) return '/'

  const role = authStore.userRole
  if (to.meta.roles && !to.meta.roles.includes(role)) return '/'
  if (to.meta.adminOnly && role !== 1) return '/'
  if (to.meta.parentOnly && role !== 4) return '/'

  return true
})

export default router