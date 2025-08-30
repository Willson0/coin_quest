<script>
import NavComponent from "@/components/NavComponent.vue";
import axios from 'axios';
import config from "@/config.json"
import {endLoading, notify, toLink} from "@/utils.js";
import ProfileView from "@/views/ProfileView.vue";
import CoursesView from "@/views/CoursesView.vue";
import TournamentView from "@/views/TournamentView.vue";
import NewsView from "@/views/NewsView.vue";
import TradeView from "@/views/TradeView.vue";
import ShopView from "@/views/ShopView.vue";
import ThemeView from "@/views/ThemeView.vue";
import LessonView from "@/views/LessonView.vue";
import WayPaymentComponent from "@/components/WayPaymentComponent.vue";
import SendView from "@/views/SendView.vue";
import TopupView from "@/views/TopupView.vue";
import SendWalletView from "@/views/SendWalletView.vue";
import ChangeView from "@/views/ChangeView.vue";
import SendContactView from "@/views/SendContactView.vue";
import TopupCardView from "@/views/TopupCardView.vue";
import AchievementsView from "@/views/AchievementsView.vue";
import SupportView from "@/views/SupportView.vue";
import TopupWalletView from "@/views/TopupWalletView.vue";
import TopupP2PMarket from "@/views/TopupP2PMarketView.vue";
import router from "@/router.js";

export default {
    name: "MainView",
    data () {
        return {
            queryHistory: [],
            isGoingBack: false,
            firstLoading: true,
            touch: false,
            notWhiteList: false,
        }
    },
    components: {
        TopupP2PMarket,
        TopupWalletView,
        SupportView,
        AchievementsView,
        TopupCardView,
        SendContactView,
        ChangeView,
        SendWalletView,
        TopupView,
        SendView,
        WayPaymentComponent,
        LessonView,
        ThemeView,
        ShopView,
        TradeView,
        NewsView,
        TournamentView,
        CoursesView,
        NavComponent,
        ProfileView,
    },
    async mounted () {
        document.addEventListener('gesturestart', function (e) {
            e.preventDefault();
        });
        document.addEventListener('gesturechange', function (e) {
            e.preventDefault();
        });
        document.addEventListener('gestureend', function (e) {
            e.preventDefault();
        });

        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            let now = new Date().getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        window.Telegram.WebApp.setHeaderColor("secondary_bg_color");

        document.addEventListener('touchstart', function(event) {
            const activeElement = document.activeElement;
            if ((activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')
                && !activeElement.contains(event.target)
                && event.target !== activeElement) {
                if (event.target.tagName !== 'INPUT' && event.target.tagName !== 'TEXTAREA') {
                    activeElement.blur();
                }
            }
        }, { passive: true });

        window.Telegram.WebApp.expand();
        window.Telegram.WebApp.disableVerticalSwipes();
        if (window.Telegram.WebApp.initDataUnsafe.start_param) {
            let origParams = decodeURIComponent(window.Telegram.WebApp.initDataUnsafe.start_param);
            const params = origParams.split("_");

            const sessionKey = 'tg_start_param';
            if (!sessionStorage.getItem(sessionKey)) {
                if (/^[0-9]+$/.test(params[1]) && Number(params[1]) >= 0)  {
                    if (params[0] === "achievement") toLink("achievements", params[1])
                    else if (params[0] === "order") toLink("topup_p2p_market", params[1])
                } else if (params[0] === "wallet") toLink("sendwallet", params[1])
                else this.$router.push({ query: { s: 'profile' }});

                sessionStorage.setItem(sessionKey, "1")
            }
        }
        else if (!this.$route.query.s) this.$router.push({ query: { s: 'profile' }});

        this.fetchData();

        window.Telegram.WebApp.BackButton.onClick(this.backByQuery);
        window.backByQueryFunction = this.backByQuery;

        window.addEventListener("touchstart", () => this.touch = true);
        // window.addEventListener("touchend", () => this.touch = false);

        this.hideFooter();

        document.addEventListener("touchstart", async (e) => {
            let menu = e.target.closest(".profile_menu>div") || e.target.closest(".invoice_main>div");
            if (menu) {
                if (menu.querySelector(".iphoneSwitcher")) return;
                menu.style.backgroundColor = "var(--tg-theme-section-separator-color)";
                menu.addEventListener("transitionend", () => {
                    menu.style.backgroundColor = "";
                }, {once: true});
            }
        });
    },
    watch: {
        $route(to, from) {
            clearInterval(this.$store.state.interval);
            document.body.style.overflow = "";
        },
        '$route.query' (to, from) {

            window.Telegram.WebApp.setHeaderColor("secondary_bg_color");
            document.body.style.overflow = "";

            const footer = document.querySelector('.nav');
            if (footer) {
                footer.style.display = '';
                footer.style.opacity = "1";
            }

            this.$nextTick(() => this.hideFooter())

            document.body.style.overflow = "";
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (this.isGoingBack === true) {
                this.isGoingBack = false;
                return;
            }
            if (from.s === undefined) return;

            if (to.needback === "1" || to.needback == undefined || to.needback == null) {
                this.queryHistory.push(from);
            }

            window.Telegram.WebApp.BackButton.show();
        }
    },
    methods: {
        async fetchData () {
            axios.post(config.backend + "auth/profile", {
                "initData": window.Telegram.WebApp.initData,
            }).then((response) => {
                if (this.firstLoading) {
                    this.firstLoading = false;
                    endLoading();
                }

                let user = response.data;
                user.courses.forEach(course => {
                    const lessons = course.lessons;
                    const total = lessons.length;
                    const completed = lessons.filter(lesson =>
                        lesson.user_points !== null && lesson.user_points >= -1
                    ).length;

                    course.progress = total > 0 ? Math.round((completed / total) * 100) : 0;
                });

                this.$store.dispatch("updateUser", user);
            }).catch((error) => {
                if (error.response.status === 423) {
                    notify ("Доступ запрещен. Вы не находитесь в белом списке", 1);
                    return this.notWhiteList = true;
                } else {
                    document.querySelector(".unreg").style.display = "flex";
                    endLoading();
                }
            }).finally(() => {
            });
        },
        backByQuery() {
            if (this.queryHistory.length > 0) {
                this.isGoingBack = true;

                const prevQuery = this.queryHistory.pop();
                this.$router.push({ query: prevQuery });

                if (this.queryHistory.length === 0) window.Telegram.WebApp.BackButton.hide();
            } else {
                this.$router.push({ query: {s: 'profile'} });
            }
        },
        hideFooter () {
            let footer = document.querySelector(".nav");
            if (footer) {
                document.querySelectorAll("input, textarea").forEach((el) => {
                    el.addEventListener("focus", () => {
                        if (this.touch) {
                            footer.style.opacity = "0";

                            let dialog = document.querySelector(".dialog")
                            if (dialog) dialog.style.height = "calc(100vh - 10px)";
                            document.querySelector(".nav").style.paddingBottom = "0px"
                        }
                    });
                    el.addEventListener("blur", () => {
                        footer.style.opacity = "1";

                        let dialog = document.querySelector(".dialog")
                        if (dialog) dialog.style.height = "";

                        document.querySelector(".nav").style.paddingBottom = "";
                    });
                })
            }
        },
    },
    computed: {
        name () {
            return window.Telegram.WebApp.initDataUnsafe?.user?.first_name;
        }
    }
}
</script>

<template>
    <div class="notification_container"></div>
    <div class="loading">
        <div v-if="!notWhiteList">Добрый день, {{name}}</div>
        <div v-else>Вы не состоите<br>в белом списке</div>
    </div>
    <div class="notification_container"></div>
    <lesson-view v-if="$route.query.s === 'lesson'" />
    <send-view v-else-if="$route.query.s === 'send'" />
    <topup-view v-else-if="$route.query.s === 'topup'" />
    <change-view v-else-if="$route.query.s === 'change'" />
    <send-wallet-view v-else-if="$route.query.s === 'sendwallet'" />
    <send-contact-view v-else-if="$route.query.s === 'sendcontact'" />
    <topup-card-view v-else-if="$route.query.s === 'topupcard'" />
    <topup-wallet-view v-else-if="$route.query.s === 'topupwallet'" />
    <achievements-view v-else-if="$route.query.s === 'achievements'" />
    <topup-p2-p-market v-else-if="$route.query.s === 'topup_p2p_market'" />
    <support-view v-else-if="$route.query.s === 'support'" />
    <nav-component v-else>
        <profile-view v-if="$route.query.s === 'profile'" />
        <courses-view v-if="$route.query.s === 'courses'" />
        <tournament-view v-if="$route.query.s === 'tournament'" />
        <news-view v-if="$route.query.s === 'news'" />
        <trade-view v-if="$route.query.s === 'trade'" />
        <shop-view v-if="$route.query.s === 'shop'" />
        <theme-view v-if="$route.query.s === 'theme'" />
    </nav-component>
</template>

<style scoped>

</style>