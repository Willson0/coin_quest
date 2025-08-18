<script>
import {notify, openOverlay, showSuccess} from "@/utils.js";
import CryptoOverlay from "@/components/CryptoOverlay.vue";
import axios from "axios";
import config from "@/config.json";
import SuccessComponent from "@/components/SuccessComponent.vue";

export default {
    name: "ChangeView",
    components: {SuccessComponent, CryptoOverlay},
    data () {
        return {
            firstCoin: 0,
            secondCoin: 1,
            rotate: 0,
            editFirst: true,
            inputFirst: "",
            inputSecond: "",
        }
    },
    methods: {
        openOverlay,
        transfer () {
            let oldFirst = this.firstCoin;
            this.firstCoin = this.secondCoin;
            this.secondCoin = oldFirst;

            oldFirst = this.inputFirst;
            this.inputFirst = this.inputSecond;
            this.inputSecond = oldFirst;

            this.rotate += 180;
        },
        changeCrypto (newCoin) {
            if (this.editFirst) this.firstCoin = newCoin;
            else this.secondCoin = newCoin;

            this.inputCoin(true);
        },
        inputCoin (isFirst) {
            let firstPrice = this.user.currenciesData[this.firstCoin].price
            let secondPrice = this.user.currenciesData[this.secondCoin].price;

            if (isFirst) this.inputSecond = (firstPrice / secondPrice) * Number(this.inputFirst);
            else this.inputFirst = (secondPrice / firstPrice) * Number(this.inputSecond);
        },
        async sendData () {
            if (!this.inputFirst || !this.inputSecond) return notify('Заполните все поля', 1);
            if (this.user.currenciesData[this.firstCoin]?.coingeckoId === this.user.currenciesData[this.secondCoin]?.coingeckoId) return notify('Одинаковые криптовалюты', 1);

            if (!this.user.crypto?.[this.user.currenciesData[this.firstCoin]?.coingeckoId] || this.user.crypto?.[this.user.currenciesData[this.firstCoin]?.coingeckoId] < Number(this.inputFirst))
                return notify('Недостаточно ' + this.user.currenciesData[this.firstCoin]?.symbol + ' на счету', 1);

            await axios.post(config.backend + "trade", {
                initData: window.Telegram.WebApp.initData,
                marketed: this.user.currenciesData[this.firstCoin]?.coingeckoId,
                marketed_count: this.inputFirst,
                purchasable: this.user.currenciesData[this.secondCoin]?.coingeckoId,
            }).then((response) => {
                let newUser = {...this.user};
                newUser.crypto = response.data.crypto;
                this.$store.dispatch("updateUser", newUser);

                showSuccess();
            }).catch((error) => {
                if (error.response) {
                    notify(error.response.data.message, 1);
                }
            });
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
    <crypto-overlay @change="changeCrypto"/>
    <div class="change">
        <div class="change_main" v-if="user.currenciesData">
            <svg :style="{transform: `rotate(${rotate}deg)`}" @click="transfer" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                <rect width="32" height="32" rx="16" fill="#F5F5F5"/>
                <path d="M14 8V24L10 20" stroke="#B963FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M18 24V8L22 12" stroke="#B963FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div>
                <div class="shop_inputs_text">Вы платите</div>
                <div class="shop_inputs_main">
                    <input v-model="inputFirst" @input="inputCoin(true)" type="number" placeholder="Введите сумму">
                    <div style="cursor:pointer;" @click="editFirst = true; openOverlay('trade_overlay', 'trade_background')">
                        {{ user.currenciesData[this.firstCoin].symbol  }}
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="8" viewBox="0 0 14 8" fill="none">
                            <path d="M12.6856 0H1.51523C0.610482 0 0.170842 1.1058 0.828603 1.72701L6.74232 7.31219C7.14433 7.69186 7.77816 7.6732 8.15714 7.27054L13.4138 1.68536C14.0144 1.04719 13.5619 0 12.6856 0Z" fill="#1E1E22"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div>
                <div class="shop_inputs_text">Вы получаете</div>
                <div class="shop_inputs_main">
                    <input v-model="inputSecond" @input="inputCoin(false)" type="number" placeholder="0">
                    <div style="cursor:pointer;" @click="editFirst = false; openOverlay('trade_overlay', 'trade_background')">
                        {{ user.currenciesData[this.secondCoin].symbol  }}
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="8" viewBox="0 0 14 8" fill="none">
                            <path d="M12.6856 0H1.51523C0.610482 0 0.170842 1.1058 0.828603 1.72701L6.74232 7.31219C7.14433 7.69186 7.77816 7.6732 8.15714 7.27054L13.4138 1.68536C14.0144 1.04719 13.5619 0 12.6856 0Z" fill="#1E1E22"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <button @click="sendData">Провести сделку</button>
    </div>
    <success-component title="Обмен совершен" back-link="profile"/>
</template>

<style scoped>

</style>