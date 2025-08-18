<script>
import SelectCurrencyComponent from "@/components/SelectCurrencyComponent.vue";
import SelectFiatComponent from "@/components/SelectFiatComponent.vue";
import {formatPrice, notify, showSuccess} from "../utils.js";
import axios from "axios";
import config from "@/config.json";
import SuccessComponent from "@/components/SuccessComponent.vue";

export default {
    name: "TopupCardView",
    data () {
        return {
            selectedCurrency: null,
            selectedFiat: null,
            count: 0,
            isChange: false,
        }
    },
    components: {SuccessComponent, SelectFiatComponent, SelectCurrencyComponent},
    mounted () {
        window.Telegram.WebApp.BackButton.offClick(window.backByQueryFunction);
        window.Telegram.WebApp.BackButton.onClick(this.backFunction);
        window.Telegram.WebApp.BackButton.show();
    },
    unmounted () {
        window.Telegram.WebApp.BackButton.offClick(this.backFunction);
        window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
    },
    methods: {
        formatPrice,
        backFunction () {
              if (!this.selectedCurrency) window.backByQueryFunction();
              else if (this.selectedCurrency && !this.selectedFiat) this.selectedCurrency = null;
              else if (this.isChange) this.isChange = false;
              else this.selectedFiat = null;
        },
        changeSizeInput (ev) {
            let el = ev.target;
            el.style.width = "";
            el.style.width = el.scrollWidth + "px";
        },
        async sendData () {
            await axios.post(config.backend + "trade/buy", {
                initData: window.Telegram.WebApp.initData,
                currency: this.selectedCurrency,
                amount: this.count,
            }).then((response) => {
                let newUser = {...this.user};
                newUser.crypto = response.data.crypto;
                this.$store.dispatch("updateUser", newUser);

                showSuccess();
            }).catch((error) => {
                console.log(error);
                notify(error.response?.data?.message || error.message || "Неизвестная ошибка", 1);
            })
        }
    },
    computed: {
        user () {
            return this.$store.state.user;
        },
    }
}
</script>

<template>
    <select-currency-component v-if="!selectedCurrency" @change="selectedCurrency = $event"/>
    <select-fiat-component v-if="(selectedCurrency && !selectedFiat) || isChange" @change="selectedFiat = $event; isChange = false;"/>
    <div class="topupCard">
        <div class="topupCard_main">
            <div class="topupCard_main_info">Вы покупаете</div>
            <div class="topupCard_main_input">
                <input type="number" v-model="count" @input="changeSizeInput" min="0" placeholder="0">
                <div class="topupCard_main_input_currency">
                    {{ user.currenciesData?.find(a => a.coingeckoId === selectedCurrency)?.symbol }}
                </div>
                <div class="topupCard_main_input_fiat" @click="isChange = true">
                    {{ user.fiat_currencies?.find(a => a.id === selectedFiat)?.symbol }}
                    <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.6856 0H1.51523C0.610483 0 0.170843 1.1058 0.828603 1.72701L6.74232 7.31219C7.14433 7.69186 7.77816 7.6732 8.15714 7.27054L13.4138 1.68536C14.0144 1.04719 13.5619 0 12.6856 0Z" fill="#1E1E22"/>
                    </svg>
                </div>
            </div>
            <div class="topupCard_main_price">
                = {{ formatPrice(user.currenciesData?.find(a => a.coingeckoId === selectedCurrency)?.price * count) }} $
            </div>
        </div>
        <button @click="sendData">Купить {{ count }} {{ user.currenciesData?.find(a => a.coingeckoId === selectedCurrency)?.symbol }}</button>
    </div>
    <success-component title="Успешная покупка" :description="`В течение 15 минут ${count} ${user.currenciesData?.find(a => a.coingeckoId === selectedCurrency)?.symbol} будет начислен`" back-link="topup"/>
</template>

<style scoped>

</style>