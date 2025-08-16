<script>
import {closeOverlay, notify, openOverlay, showOverlay} from "@/utils.js";
import CryptoOverlay from "@/components/CryptoOverlay.vue";
import axios from "axios";
import config from "@/config.json";

export default {
    name: "ShopView",
    components: {CryptoOverlay},
    mounted () {
    },
    methods: {
        openOverlay,
        async sendData () {
            if (!this.firstCoin || !this.secondCoin) return notify('Заполните все поля', 1);
            if (this.coin.coingeckoId === this.user.currenciesData[this.selectedCurrency]?.coingeckoId) return notify('Одинаковые криптовалюты', 1);

            if (this.isSale) {
                if (!this.user.crypto?.[this.coin.coingeckoId] || this.user.crypto?.[this.coin.coingeckoId] < Number(this.firstCoin))
                    return notify('Недостаточно ' + this.coin.symbol + ' на счету', 1);
            } else {
                if (!this.user.crypto?.[this.user.currenciesData[this.selectedCurrency]?.coingeckoId] || this.user.crypto?.[this.user.currenciesData[this.selectedCurrency]?.coingeckoId] < Number(this.secondCoin))
                    return notify('Недостаточно ' + this.user.currenciesData[this.selectedCurrency]?.symbol + ' на счету', 1);
            }

            await axios.post(config.backend + "trade", {
                initData: window.Telegram.WebApp.initData,
                marketed: !this.isSale ? this.user.currenciesData[this.selectedCurrency]?.coingeckoId : this.coin.coingeckoId,
                marketed_count: !this.isSale ? this.secondCoin : this.firstCoin,
                purchasable: !this.isSale ? this.coin.coingeckoId : this.user.currenciesData[this.selectedCurrency]?.coingeckoId,
            }).then((response) => {
                let newUser = {...this.user};
                newUser.crypto = response.data.crypto;
                this.$store.dispatch("updateUser", newUser);

                notify('Успешно', 0);
            }).catch((error) => {
                if (error.response) {
                    notify(error.response.data.message, 1);
                }
            });
        },
        inputFirstCoin() {
            let firstPrice = this.coin.price;
            let secondPrice = this.user.currenciesData[this.selectedCurrency].price;

            this.secondCoin = (firstPrice / secondPrice) * Number(this.firstCoin);
        },
        inputSecondCoin () {
            let firstPrice = this.coin.price;
            let secondPrice = this.user.currenciesData[this.selectedCurrency].price;

            this.firstCoin = (secondPrice / firstPrice) * Number(this.secondCoin);
        }
    },
    data () {
        return {
            isSale: false,
            credit: 5,
            selectedCurrency: 0,
            firstCoin: '',
            secondCoin: '',
        }
    },
    computed: {
        user() {
            return this.$store.state.user;
        },
        coin () {
            return this.user.currenciesData?.find(item => item.coingeckoId === this.$route.query.id);
        }
    },
    watch: {
        selectedCurrency () {
            let firstPrice = this.coin.price;
            let secondPrice = this.user.currenciesData[this.selectedCurrency].price;

            this.secondCoin = (firstPrice / secondPrice) * Number(this.firstCoin);
        },
    }
}
</script>

<template>
    <crypto-overlay @change="selectedCurrency = $event"/>
    <div class="shop" v-if="coin">
        <div class="shop_selector">
            <div class="shop_selector_background" :class="{'active': isSale}"></div>
            <div class="shop_selector_items" :class="{'active': !isSale}" @click="isSale = false">Покупка</div>
            <div class="shop_selector_items" :class="{'active': isSale}" @click="isSale = true">Продажа</div>
        </div>
        <div class="shop_inputs">
            <div>
                <input @input="inputFirstCoin" v-model="firstCoin" type="number" placeholder="Автозаполнение">
                <div>{{ coin.symbol }}</div>
            </div>
            <div>
                <input @input="inputSecondCoin" v-model="secondCoin" type="number" placeholder="Введите сумму">
                <div style="cursor:pointer;" @click="openOverlay('trade_overlay', 'trade_background')">
                    {{ user.currenciesData[selectedCurrency].symbol }}
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="8" viewBox="0 0 14 8" fill="none">
                        <path d="M12.6856 0H1.51523C0.610482 0 0.170842 1.1058 0.828603 1.72701L6.74232 7.31219C7.14433 7.69186 7.77816 7.6732 8.15714 7.27054L13.4138 1.68536C14.0144 1.04719 13.5619 0 12.6856 0Z" fill="#1E1E22"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="shop_credit">
            <div class="shop_credit_title">Кредитное плечо</div>
            <div class="trade_time">
                <div :class="{active: credit === 5}" @click="credit = 5">5x</div>
                <div :class="{active: credit === 10}" @click="credit = 10">10x</div>
                <div :class="{active: credit === 15}" @click="credit = 15">15x</div>
                <div :class="{active: credit === 20}" @click="credit = 20">20x</div>
            </div>
        </div>
        <button @click="sendData">Купить</button>
    </div>
</template>

<style scoped>

</style>