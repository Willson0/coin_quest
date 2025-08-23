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
            let counter = 0;
            for (let course of this.user.courses) {
                for (let lesson of course.lessons) {
                    if (lesson.count_tries > 0 && lesson.user_points >= 50) counter++;
                    else if (lesson.count_tries === 0 && lesson.user_points != null) counter++;
                }
            }
            return counter;
        }
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
                <div v-for="ach in user.achievements" v-show="countLesson >= ach.progress && user.pinned_achievements?.includes(ach.id)"><img :src="config.storage + ach.image" alt=""></div>
                <div v-for="el in (3 - (user.achievements?.filter(ach => countLesson >= ach.progress && user.pinned_achievements?.includes(ach.id)).length ?? 0))"></div>
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
        <lesson-component style="margin: 11px; margin-top: 24px;" :is-closest="true" v-if="closestLesson" :lesson="closestLesson" />
    </div>
</template>

<style scoped>

</style>