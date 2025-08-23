<script>
import {toLink} from "@/utils.js";

export default {
    name: "CoursesView",
    methods: {
        toLink,
        getRussianLessons (lessons) {
            if (lessons === 1) return 'урок'
            else if (lessons > 1 && lessons < 5) return 'урока'
            else return 'уроков'
        },
    },
    data () {
        return {
            gradients: {
                '1': 'linear-gradient(-135deg, #FFFFFF 0%, #F7F5FF 34%, #639AFF 100%)',
                '2': 'linear-gradient(-135deg, #FFFFFF 0%, #F7F5FF 34%, #63FFCE 100%)',
                '3': 'linear-gradient(-135deg, #FFFFFF 0%, #F7F5FF 34%, #F84040FF 100%)',
            },
            search: '',
            isAsc: true,
        }
    },
    computed: {
        user() {
            return this.$store.state.user;
        },
    },
}
</script>

<template>
    <div class="courses">
        <div class="courses_search">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M13.24 13.2401C15.58 10.9001 15.58 7.10012 13.24 4.75012C10.9 2.41012 7.1 2.41012 4.75 4.75012C2.41 7.09012 2.41 10.8901 4.75 13.2401C7.09 15.5801 10.89 15.5801 13.24 13.2401Z" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.5 13.5L21 21" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input placeholder="Поиск" v-model="search">
        </div>
        <div class="courses_title">
            <div>Список тем</div>
            <svg @click="isAsc = !isAsc" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M4 18H8C8.55 18 9 17.55 9 17C9 16.45 8.55 16 8 16H4C3.45 16 3 16.45 3 17C3 17.55 3.45 18 4 18ZM3 7C3 7.55 3.45 8 4 8H20C20.55 8 21 7.55 21 7C21 6.45 20.55 6 20 6H4C3.45 6 3 6.45 3 7ZM4 13H14C14.55 13 15 12.55 15 12C15 11.45 14.55 11 14 11H4C3.45 11 3 11.45 3 12C3 12.55 3.45 13 4 13Z" fill="#1E1E22"/>
            </svg>
        </div>
        <div v-if="!user.courses?.length">Тут пока что ничего нет...</div>
        <div class="courses_main" :style="{'flex-direction': isAsc ? 'column' : 'column-reverse'}">
            <div :style="{background: gradients[course.level]}" :class="{'inactive': user.courses.find(c => c.id === course.required_course)?.progress < 100}"
                 @click="user.courses.find(c => c.id === course.required_course)?.progress < 100 ? null : toLink('theme', course.id)"
                 v-for="course in user.courses?.filter(c => c.title.toLowerCase().trim().replace(/\s{2,}/g, ' ').includes(search.toLowerCase().trim().replace(/\s{2,}/g, ' ')))">
                <div class="courses_main_item_dark"></div>
                <div class="courses_main_item_info">
                    <div class="courses_main_item_info_name">{{ course.title }}</div>
                    <div class="courses_main_item_info_meta">
                        <div class="courses_main_item_info_meta_lessons">{{ course.lessons.length }} {{ getRussianLessons(course.lessons.length) }}</div>
                        <div class="courses_main_item_info_meta_level">{{ user.levels[course.level] }}</div>
                    </div>
                </div>
                <div class="courses_main_item_progress">
                    <svg v-if="user.courses.find(c => c.id === course.required_course)?.progress < 100" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none" style="z-index: 9999">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8 11.2C8 9.29044 8.84285 7.45909 10.3431 6.10883C11.8434 4.75857 13.8783 4 16 4C18.1217 4 20.1566 4.75857 21.6569 6.10883C23.1571 7.45909 24 9.29044 24 11.2H25.3333C26.0406 11.2 26.7189 11.4529 27.219 11.9029C27.719 12.353 28 12.9635 28 13.6V25.6C28 26.2365 27.719 26.847 27.219 27.2971C26.7189 27.7471 26.0406 28 25.3333 28H6.66667C5.95942 28 5.28115 27.7471 4.78105 27.2971C4.28095 26.847 4 26.2365 4 25.6V13.6C4 12.9635 4.28095 12.353 4.78105 11.9029C5.28115 11.4529 5.95942 11.2 6.66667 11.2H8ZM16 6.4C17.4145 6.4 18.771 6.90571 19.7712 7.80589C20.7714 8.70606 21.3333 9.92696 21.3333 11.2H10.6667C10.6667 9.92696 11.2286 8.70606 12.2288 7.80589C13.229 6.90571 14.5855 6.4 16 6.4ZM18.6667 18.4C18.6667 18.8213 18.5434 19.2351 18.3094 19.6C18.0753 19.9648 17.7387 20.2678 17.3333 20.4784V22C17.3333 22.3183 17.1929 22.6235 16.9428 22.8485C16.6928 23.0736 16.3536 23.2 16 23.2C15.6464 23.2 15.3072 23.0736 15.0572 22.8485C14.8071 22.6235 14.6667 22.3183 14.6667 22V20.4784C14.1583 20.2142 13.761 19.8065 13.5364 19.3184C13.3118 18.8303 13.2724 18.2891 13.4243 17.7788C13.5762 17.2685 13.911 16.8176 14.3767 16.496C14.8424 16.1744 15.413 16 16 16C16.7072 16 17.3855 16.2529 17.8856 16.7029C18.3857 17.153 18.6667 17.7635 18.6667 18.4Z" fill="#1E1E22"/>
                    </svg>
                    <svg v-else-if="course.progress === 100" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <circle cx="16" cy="16" r="16" fill="#5AD000"/>
                        <path d="M32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0C24.8366 0 32 7.16344 32 16ZM3.2 16C3.2 23.0692 8.93075 28.8 16 28.8C23.0692 28.8 28.8 23.0692 28.8 16C28.8 8.93075 23.0692 3.2 16 3.2C8.93075 3.2 3.2 8.93075 3.2 16Z" fill="#F5F5F5"/>
                        <path d="M32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0C24.8366 0 32 7.16344 32 16ZM3.2 16C3.2 23.0692 8.93075 28.8 16 28.8C23.0692 28.8 28.8 23.0692 28.8 16C28.8 8.93075 23.0692 3.2 16 3.2C8.93075 3.2 3.2 8.93075 3.2 16Z" fill="#3BAE2C"/>
                        <path d="M11 16.5L14.1914 19.963C14.6584 20.4697 15.4859 20.3633 15.8096 19.755L21 10" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <div v-else :style="{background: `conic-gradient(
                                                #FF7F00 0deg ${Math.round(course.progress / 100 * 360)}deg,
                                                #F5F5F5 ${Math.round(course.progress / 100 * 360)}deg 360deg
                                        )`}"
                    >
                        <div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>