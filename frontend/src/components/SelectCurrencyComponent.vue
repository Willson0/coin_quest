<script>
export default {
    name: "SelectCurrencyComponent",
    data () {
        return {

        }
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
    },
    props: {
        crypts: {
            type: Array,
            default: null
        }
    }
}
</script>

<template>
    <div class="selectCurrency">
        <div @click="$emit('change', crypt.coingeckoId)" v-for="crypt in (crypts ?? user.currenciesData)">
            <img :src="crypt.logo" alt="">
            <div>{{ crypt.name }}</div>
        </div>
    </div>
</template>

<style scoped>

</style>