<script>
import CryptoOverlay from "@/components/CryptoOverlay.vue";
import {notify, openOverlay, showSuccess, toLink} from "@/utils.js";
import axios from "axios";
import config from "@/config.json";
import SuccessComponent from "@/components/SuccessComponent.vue";

export default {
    name: "SendWalletView",
    components: {SuccessComponent, CryptoOverlay},
    data () {
        return {
            selectedCurrency: 0,
            wallet: '',
            amount: '',
            isLoading: false,
        }
    },
    async mounted () {
    },
    methods: {
        openOverlay,
        async sendData () {
            if (!this.wallet.trim().length === 0) notify("Введите адрес кошелька!", 1)
            // if (!/[A-Za-z0-9]{26,60}/.test(this.wallet)) return notify("Неверный адрес кошелька!", "error")
            if (this.amount <= 0) return notify("Сумма должна быть больше 0!", 1);
            if (this.amount > this.user.crypto[this.user.currenciesData[this.selectedCurrency].coingeckoId])
                return notify("Недостаточно средств!", 1);

            this.isLoading = true;
            await axios.post(config.backend + "trade/send", {
                initData: window.Telegram.WebApp.initData,
                wallet: this.wallet,
                currency: this.user.currenciesData[this.selectedCurrency].coingeckoId,
                amount: this.amount,
            }).then((response) => {
                let newUser = {...this.user, crypto: response.data.crypto};
                this.$store.dispatch("updateUser", newUser);

                showSuccess();
            }).catch((error) => {
                notify(error.response.data.message, 1)
            }).finally(() => {
                this.isLoading = false;
            })
        }
    },
    computed: {
        user () {
            return this.$store.state.user
        }
    },
}
</script>

<template>
    <crypto-overlay @change="selectedCurrency = $event" />
    <div class="sendWallet" v-if="user.id">
        <div class="sendWallet_currency" @click="openOverlay('trade_overlay', 'trade_background')">
            <img :src="user.currenciesData[selectedCurrency].logo" alt="">
            <div class="sendWallet_currency_info">
                <div class="sendWallet_currency_info_name">{{ user.currenciesData[selectedCurrency].name }}</div>
                <div class="sendWallet_currency_info_count">{{ user.crypto?.[user.currenciesData[selectedCurrency].coingeckoId] ?? 0 }}</div>
            </div>
            <div class="sendWallet_currency_price">${{ user.currenciesData[selectedCurrency].price }}</div>
            <svg xmlns="http://www.w3.org/2000/svg" width="8" height="15" viewBox="0 0 8 15" fill="none">
                <path d="M1 1.5L6.29289 6.79289C6.68342 7.18342 6.68342 7.81658 6.29289 8.20711L1 13.5" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <input type="text" v-model="wallet" placeholder="Адрес кошелька">
        <input type="number" v-model="amount" placeholder="Введите сумму">
        <button @click="sendData" :class="{'active': isLoading}">Отправить</button>
    </div>
    <success-component title="Отправка совершена" :description="`В течение 15 минут ${this.amount} ${user.currenciesData?.[selectedCurrency].symbol} будет начислен на счет`" back-link="send"/>
</template>

<style scoped>

</style>