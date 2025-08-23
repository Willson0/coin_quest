<script>
import config from "@/config.json";
export default {
    name: "SelectFiatComponent",
    data () {
        return {
            search: '',
            config: config,
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
    <div class="selectFiat">
        <div class="courses_search">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M13.24 13.2401C15.58 10.9001 15.58 7.10012 13.24 4.75012C10.9 2.41012 7.1 2.41012 4.75 4.75012C2.41 7.09012 2.41 10.8901 4.75 13.2401C7.09 15.5801 10.89 15.5801 13.24 13.2401Z" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.5 13.5L21 21" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input v-model="search" placeholder="Поиск валюты">
        </div>
        <div v-if="!user.fiat_currencies?.length">Тут пока что ничего нет...</div>
        <div class="trade_overlay_main selectFiat_main">
            <div v-for="(crypt, key) in user.fiat_currencies?.filter(c => c.name.toLowerCase().trim().includes(search.toLowerCase().trim()))" @click="$emit('change', crypt.id)">
                <img :src="config.storage + crypt.image" alt="">
                <div class="trade_overlay_main_info">
                    <div>{{ crypt.name }}</div>
                    <div style="font-weight: 400">{{ crypt.symbol }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>