<script>
import {closeOverlay} from "@/utils.js";

export default {
    name: "CryptoOverlay",
    methods: {
        closeOverlay,
        onStartDrag(e) {
            this.dragStartY = e.touches ? e.touches[0].clientY : e.clientY;
            this.dragging = true;

            window.addEventListener('mousemove', this.onMoveDrag);
            window.addEventListener('touchmove', this.onMoveDrag);
            window.addEventListener('mouseup', this.onEndDrag);
            window.addEventListener('touchend', this.onEndDrag);
        },
        onMoveDrag(e) {
            if(this.dragging) {
                let el = document.querySelector('.trade_overlay');
                let transformY = e.touches ? e.touches[0].clientY - this.dragStartY : e.clientY - this.dragStartY;
                if (transformY < 0) return;

                el.style.transition = 'none';
                el.style.transform = `translateY(${transformY}px)`;
            }
        },
        onEndDrag(e) {
            if (!this.dragging) return;

            let el = document.querySelector('.trade_overlay');
            el.style.transition = '';
            el.style.transform = 'translateY(0)';

            const endY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
            const deltaY = endY - this.dragStartY;

            if(deltaY > 50) this.closeOverlay('trade_overlay', 'trade_background');

            window.removeEventListener('mousemove', this.onMoveDrag);
            window.removeEventListener('touchmove', this.onMoveDrag);
            window.removeEventListener('mouseup', this.onEndDrag);
            window.removeEventListener('touchend', this.onEndDrag);
            this.dragging = false;
            this.dragStartY = null;
        },
    },
    computed: {
        user() {
            return this.$store.state.user;
        },
    },
    data () {
        return {
            selectedCurrency: 0,
            search: "",
            dragStartY: null,
            dragging: false,
            transformY: 0,
        }
    },
    watch: {
        selectedCurrency () {
            this.closeOverlay('trade_overlay', 'trade_background');
        },
    }
}
</script>

<template>
    <div @click="closeOverlay('trade_overlay', 'trade_background')" class="background trade_background" style="display: none"></div>
    <div style="display:none" class="overlay trade_overlay" >
        <div class="trade_overlay_close_area" @mousedown="onStartDrag" @touchstart="onStartDrag">
            <div></div>
        </div>
        <div class="courses_search">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M13.24 13.2401C15.58 10.9001 15.58 7.10012 13.24 4.75012C10.9 2.41012 7.1 2.41012 4.75 4.75012C2.41 7.09012 2.41 10.8901 4.75 13.2401C7.09 15.5801 10.89 15.5801 13.24 13.2401Z" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.5 13.5L21 21" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input v-model="search" placeholder="Поиск криптовалюты">
        </div>
        <div class="trade_overlay_main">
            <div v-for="(crypt, key) in user.currenciesData?.filter(c => c.name.toLowerCase().trim().includes(search.toLowerCase().trim()))" @click="selectedCurrency = key; $emit('change', selectedCurrency)">
                <img :src="crypt.logo" alt="">
                <div class="trade_overlay_main_info">
                    <div>{{ crypt.symbol }}</div>
                    <div>{{ crypt.name }}</div>
                </div>
                <div class="trade_overlay_main_price">${{crypt.price}}</div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>