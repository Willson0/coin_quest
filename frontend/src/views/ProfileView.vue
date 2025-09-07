<script>
import {toLink} from "@/utils.js";
import config from "@/config.json";
import LessonComponent from "@/components/LessonComponent.vue";

export default {
    components: {LessonComponent},
    data () {
        return {
            config: config,
            mouseDown: false,
            startX: 0,
            scrollLeft: 0,
            isDragging: false,
        }
    },
    mounted () {
        this.initLefts();

        window.addEventListener('mouseup', this.mouseup);
        window.addEventListener('mousemove', this.mousemove);
    },
    unmounted () {
        window.removeEventListener('mouseup', this.mouseup);
        window.removeEventListener('mousemove', this.mousemove);
    },
    computed: {
        avatar () {
            return window.Telegram.WebApp.initDataUnsafe?.user?.photo_url;
        },
        name () {
            return window.Telegram.WebApp.initDataUnsafe?.user?.first_name;
        },
        user () {
            return this.$store.state.user;
        },
        balance () {
            if (!this.user.id) return;
            let summ = 0;

            for (let crypt in this.user.crypto) {
                summ += this.user.crypto[crypt] * this.user.currenciesData?.find(a => a.coingeckoId === crypt)?.price;
            }
            return summ;
        },
        percentCourse () {
            if (!this.user.id) return 0;

            const totalLessons = this.user.courses.reduce((sum, course) => sum + course.lessons.length, 0);
            if (totalLessons === 0) return 100;
            return this.countLesson / totalLessons * 100;
        },
        closestLesson () {
            if (this.user.courses) {
                for (let course of this.user.courses) {
                    let ended = course.lessons.filter(a => a.user_points != null);
                    if (ended.length === 0) {
                        if (course.lessons.length > 0) {
                            return course.lessons[0];
                        }
                    }

                    let last = ended.sort((a, b) => b.user_points - a.user_points)[0];
                    console.log(last);
                    let next = course.lessons.filter(a => a.number > last.number);
                    if (next) {
                        return next.sort((a, b) => a.number - b.number)[0];
                    }
                }
            }
        },
        countLesson () {
            if (!this.user.id) return;
            let counter = 0;
            for (let course of this.user.courses) {
                for (let lesson of course.lessons) {
                    if (lesson.count_tries > 0 && lesson.user_points >= 50) counter++;
                    else if (lesson.count_tries === 0 && lesson.user_points != null) counter++;
                }
            }
            return counter;
        },
        unpinnedAchievements () {
            if (!this.countLesson) return;
            let countPinned = this.user.achievements?.filter(ach => this.hasAchievement(ach) && this.user.pinned_achievements?.includes(ach.id))?.length ?? 0;
            let achs = this.user.achievements?.filter(ach => this.hasAchievement(ach) && !this.user.pinned_achievements?.includes(ach.id)).slice(0, 3 - countPinned);
            return achs;
        },
    },
    methods: {
        toLink,
        formNum (number) {
            return number?.toLocaleString('de-DE');
        },
        getLeftAchievement (progress) {
            let el = document.querySelector('.profile_progress_bar');
            if (!el) return;

            let fullWidth = el.offsetWidth;
            let fullPercent = this.user.courses.reduce((sum, course) => sum + course.lessons.length, 0);
            console.log(progress, fullPercent)

            if (fullPercent === 0) return 100 + "%";
            if (fullWidth * (progress / fullPercent) < 32) return 40 + "px";
            // else if (fullWidth * (progress / fullPercent) > fullWidth - 32) return "calc(100% - 36px)";
            return (progress / fullPercent) * 100 + "%";
        },
        initLefts () {
            if (!this.user.id) return;
            this.user.achievements.forEach((ach) => {
                ach.left = this.getLeftAchievement(ach.progress);
            });
        },
        formatPrice(number, decimals = 0) {
            let parts = number.toFixed(decimals).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            return decimals > 0 ? parts.join('.') : parts[0];
        },
        mousedown(ev) {
            ev.preventDefault();
            let el = this.$refs.newsNav;

            document.body.classList.add("grabbing");

            this.mouseDown = true;
            this.startX = ev.pageX - el.offsetLeft;
            this.scrollLeft = el.scrollLeft;
        },
        mousemove (ev) {
            if (!this.mouseDown) return;

            ev.preventDefault();
            let slider = this.$refs.newsNav;

            const x = ev.pageX - slider.offsetLeft;
            const walk = (x - this.startX) * 1; // 1 = чувствительность
            slider.scrollLeft = this.scrollLeft - walk;
        },
        mouseup (ev) {
            this.mouseDown = false;

            document.body.classList.remove("grabbing");
        },
        hasAchievement (achievement) {
            if (achievement.type === 'lessons') return this.countLesson >= achievement.progress
            else if (achievement.type === 'channel') return this.user.channels.includes(achievement.progress)
            else if (achievement.type === "tournament") return this.user.wonTournaments.includes(Number(achievement.progress))
        }
    },
    watch: {
        user () {
            this.initLefts();
        }
    }
}
</script>

<template>
    <div class="profile">
        <div class="profile_background">
            <img :src="avatar" alt="">
        </div>
        <div class="profile_info">
            <div class="profile_info_avatar">
                <img :src="avatar" alt="">
            </div>
            <div class="profile_info_name">{{name}}</div>
            <div class="profile_info_wallet">
                <div class="profile_info_wallet_text">Баланс кошелька</div>
                <div class="profile_info_wallet_balance">
                    ${{balance?.toFixed(2)}}
<!--                    <svg xmlns="http://www.w3.org/2000/svg" width="8" height="15" viewBox="0 0 8 15" fill="none">-->
<!--                        <path d="M1 1.5L6.29289 6.79289C6.68342 7.18342 6.68342 7.81658 6.29289 8.20711L1 13.5" stroke="#1E1E22" stroke-width="2" stroke-linecap="round"/>-->
<!--                    </svg>-->
                </div>
                <div class="profile_info_wallet_buttons">
                    <div @click="toLink('send')">
                        <button>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="26" viewBox="0 0 18 26" fill="none">
                                <path d="M8.99997 25V1M8.99997 1L1.5 8.5M8.99997 1L16.5 8.5" stroke="#B963FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div>Отправить</div>
                    </div>
                    <div @click="toLink('topup')">
                        <button>
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26" fill="none">
                                <path d="M13 0C13.5523 0 14 0.447715 14 1V12H25C25.5523 12 26 12.4477 26 13C26 13.5523 25.5523 14 25 14H14V25C14 25.5523 13.5523 26 13 26C12.4477 26 12 25.5523 12 25V14H1C0.447716 14 5.66448e-07 13.5523 0 13C2.41411e-08 12.4477 0.447715 12 1 12H12V1C12 0.447715 12.4477 0 13 0Z" fill="#B963FF"/>
                            </svg>
                        </button>
                        <div>Пополнить</div>
                    </div>
                    <div @click="toLink('change')">
                        <button>
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26" fill="none">
                                <path d="M1 7.49999L25 7.49999M25 7.49999L17.5 1M25 7.49999L17.5 14" stroke="#B963FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M25 19.5L1 19.5M1 19.5L8.5 14M1 19.5L8.5 25" stroke="#B963FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div>Обменять</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="profile_crypto" ref="newsNav" @mousedown.stop="mousedown">
            <div>
                <div v-for="(count, crypt) in user.crypto" v-show="count > 0">
                    <div class="profile_crypto_header">
                        <img :src="user.currenciesData.find(a => a.coingeckoId === crypt)?.logo" alt="">
                        <div>
                            <div class="profile_crypto_header_title">{{user.currenciesData.find(a => a.coingeckoId === crypt)?.symbol}}</div>
                            <div class="profile_crypto_header_price">{{formatPrice(user.currenciesData.find(a => a.coingeckoId === crypt)?.price, 2)}} $<span :style="{'color': user.currenciesData.find(a => a.coingeckoId === crypt)?.change >= 0 ? '#5AD000' : '#AF0003'}"> {{user.currenciesData.find(a => a.coingeckoId === crypt)?.change.toFixed(2)}}%</span></div>
                        </div>
                    </div>
                    <div class="profile_crypto_footer">
                        <div class="profile_crypto_footer_count">{{Number(count).toFixed(4).replace(/\.?0+$/, "")}} {{user.currenciesData.find(a => a.coingeckoId === crypt)?.symbol}}</div>
                        <div class="profile_crypto_footer_count_price">${{formatPrice(user.currenciesData.find(a => a.coingeckoId === crypt)?.price * count, 2)}}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="profile_achievements">
            <div class="profile_achievements_title" @click="toLink('achievements')">
                Витрина достижений
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="15" viewBox="0 0 8 15" fill="none">
                    <path d="M1 1.5L6.29289 6.79289C6.68342 7.18342 6.68342 7.81658 6.29289 8.20711L1 13.5" stroke="#1E1E22" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="profile_achievements_main">
                <div v-for="ach in user.achievements" v-show="hasAchievement(ach) && user.pinned_achievements?.includes(ach.id)"><img :src="config.storage + ach.image" alt=""></div>
                <div v-for="ach in unpinnedAchievements">
                    <img :src="config.storage + ach.image" alt="">
                </div>
                <div v-for="ach in Math.max(0, 3 - (user.achievements?.filter(ach => hasAchievement(ach)).length ?? 0))" :key="ach"></div>
            </div>
        </div>
        <div class="profile_progress">
            <div class="profile_progress_title">
                <div>Прогресс курсов</div>
                <div>{{ percentCourse }} / 100</div>
            </div>
            <div class="profile_progress_bar">
                <div class="profile_progress_bar_fill" :style="{'width': `${ percentCourse }%`}"></div>
                <div :style="{'left': ach.left}" class="profile_progress_bar_item" v-for="ach in user.achievements">
                    <svg v-if="countLesson >= Number(ach.progress)" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                        <path d="M5.00002 4.14307H17C17.682 4.14307 18.3361 4.41398 18.8183 4.89622C19.3005 5.37846 19.5714 6.03251 19.5714 6.71449V9.14307C19.5714 9.69535 19.1237 10.1431 18.5714 10.1431H14C13.7633 10.1431 13.5714 9.95119 13.5714 9.7145C13.5714 9.4778 13.3796 9.28592 13.1429 9.28592H8.85716C8.62047 9.28592 8.42859 9.4778 8.42859 9.7145C8.42859 9.95119 8.23671 10.1431 8.00002 10.1431H3.42859C2.8763 10.1431 2.42859 9.69535 2.42859 9.14307V6.71449C2.42859 6.03251 2.69951 5.37846 3.18174 4.89622C3.66398 4.41398 4.31803 4.14307 5.00002 4.14307ZM10.1429 11.0002C10.1429 10.5268 10.5266 10.1431 11 10.1431C11.4734 10.1431 11.8572 10.5268 11.8572 11.0002C11.8572 11.4736 11.4734 11.8574 11 11.8574C10.5266 11.8574 10.1429 11.4736 10.1429 11.0002ZM2.42859 12.0002C2.42859 11.4479 2.8763 11.0002 3.42859 11.0002H7.8225C8.15723 11.0002 8.42859 11.2716 8.42859 11.6063C8.42859 11.767 8.49244 11.9212 8.60611 12.0349L9.84998 13.2787C10.0375 13.4663 10.2919 13.5716 10.5571 13.5716H11.4429C11.7082 13.5716 11.9625 13.4663 12.1501 13.2787L13.3939 12.0349C13.5076 11.9212 13.5714 11.767 13.5714 11.6063C13.5714 11.2716 13.8428 11.0002 14.1775 11.0002H18.5714C19.1237 11.0002 19.5714 11.4479 19.5714 12.0002V16.8574C19.5714 17.4096 19.1237 17.8574 18.5714 17.8574H3.42859C2.8763 17.8574 2.42859 17.4096 2.42859 16.8574V12.0002Z" fill="#FF7700"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3 5.4C3 3.96783 3.63214 2.59432 4.75736 1.58162C5.88258 0.568927 7.4087 0 9 0C10.5913 0 12.1174 0.568927 13.2426 1.58162C14.3679 2.59432 15 3.96783 15 5.4H16C16.5304 5.4 17.0391 5.58964 17.4142 5.92721C17.7893 6.26477 18 6.72261 18 7.2V16.2C18 16.6774 17.7893 17.1352 17.4142 17.4728C17.0391 17.8104 16.5304 18 16 18H2C1.46957 18 0.960859 17.8104 0.585786 17.4728C0.210714 17.1352 0 16.6774 0 16.2V7.2C0 6.72261 0.210714 6.26477 0.585786 5.92721C0.960859 5.58964 1.46957 5.4 2 5.4H3ZM9 1.8C10.0609 1.8 11.0783 2.17928 11.8284 2.85442C12.5786 3.52955 13 4.44522 13 5.4H5C5 4.44522 5.42143 3.52955 6.17157 2.85442C6.92172 2.17928 7.93913 1.8 9 1.8ZM11 10.8C11 11.116 10.9076 11.4263 10.732 11.7C10.5565 11.9736 10.304 12.2008 10 12.3588V13.5C10 13.7387 9.89464 13.9676 9.70711 14.1364C9.51957 14.3052 9.26522 14.4 9 14.4C8.73478 14.4 8.48043 14.3052 8.29289 14.1364C8.10536 13.9676 8 13.7387 8 13.5V12.3588C7.61874 12.1607 7.32077 11.8549 7.15231 11.4888C6.98384 11.1227 6.95429 10.7168 7.06824 10.3341C7.18219 9.95139 7.43326 9.6132 7.78253 9.37199C8.1318 9.13077 8.55975 9.00002 9 9C9.53043 9 10.0391 9.18964 10.4142 9.52721C10.7893 9.86477 11 10.3226 11 10.8Z" fill="#1E1E22"/>
                    </svg>
                </div>
            </div>
        </div>
        <button class="profile_sup" @click="toLink('support')" style="display:flex; margin: 11px; margin-top: 20px; margin-bottom: 12px; background-color: var(--die); box-shadow: 0 0 6px 0 rgba(74, 74, 74, 0.1)">
            <div style="display:flex; flex-direction:row; margin: 0 auto; color: black; gap: 10px; font-size: 16px; line-height: 24px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="24" viewBox="0 0 18 24" fill="none">
                    <path d="M9 18.8567C8.40618 18.8568 7.83093 18.6479 7.37347 18.2661C6.91602 17.8843 6.60505 17.3534 6.4942 16.765C4.82672 16.2453 3.35829 15.2205 2.2884 13.8299C1.4807 12.781 0.925721 11.5571 0.667225 10.2549C0.408729 8.95267 0.453792 7.60768 0.798859 6.32601C1.14393 5.04435 1.77956 3.86107 2.65562 2.86951C3.53168 1.87795 4.62421 1.10524 5.84702 0.612316C7.06983 0.119393 8.38949 -0.0802539 9.7019 0.0291226C11.0143 0.138499 12.2836 0.553907 13.4096 1.24258C14.5355 1.93125 15.4875 2.87435 16.1903 3.99749C16.893 5.12063 17.3274 6.39309 17.4592 7.71449C17.5051 8.18596 17.1192 8.57 16.65 8.57C16.1808 8.57 15.8051 8.18425 15.749 7.71449C15.5973 6.50209 15.1271 5.3526 14.387 4.38495C13.647 3.4173 12.664 2.66666 11.5398 2.21068C10.4156 1.7547 9.19102 1.60996 7.99273 1.79142C6.79445 1.97289 5.666 2.47397 4.72417 3.24283C3.78234 4.01169 3.06132 5.02038 2.63574 6.16455C2.21015 7.30871 2.09545 8.54678 2.3035 9.75065C2.51156 10.9545 3.03481 12.0805 3.81911 13.012C4.60342 13.9435 5.62028 14.6467 6.7645 15.0489C6.98082 14.6509 7.29773 14.3177 7.68304 14.0832C8.06836 13.8487 8.50835 13.7212 8.95833 13.7137C9.40831 13.7062 9.85225 13.8189 10.2451 14.0404C10.6379 14.2619 10.9656 14.5843 11.1948 14.9748C11.4241 15.3654 11.5468 15.8101 11.5505 16.264C11.5542 16.7178 11.4387 17.1646 11.2158 17.5589C10.9929 17.9531 10.6704 18.2809 10.2813 18.5088C9.8921 18.7368 9.45004 18.8568 9 18.8567ZM2.2 16.285V16.2387C1.73517 15.8189 1.30951 15.3569 0.9284 14.8586C0.649116 15.281 0.500055 15.7773 0.5 16.285V17.1422C0.5 20.5214 3.662 24 9 24C14.338 24 17.5 20.5214 17.5 17.1422V16.285C17.5 15.603 17.2313 14.9488 16.7531 14.4666C16.2749 13.9843 15.6263 13.7133 14.95 13.7133H12.4C12.7723 14.2105 13.0375 14.7934 13.165 15.4278H14.95C15.1754 15.4278 15.3916 15.5181 15.551 15.6789C15.7104 15.8396 15.8 16.0576 15.8 16.285V17.1422C15.8 19.6076 13.3656 22.2856 9 22.2856C4.6344 22.2856 2.2 19.6076 2.2 17.1422V16.285ZM9 11.9989C8.02728 11.9988 7.08399 12.3353 6.3276 12.9521C5.58435 12.4929 4.97068 11.8486 4.54556 11.0812C4.12043 10.3138 3.89812 9.44905 3.9 8.57C3.90025 7.67331 4.13295 6.79226 4.57502 6.01426C5.01708 5.23625 5.65315 4.58831 6.42014 4.13471C7.18712 3.68112 8.05837 3.43763 8.94746 3.42839C9.83655 3.41915 10.7126 3.64449 11.4887 4.08205C12.2648 4.51962 12.9139 5.1542 13.3718 5.92286C13.8297 6.69151 14.0803 7.56754 14.0989 8.46403C14.1175 9.36053 13.9033 10.2463 13.4777 11.0336C13.052 11.8209 12.4297 12.4823 11.6724 12.9521C10.916 12.3353 9.97272 11.9988 9 11.9989ZM5.6 8.57C5.6 9.4794 5.95821 10.3515 6.59584 10.9946C7.23346 11.6376 8.09826 11.9989 9 11.9989C9.90174 11.9989 10.7665 11.6376 11.4042 10.9946C12.0418 10.3515 12.4 9.4794 12.4 8.57C12.4 7.6606 12.0418 6.78845 11.4042 6.14541C10.7665 5.50237 9.90174 5.14111 9 5.14111C8.09826 5.14111 7.23346 5.50237 6.59584 6.14541C5.95821 6.78845 5.6 7.6606 5.6 8.57Z" fill="#B963FF"/>
                </svg>
                Перейти в чат с экспертом
            </div>
        </button>
        <lesson-component style="margin: 11px; margin-top: 24px;" :is-closest="true" v-if="closestLesson" :lesson="closestLesson" />
    </div>
</template>

<style scoped>

</style>