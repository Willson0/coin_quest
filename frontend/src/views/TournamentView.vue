<script>
import TournamentSVG from "@/components/svg/TournamentSVG.vue";
import {toLink} from "@/utils.js";

export default {
    name: "TournamentView",
    components: {TournamentSVG},
    data () {
        return {
            isFullMode: false,
            maxLength: 7,
        }
    },
    methods: {
        toLink,
        getRussianPoints (points) {
            if (points % 10 === 1 && points !== 11) return "балл";
            else if (points % 10 >= 2 && points % 10 <= 4 && (points < 10 || points > 20)) return "балла";
            else return "баллов";
        },
        getRussianLesson (lessons) {
            if (lessons % 10 === 1 && lessons !== 11) return "урок";
            else if (lessons % 10 >= 2 && lessons % 10 <= 4 && (lessons < 10 || lessons > 20)) return "урока";
            else return "уроков";
        },
        back () {
            this.isFullMode = false;
        },
    },
    computed: {
        avatar () {
            return window.Telegram.WebApp.initDataUnsafe?.user?.photo_url;
        },
        toEndDate () {
            if (!this.user.tournament) return;

            let dateEnd = new Date(this.closestTournament.date_end.replace(' ', 'T') + '+03:00');
            let now = new Date();
            let diff = dateEnd.getTime() - now.getTime();

            if (diff <= 0) return "Закончен";

            let minutes = Math.floor(diff / (1000 * 60)) % 60;
            let hours = Math.floor(diff / (1000 * 60 * 60)) % 24;
            let days = Math.floor(diff / (1000 * 60 * 60 * 24));

            return `${days.toString().padStart(2, "0")}д ${hours.toString().padStart(2, "0")}ч ${minutes.toString().padStart(2, "0")}м`;
        },
        user() {
            return this.$store.state.user;
        },
        userTopIndex () {
            if (this.user.tournament) {
                return this.user.tournament.top.findIndex((item) => item.user_id === this.user.id);
            }
        },
        closestTournament () {
            if (!this.user.id) return;

            let close = 0;
            if (this.user.closest_tournament?.id) close = this.user.closest_tournament;
            else close = this.user.tournament;

            if (!close) return;

            if (close.object_id !== 0) close.object = this.user.courses.find((item) => item.id === close.object_id);
            return close;
        },
        getTournamentDate () {
            if (this.closestTournament.id === this.user.tournament.id && this.closestTournament.type !== 'lesson') {
                let ms = new Date(this.closestTournament.date_end.replace(' ', 'T') + '+03:00').getTime() - new Date(this.closestTournament.date_start.replace(' ', 'T') + '+03:00').getTime();
                let hours = Math.floor(ms / (1000 * 60 * 60));
                let minutes = Math.floor(ms / (1000 * 60)) % 60;

                return `${hours.toString().padStart(2, "0")}ч ${minutes.toString().padStart(2, "0")}м`
            }
            let startDate = new Date(this.closestTournament.date_start.replace(' ', 'T') + '+03:00');
            console.log(this.closestTournament.date_start);
            let endDate = new Date(this.closestTournament.date_end.replace(' ', 'T') + '+03:00');

            return `${startDate.getDate().toString().padStart(2, "0")}.${(startDate.getMonth() + 1).toString().padStart(2, "0")}ㅤ${startDate.getHours().toString().padStart(2, "0")}:${startDate.getMinutes().toString().padStart(2, "0")} ` +
                '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="2" viewBox="0 0 28 2" fill="none">' +
                '<path d="M0 1L28 1" stroke="#818181"/>' +
                '</svg>' + ` ${endDate.getDate().toString().padStart(2, "0")}.${(endDate.getMonth() + 1).toString().padStart(2, "0")}ㅤ${endDate.getHours().toString().padStart(2, "0")}:${endDate.getMinutes().toString().padStart(2, "0")}`
        }
    },
    unmounted () {
        window.Telegram.WebApp.BackButton.offClick(this.back);
        window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
    },
    watch: {
        isFullMode (oldValue, newValue) {
            if (this.isFullMode) {
                window.Telegram.WebApp.BackButton.offClick(window.backByQueryFunction);
                window.Telegram.WebApp.BackButton.onClick(this.back);
            } else {
                window.Telegram.WebApp.BackButton.offClick(this.back);
                window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
            }
            let poster = this.$refs.poster;
            let button = this.$refs.button;
            let closestTournament = this.$refs.closestTournament;

            if (this.isFullMode) {
                window.scrollTo({ top: 0, behavior: 'smooth' })
                poster.style.height = "0px";
                button.style.opacity = "0";
                closestTournament.style.opacity = "0";
                poster.addEventListener("transitionend", () => {
                    poster.style.display = "none";
                    button.style.display = "none";
                    closestTournament.style.display = "none";
                    this.maxLength = null;
                }, {once: true});
            } else {
                poster.style.display = "";
                button.style.display = "";
                closestTournament.style.display = "";
                window.scrollTo({ top: 0, behavior: 'smooth' })
                requestAnimationFrame(() => {
                    poster.style.height = "";
                    button.style.opacity = "";
                    closestTournament.style.opacity = "1";
                    this.maxLength = null;
                })
            }
        }
    }
}
</script>

<template>
    <div class="tournament">
        <div class="tournament_poster" ref="poster">
            <div class="tournament_poster_img">
                <div class="tournament_poster_img_blur_left"></div>
                <div class="tournament_poster_img_blur_right"></div>
                <img src="/tournament_background.webp" alt="">
            </div>
            <div class="tournament_poster_title">Турниры</div>
            <div class="tournament_poster_description">Участвуйте в турнирах, проходите тесты и темы
                на время. Зарабатывайте баллы и занимайте лидирующие позиции и получайте бонусы!</div>
        </div>
        <div class="tournament_leaders_title" style="margin-left: 20px;" v-if="!user.tournament">Тут пока что ничего нет...</div>
        <div class="tournament_leaders" v-if="user.tournament">
            <div class="tournament_leaders_title">
                <div>Лидеры</div>
                <div>{{toEndDate}}</div>
            </div>
            <div class="tournament_leaders_main" v-if="this.user.tournament">
                <div class="tournament_leaders_main_second" v-if="this.user.tournament.top.length > 1">
                    <img :src="this.user.tournament.top[1].avatar" alt="">
                    <div class="tournament_leaders_main_name">{{ this.user.tournament.top[1]?.name }}</div>
                    <div class="tournament_leaders_main_count">{{ this.user.tournament.top[1]?.points }} {{ getRussianPoints(this.user.tournament.top[1]?.points) }}</div>
                </div>
                <div class="tournament_leaders_main_first" v-if="this.user.tournament.top.length > 0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="19" viewBox="0 0 32 19" fill="none">
                        <path d="M0.189419 10.2807L4.79603 18.2754C5.05379 18.7228 5.54143 19 6.07051 19H25.9295C26.4586 19 26.9462 18.7228 27.2039 18.2754L31.8106 10.2807C32.4419 9.18501 31.3949 7.89556 30.1461 8.23084L23.8502 9.92116C23.2014 10.0954 22.5138 9.81723 22.1858 9.24798L17.2745 0.724567C16.7178 -0.241522 15.2822 -0.241522 14.7255 0.724566L9.81426 9.24798C9.48625 9.81723 8.79859 10.0954 8.14981 9.92116L1.85387 8.23083C0.605073 7.89556 -0.441934 9.18501 0.189419 10.2807Z" fill="#FFAF04"/>
                    </svg>
                    <img :src="this.user.tournament.top[0].avatar" alt="">
                    <div class="tournament_leaders_main_name">{{ this.user.tournament.top[0]?.name }}</div>
                    <div class="tournament_leaders_main_count">{{ this.user.tournament.top[0]?.points }} {{ getRussianPoints(this.user.tournament.top[0]?.points) }}</div>
                </div>
                <div class="tournament_leaders_main_third" v-if="this.user.tournament.top.length > 2">
                    <img :src="this.user.tournament.top[2].avatar" alt="">
                    <div class="tournament_leaders_main_name">{{ this.user.tournament.top[2]?.name }}</div>
                    <div class="tournament_leaders_main_count">{{ this.user.tournament.top[2]?.points }} {{ getRussianPoints(this.user.tournament.top[2]?.points) }}</div>
                </div>
                <tournament-s-v-g />
            </div>
        </div>
        <div class="tournament_you" v-if="user.tournament">
            <div>
                <div class="tournament_you_avatar">
                    <img :src="avatar" alt="">
                    <div>Вы</div>
                    <div class="tournament_you_dark"></div>
                </div>
                <div class="tournament_you_info">
                    <div class="tournament_you_info_count">{{ user.tournament?.top?.[userTopIndex]?.points ?? 0 }} баллов</div>
                    <div>{{ userTopIndex+1 }} - место в рейтинге</div>
                </div>
            </div>
        </div>
        <div class="tournament_list">
            <div v-for="(record, key) in user.tournament?.top.slice(3, maxLength)">
                <div class="tournament_list_number">{{ key+4 }}</div>
                <div class="tournament_list_user">
                    <img src="" alt="">
                    <div>{{ record.name }}</div>
                </div>
                <div class="tournament_list_count">{{ record.points }} баллов</div>
            </div>
            <button ref="button" @click="isFullMode = true" v-if="user.tournament?.top.length > 7">Смотреть всех</button>
        </div>
        <div class="tournament_closest" ref="closestTournament" v-if="closestTournament">
            <div class="tournament_closest_title">Ближайший турнир</div>
            <div v-if="closestTournament?.type === 'time'" class="tournament_closest_time"
                 :style="{'cursor': closestTournament.id === this.user.tournament.id ? 'pointer' : 'auto'}"
                @click="closestTournament.id === this.user.tournament.id ? toLink('courses') : ''">
                <div class="tournament_closest_time_titleDateContainer">
                    <div class="tournament_closest_time_title">{{ closestTournament.name }}</div>
                    <div class="tournament_closest_time_date" v-html="getTournamentDate"></div>
                </div>
                <div class="tournament_closest_time_go" v-if="closestTournament.id === this.user.tournament.id">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <circle cx="16" cy="16" r="16" fill="#B963FF"/>
                        <path d="M14 10L19.2929 15.2929C19.6834 15.6834 19.6834 16.3166 19.2929 16.7071L14 22" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
            <div v-else-if="closestTournament" :style="{'cursor': user.courses.find(c => c.id === closestTournament.object.required_course)?.progress < 100 ? 'not-allowed' : 'pointer'}"
                 @click="user.courses.find(c => c.id === closestTournament.object.required_course)?.progress < 100 ? null : toLink('theme', closestTournament.object_id)" class="tournament_closest_lesson">
                <div class="tournament_closest_lesson_info">
                    <div class="tournament_closest_lesson_info_title">{{ closestTournament.object.title }}</div>
                    <div class="tournament_closest_lesson_info_level_container">
                        <div class="tournament_closest_lesson_info_level_container_count">{{ closestTournament.object.lessons.length }} {{ getRussianLesson(closestTournament.object.lessons.length) }}</div>
                        <div class="tournament_closest_lesson_info_level_container_level">{{ user.levels[closestTournament.object.level] }}</div>
                    </div>
                    <div class="tournament_closest_lesson_info_date" v-html="getTournamentDate"></div>
                </div>
                <div v-if="user.id" class="tournament_closest_lesson_progress">
                    <svg v-if="user.courses.find(c => c.id === closestTournament.object.required_course)?.progress < 100" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none" style="z-index: 9999">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8 11.2C8 9.29044 8.84285 7.45909 10.3431 6.10883C11.8434 4.75857 13.8783 4 16 4C18.1217 4 20.1566 4.75857 21.6569 6.10883C23.1571 7.45909 24 9.29044 24 11.2H25.3333C26.0406 11.2 26.7189 11.4529 27.219 11.9029C27.719 12.353 28 12.9635 28 13.6V25.6C28 26.2365 27.719 26.847 27.219 27.2971C26.7189 27.7471 26.0406 28 25.3333 28H6.66667C5.95942 28 5.28115 27.7471 4.78105 27.2971C4.28095 26.847 4 26.2365 4 25.6V13.6C4 12.9635 4.28095 12.353 4.78105 11.9029C5.28115 11.4529 5.95942 11.2 6.66667 11.2H8ZM16 6.4C17.4145 6.4 18.771 6.90571 19.7712 7.80589C20.7714 8.70606 21.3333 9.92696 21.3333 11.2H10.6667C10.6667 9.92696 11.2286 8.70606 12.2288 7.80589C13.229 6.90571 14.5855 6.4 16 6.4ZM18.6667 18.4C18.6667 18.8213 18.5434 19.2351 18.3094 19.6C18.0753 19.9648 17.7387 20.2678 17.3333 20.4784V22C17.3333 22.3183 17.1929 22.6235 16.9428 22.8485C16.6928 23.0736 16.3536 23.2 16 23.2C15.6464 23.2 15.3072 23.0736 15.0572 22.8485C14.8071 22.6235 14.6667 22.3183 14.6667 22V20.4784C14.1583 20.2142 13.761 19.8065 13.5364 19.3184C13.3118 18.8303 13.2724 18.2891 13.4243 17.7788C13.5762 17.2685 13.911 16.8176 14.3767 16.496C14.8424 16.1744 15.413 16 16 16C16.7072 16 17.3855 16.2529 17.8856 16.7029C18.3857 17.153 18.6667 17.7635 18.6667 18.4Z" fill="#1E1E22"/>
                    </svg>
                    <svg v-else-if="closestTournament.object.progress === 100" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <circle cx="16" cy="16" r="16" fill="#5AD000"/>
                        <path d="M32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0C24.8366 0 32 7.16344 32 16ZM3.2 16C3.2 23.0692 8.93075 28.8 16 28.8C23.0692 28.8 28.8 23.0692 28.8 16C28.8 8.93075 23.0692 3.2 16 3.2C8.93075 3.2 3.2 8.93075 3.2 16Z" fill="#F5F5F5"/>
                        <path d="M32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0C24.8366 0 32 7.16344 32 16ZM3.2 16C3.2 23.0692 8.93075 28.8 16 28.8C23.0692 28.8 28.8 23.0692 28.8 16C28.8 8.93075 23.0692 3.2 16 3.2C8.93075 3.2 3.2 8.93075 3.2 16Z" fill="#3BAE2C"/>
                        <path d="M11 16.5L14.1914 19.963C14.6584 20.4697 15.4859 20.3633 15.8096 19.755L21 10" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <div v-else :style="{background: `conic-gradient(
                                                #3BAE2C 0deg ${Math.round(closestTournament.object.progress / 100 * 360)}deg,
                                                #F5F5F5 ${Math.round(closestTournament.object.progress / 100 * 360)}deg 360deg
                                        )`, height: '32px', width: '32px'}">
                        <div style="background-color: #5AD000;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>