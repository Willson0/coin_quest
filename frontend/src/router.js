import {createRouter, createWebHistory} from 'vue-router';
import MainView from "@/views/MainView.vue";
import AdminLoginView from "@/views/admin/adminLoginView.vue";
import AdminView from "@/views/admin/adminView.vue";
import AdminUsersView from "@/views/admin/adminUsersView.vue";
import AdminUserIndexView from "@/views/admin/adminUserIndexView.vue";
import adminCoursesView from "@/views/admin/adminCoursesView.vue";
import adminPostsView from "@/views/admin/adminPostsView.vue";
import adminTournamentsView from "@/views/admin/AdminTournamentsView.vue";
import adminCurrenciesView from "@/views/admin/adminCurrenciesView.vue";
import adminAchievementsView from "@/views/admin/adminAchievementsView.vue";
import adminFiatCurrenciesView from "@/views/admin/adminFiatCurrenciesView.vue";
import adminOrdersView from "@/views/admin/adminOrdersView.vue";
import adminSupportView from "@/views/admin/adminSupportView.vue";
import adminWhiteListView from "@/views/admin/adminWhiteListView.vue";


const routes = [
    {
        path: "/",
        component: MainView,
    },
    {
        path: "/admin/login",
        component: AdminLoginView,
        meta: { title: 'CryptoCourses | Admin\'s Authorization' },
        name: 'adminlogin'
    },
    {
        path: "/admin",
        component: AdminView,
        meta: { title: 'CryptoCourses | Admin', h: 'Дашборд' },
        name: 'admin'
    },
    {
        path: "/admin/users",
        component: AdminUsersView,
        meta: { title: 'CryptoCourses | Users', h: 'Пользователи' },
        name: 'users'
    },
    {
        path: "/admin/users/:id",
        component: AdminUserIndexView,
        meta: { title: 'CryptoCourses | User', h: 'Пользователь' },
        name: 'user'
    },
    {
        path: "/admin/courses",
        component: adminCoursesView,
        meta: { title: 'CryptoCourses | Courses', h: 'Курсы' },
        name: 'courses'
    },
    {
        path: "/admin/posts",
        component: adminPostsView,
        meta: { title: 'CryptoCourses | News', h: 'Новости' },
        name: 'posts'
    },
    {
        path: "/admin/tournaments",
        component: adminTournamentsView,
        meta: { title: 'CryptoCourses | Tournaments', h: 'Турниры' },
        name: 'tournaments'
    },
    {
        path: "/admin/currencies",
        component: adminCurrenciesView,
        meta: { title: 'CryptoCourses | Currencies', h: 'Криптовалюты' },
        name: 'currencies'
    },
    {
        path: "/admin/achievements",
        component: adminAchievementsView,
        meta: { title: 'CryptoCourses | Achievements', h: 'Достижения' },
        name: 'achievements'
    },
    {
        path: "/admin/fiats",
        component: adminFiatCurrenciesView,
        meta: { title: 'CryptoCourses | Fiat', h: 'Реальная валюта' },
        name: 'fiat'
    },
    {
        path: "/admin/orders",
        component: adminOrdersView,
        meta: { title: 'CryptoCourses | Orders', h: 'Ордеры' },
        name: 'orders'
    },
    {
        path: "/admin/support",
        component: adminSupportView,
        meta: { title: 'CryptoCourses | Support', h: 'Поддержка' },
        name: 'support'
    },
    {
        path: "/admin/whitelist",
        component: adminWhiteListView,
        meta: { title: 'CryptoCourses | WhiteList', h: 'Белый список' },
        name: 'whitelist'
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router;