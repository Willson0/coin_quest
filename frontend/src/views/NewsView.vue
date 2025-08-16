<script>
import axios from "axios";
import config from "@/config";
export default {
    name: "NewsView",
    data () {
        return {
            mouseDown: false,
            startX: 0,
            scrollLeft: 0,
            selectedCategory: -1,
            isDragging: false,
            allNews: [],
            isLoading: false,
            fullCategories: [],
            config: config,
        }
    },
    mounted () {
        window.addEventListener('mouseup', this.mouseup);
        window.addEventListener('mousemove', this.mousemove);

        window.addEventListener('scroll', this.scroll);
    },
    unmounted () {
        window.removeEventListener('mouseup', this.mouseup);
        window.removeEventListener('mousemove', this.mousemove);

        window.removeEventListener('scroll', this.scroll);
    },
    methods: {
        scroll () {
            let el = document.querySelector('.news_main>div:nth-last-child(2)');
            if (el && el.getBoundingClientRect().top < window.innerHeight) {
                this.loadMore();
            }
        },
        mousedown(ev) {
            let el = this.$refs.newsNav;

            this.mouseDown = true;
            this.startX = ev.pageX - el.offsetLeft;
            this.scrollLeft = el.scrollLeft;
        },
        mousemove (ev) {
            if (!this.mouseDown) return;

            if (Math.abs(ev.pageX - this.startX) > 5) {
                this.isDragging = true
            }

            ev.preventDefault();
            let slider = this.$refs.newsNav;

            const x = ev.pageX - slider.offsetLeft;
            const walk = (x - this.startX) * 1; // 1 = чувствительность
            slider.scrollLeft = this.scrollLeft - walk;
        },
        mouseup (ev) {
            this.mouseDown = false;
            setTimeout(() => {
                this.isDragging = false;
            }, 100);
        },
        selectCategory (category) {
            if (!this.isDragging) this.selectedCategory = category;
        },
        // initAll () {
        //     if (!this.user.news || this.allNews.length) return;
        //     this.allNews = this.user.news.map(news => news.news).flat().sort((a, b) => b.id - a.id);
        // },
        formDate (date) {
            let d = new Date(date);
            return `${d.getDate().toString().padStart(2, '0')}.${(d.getMonth() + 1).toString().padStart(2, '0')}.${d.getFullYear().toString().slice(2)}`;
        },
        async loadMore () {
            if (this.isLoading) return;
            if (this.fullCategories.includes(this.selectedCategory)) return;
            this.isLoading = true;

            await axios.post(config.backend + 'news', {
                "initData": window.Telegram.WebApp.initData,
                "category": this.selectedCategory,
                "offset": this.selectedCategory === -1 ? this.user.allNews.length : this.user.news.find(item => item.id === this.selectedCategory).news.length,
            }).then((response) => {
                if (!response.data.length) return this.fullCategories.push(this.selectedCategory);

                let newUser = {...this.user}
                if (this.selectedCategory === -1) newUser.allNews = [...this.user.allNews, ...response.data];
                else newUser.news.find(item => item.id === this.selectedCategory).news = [...this.user.news.find(item => item.id === this.selectedCategory).news, ...response.data];
                this.$store.dispatch("updateUser", newUser);
            }).finally(() => {
                this.isLoading = false;
            });
        },
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
    },
    watch: {
        user () {
            // this.initAll();
        }
    }
}
</script>

<template>
    <div class="news">
        <div ref="newsNav" class="news_nav" @mousedown.stop="mousedown">
            <div @click="selectCategory(-1)" :class="{'active': selectedCategory === -1}">Все</div>
            <div @click="selectCategory(category.id)" :class="{'active': selectedCategory === category.id}"
                 v-for="category in user.news">{{ category.name }}</div>
        </div>
        <div class="news_main">
            <div v-for="post in selectedCategory === -1 ? user.allNews : user.news.find(item => item.id === selectedCategory).news">
                <div class="news_main_photo">
                    <img :src="(post.image.startsWith('https') ? post.image : config.storage + post.image)" alt="">
                    <div class="news_main_photo_category">{{ user.news.find(item => item.id === post.category_id).name }}</div>
                </div>
                <div class="news_main_info">
                    <div class="news_main_info_title">{{ post.title }}</div>
                    <div class="news_main_info_date">{{ formDate(post.created_at) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>