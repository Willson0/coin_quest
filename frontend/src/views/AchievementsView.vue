<script>
import config from "@/config.json";
import axios from "axios";
import {notify} from "@/utils.js";
export default {
    name: "AchievementsView",
    data () {
        return {
            isAsc: true,
            config: config,
            isTooltip: 0,
        }
    },
    methods: {
        getRussianLesson (count) {
            if (count === 1) return 'урок';
            else if (count === 2 || count === 3 || count === 4) return 'урока';
            else return 'уроков';
        },
        showTooltip (ev, idx) {
            if (this.isTooltip) return this.isTooltip = 0;

            this.isTooltip = idx;
            let el = this.$refs.tooltip;
            let target = ev.target.closest('svg');

            el.style.right = (window.innerWidth - target.getBoundingClientRect().right) + 'px';
            el.style.top = target.getBoundingClientRect().bottom + 'px';

            const onClick = (e) => {
                if (!el.contains(e.target)) {
                    this.isTooltip = 0;
                    window.removeEventListener('click', onClick);
                }
            };
            setTimeout(() => {
                window.addEventListener('click', onClick);
            }, 0);
        },
        async pin (id) {
            await axios.post(config.backend + `achievement/${id}/pin`, {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                let newUser = {...this.user, pinned_achievements: response.data};
                this.$store.dispatch("updateUser", newUser);
                notify("Успешно закреплено");
            }).catch((error) => {
                notify(error.response.data.message, 1)
            })
        },
        async unpin (id) {
            await axios.post(config.backend + `achievement/${id}/unpin`, {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                let newUser = {...this.user, pinned_achievements: response.data};
                this.$store.dispatch("updateUser", newUser);
                notify("Успешно откреплено");
            }).catch((error) => {
                notify(error.response.data.message, 1)
            })
        }
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
        countLesson () {
            let counter = 0;
            for (let course of this.user.courses) {
                for (let lesson of course.lessons) {
                    if (lesson.count_tries > 0 && lesson.user_points >= 50) counter++;
                    else if (lesson.count_tries === 0 && lesson.user_points != null) counter++;
                }
            }
            return counter;
        }
    }
}
</script>

<template>
    <div class="achievements_tooltip" ref="tooltip" v-show="isTooltip !== 0">
        <div class="achievements_tooltip_pin" @click="pin(isTooltip)" v-if="!user.pinned_achievements?.includes(isTooltip)">Закрепить на витрине</div>
        <div class="achievements_tooltip_pin" @click="unpin(isTooltip)" v-else>Открепить от витрины</div>
        <hr>
        <div class="achievements_tooltip_share">Поделиться</div>
    </div>
    <div class="achievements">
        <div class="achievements_title">
            <div>Достижения</div>
            <svg @click="isAsc = !isAsc" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M4 18H8C8.55 18 9 17.55 9 17C9 16.45 8.55 16 8 16H4C3.45 16 3 16.45 3 17C3 17.55 3.45 18 4 18ZM3 7C3 7.55 3.45 8 4 8H20C20.55 8 21 7.55 21 7C21 6.45 20.55 6 20 6H4C3.45 6 3 6.45 3 7ZM4 13H14C14.55 13 15 12.55 15 12C15 11.45 14.55 11 14 11H4C3.45 11 3 11.45 3 12C3 12.55 3.45 13 4 13Z" fill="#1E1E22"/>
            </svg>
        </div>
        <div class="achievements_main">
            <div v-for="ach in user.achievements?.sort((a, b) => isAsc ? a.id - b.id : b.id - a.id)" v-show="countLesson >= ach.progress">
                <img :src="config.storage + ach.image" alt="">
                <div class="achievements_main_info">
                    <div class="achievements_main_info_title">
                        {{ ach.title }}
                        <svg v-if="user.pinned_achievements?.includes(ach.id)" xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17" fill="none">
                            <path d="M6.32869 9.88599L1.99536 14.2193" stroke="#B963FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3.32861 6.54267L9.45728 12.6713L10.5886 11.54L10.3266 9.01533L13.9999 5.9L10.0999 2L6.98395 5.67333L4.45995 5.41133L3.32861 6.54267Z" fill="#B963FF" stroke="#B963FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="achievements_main_info_description">Пройдите {{ ach.progress }} {{ getRussianLesson(ach.progress) }}</div>
                </div>
                <svg @click="showTooltip($event, ach.id)" xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="none">
                    <circle cx="10" cy="18" r="2" fill="#1E1E22"/>
                    <circle cx="18" cy="18" r="2" fill="#1E1E22"/>
                    <circle cx="26" cy="18" r="2" fill="#1E1E22"/>
                </svg>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>