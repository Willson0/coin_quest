<script>
import config from "@/config.json";
import axios from "axios";
import {endLoading, formatPrice, notify, showSuccess} from "@/utils.js";
import SelectCurrencyComponent from "@/components/SelectCurrencyComponent.vue";
import SelectFiatComponent from "@/components/SelectFiatComponent.vue";
import SuccessComponent from "@/components/SuccessComponent.vue";
export default {
    name: "TopupP2PMarket",
    components: {SuccessComponent, SelectFiatComponent, SelectCurrencyComponent},
    data () {
        return {
            orders: [],
            selectedCurrency: null,
            selectedFiat: null,
            count: 1,
            changeCurrency: false,
            changeFiat: false,
            paymentSearch: "",
            payments: {
                "sbp": "СБП",
                "tbank": "Т-Банк",
                "sber": "Сбербанк",
            },
            selectedPayment: null,
            changePayment: false,
            isSafe: false,
            changeSafe: false,
            selectedOrder: null,
            inFiat: true,
        }
    },
    async mounted () {
        window.Telegram.WebApp.BackButton.offClick(window.backByQueryFunction);
        window.Telegram.WebApp.BackButton.onClick(this.backFunction);
        window.Telegram.WebApp.BackButton.show();

        await this.fetchData();
    },
    unmounted () {
        window.Telegram.WebApp.BackButton.offClick(this.backFunction);
        window.Telegram.WebApp.BackButton.onClick(window.backByQueryFunction);
    },
    methods: {
        formatPrice,
        async fetchData () {
            await axios.post(config.backend + "order", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                this.orders = response.data;
                endLoading("p2pMarket_loading");
            }).catch((error) => {
                notify(error.response.data.message ?? error.response.data, 1);
            });
        },
        backFunction () {
            if (!this.selectedCurrency) window.backByQueryFunction();
            else if (this.changeCurrency || this.changeFiat || this.changePayment
                || this.changeSafe || this.selectedOrder) {
                this.changeCurrency = false;
                this.changeFiat = false;
                this.changePayment = false;
                this.changeSafe = false;
                this.selectedOrder = null;
            } else window.backByQueryFunction();
        },
        async sendData () {
            if (!this.selectedOrder) return notify("Выберите ордер", 1);
            if (this.count <= 0) return notify("Количество должно быть больше 0", 1);

            let count = this.count;
            if (this.inFiat) count = this.count / this.selectedOrder.price;
            let price = this.count * this.selectedOrder.price;

            if (count > this.selectedOrder.remain) return notify("У Ордера нет столько криптовалюты", 1);
            if (this.selectedOrder.min_limit && price < this.selectedOrder.min_limit) return notify("Минимальный лимит: " + formatPrice(this.selectedOrder.min_limit), 1);
            if (this.selectedOrder.max_limit && price > this.selectedOrder.max_limit) return notify("Максимальный лимит: " + formatPrice(this.selectedOrder.max_limit), 1);

            await axios.post(config.backend + "order/" + this.selectedOrder, {
                initData: window.Telegram.WebApp.initData,
                count: count,
            }).then((response) => {
                let newUser = {...this.user};
                newUser.crypto = response.data.crypto;
                this.$store.dispatch("updateUser", newUser);

                showSuccess();
            }).catch((error) => {
                notify(error.response.data.message ?? error.response.data, 1);
                if (error.response.status === 404) {
                    this.clickBack();
                    this.fetchData();
                }
            });
        },
        clickBack () {
            this.selectedOrder = null;
            this.selectedFiat = null;
            this.selectedPayment = null;
            this.isSafe = false;

            this.changeCurrency = false;
            this.changeFiat = false;
            this.changePayment = false;
            this.changeSafe = false;

            this.count = 1;

            let elem = document.querySelector('.successComponent');
            elem.style.opacity = "0";
            elem.addEventListener('transitionend', () => {
                elem.style.display = "none";
            });
        }
    },
    computed: {
        user () {
            window.Telegram.WebApp.BackButton.offClick(window.backByQueryFunction);
            return this.$store.state.user;
        },
        selledCrypts () {
            if (!this.user.id) return;

            let ids = this.orders.map(ord => ord.currency_id);
            let coingeckoIds = this.user.currencies?.filter(item => ids.includes(item.id)).map(item => item.coingeckoId);
            return this.user.currenciesData?.filter(item => coingeckoIds.includes(item.coingeckoId));
        },
        filteredOrders () {
            let orders = [...this.orders];
            if (this.selectedCurrency) orders = orders.filter(ord => ord.currency_id === this.user.currencies?.find(a => a.coingeckoId === this.selectedCurrency).id)
            if (this.selectedFiat) orders = orders.filter(ord => ord.fiat_currency_id === this.selectedFiat);
            if (this.selectedPayment) orders = orders.filter(ord => ord.payment_method === this.selectedPayment);
            if (this.isSafe) orders = orders.filter(ord => ord.is_safe === true);

            return orders.sort((a,b) => b.id - a.id);
        },
    },
}
</script>

<template>
    <div class="loading p2pMarket_loading"></div>
    <select-currency-component @change="selectedCurrency = $event; changeCurrency = false;"
                               :crypts="selledCrypts" v-if="(!selectedCurrency) || changeCurrency"/>
    <select-fiat-component v-if="changeFiat" @change="selectedFiat = $event; changeFiat = false"/>
    <div class="p2pMarket">
        <div class="p2pMarket_filters">
            <div @click="changePayment = true">
                <div>
                    <div class="p2pMarket_filters_title">Оплата</div>
                    <div class="p2pMarket_filters_value">{{ selectedPayment ? payments[selectedPayment] : 'Все' }}</div>
                </div>
                <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12.6856 0H1.51523C0.610482 0 0.170842 1.1058 0.828603 1.72701L6.74232 7.31219C7.14433 7.69186 7.77816 7.6732 8.15714 7.27054L13.4138 1.68536C14.0144 1.04719 13.5619 0 12.6856 0Z" fill="#1E1E22"/>
                </svg>
            </div>
            <div @click="changeSafe = true">
                <div>
                    <div class="p2pMarket_filters_title">Верификация</div>
                    <div class="p2pMarket_filters_value">{{ isSafe ? 'Надежные' : 'Все' }}</div>
                </div>
                <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12.6856 0H1.51523C0.610482 0 0.170842 1.1058 0.828603 1.72701L6.74232 7.31219C7.14433 7.69186 7.77816 7.6732 8.15714 7.27054L13.4138 1.68536C14.0144 1.04719 13.5619 0 12.6856 0Z" fill="#1E1E22"/>
                </svg>
            </div>
            <div @click="changeFiat = true">
                <div>
                    <div class="p2pMarket_filters_title">Валюта</div>
                    <div class="p2pMarket_filters_value">{{ selectedFiat ? user.fiat_currencies?.find(a => a.id === selectedFiat)?.symbol : 'Все' }}</div>
                </div>
                <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12.6856 0H1.51523C0.610482 0 0.170842 1.1058 0.828603 1.72701L6.74232 7.31219C7.14433 7.69186 7.77816 7.6732 8.15714 7.27054L13.4138 1.68536C14.0144 1.04719 13.5619 0 12.6856 0Z" fill="#1E1E22"/>
                </svg>
            </div>
            <div @click="changeCurrency = true">
                <div>
                    <div class="p2pMarket_filters_title">Крипта</div>
                    <div class="p2pMarket_filters_value">{{ user.currenciesData?.find(item => item.coingeckoId === selectedCurrency)?.symbol }}</div>
                </div>
                <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12.6856 0H1.51523C0.610482 0 0.170842 1.1058 0.828603 1.72701L6.74232 7.31219C7.14433 7.69186 7.77816 7.6732 8.15714 7.27054L13.4138 1.68536C14.0144 1.04719 13.5619 0 12.6856 0Z" fill="#1E1E22"/>
                </svg>
            </div>
        </div>
        <div class="p2pMarket_countInput">
            <div>Сумма</div>
            <input type="number" v-model="count">
        </div>
        <div class="p2pMarket_orders">
            <div v-for="order in filteredOrders">
                <div class="p2pMarket_order_header">
                    <div class="p2pMarket_order_header_info">
                        <div class="p2pMarket_order_header_info_price">{{ formatPrice(order.price * count) }} {{ user.fiat_currencies?.find(a => a.id === order.fiat_currency_id)?.symbol }}</div>
                        <div>Цена за {{count}} {{ user.currenciesData?.find(item => item.coingeckoId === this.user.currencies?.find(cur => cur.id === order.currency_id)?.coingeckoId).symbol }}</div>
                    </div>
                    <div class="p2pMarket_order_header_actions">
                        <div>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.23077 14.1942C1.23077 14.5266 1.50629 14.7961 1.84615 14.7961H14.1538C14.4937 14.7961 14.7692 14.5266 14.7692 14.1942V9.39219C14.7692 9.05233 15.0447 8.77681 15.3846 8.77681C15.7245 8.77681 16 9.05233 16 9.39219V14.1942C16 15.1915 15.1734 16 14.1538 16H1.84615C0.826551 16 0 15.1915 0 14.1942V9.3922C0 9.05233 0.275517 8.77681 0.615385 8.77681C0.955252 8.77681 1.23077 9.05233 1.23077 9.3922V14.1942ZM11.4619 4.14724C11.681 4.40973 11.6415 4.80107 11.3743 5.01445C11.1152 5.2214 10.7382 5.18334 10.5257 4.92877L8.61539 2.64051V12.375C8.61539 12.7148 8.33987 12.9903 8 12.9903C7.66013 12.9903 7.38462 12.7148 7.38462 12.375V2.64051L5.47434 4.92877C5.26182 5.18334 4.88482 5.2214 4.62571 5.01445C4.35853 4.80107 4.31901 4.40973 4.53813 4.14724L7.23231 0.919672C7.63207 0.440769 8.36793 0.440768 8.76769 0.919672L11.4619 4.14724Z" fill="white"/>
                            </svg>
                        </div>
                        <div style="padding: 9px 22px" @click="selectedOrder = order">Купить</div>
                    </div>
                </div>
                <hr>
                <div class="p2pMarket_order_main">
                    <div class="p2pMarket_order_main_rows">
                        <div class="p2pMarket_order_main_user">
                            <img :src="order.user_avatar" alt="">
                            <div>{{order.user}}</div>
                        </div>
                        <div>Доступно</div>
                        <div>Лимиты</div>
                        <div>Методы оплаты</div>
                    </div>
                    <div class="p2pMarket_order_main_values">
                        <div style="line-height: 28px">Сделок: {{ order.count_deals }} - 100%</div>
                        <div>{{ order.remain }} BTC</div>
                        <div>{{ order.min_limit ?? "0" }} - {{ order.max_limit ?? "∞" }} RUB</div>
                        <div>{{ payments[order.payment_method] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="selectFiat" style="gap: 12px;" v-if="changePayment">
        <div class="courses_search">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M13.24 13.2401C15.58 10.9001 15.58 7.10012 13.24 4.75012C10.9 2.41012 7.1 2.41012 4.75 4.75012C2.41 7.09012 2.41 10.8901 4.75 13.2401C7.09 15.5801 10.89 15.5801 13.24 13.2401Z" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.5 13.5L21 21" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input v-model="paymentSearch" placeholder="Поиск">
        </div>
        <div class="topup_payment_selectAll">Выбрать все</div>
        <div class="trade_overlay_main selectFiat_main">
            <div v-for="(value, key) in Object.fromEntries(Object.entries(payments).filter((([k, val]) => val.toLowerCase().trim().includes(paymentSearch.toLowerCase().trim()))))" @click="selectedPayment = key">
                <div class="topup_checkbox" :class="{'active': selectedPayment === key || !selectedPayment}">
                    <svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.5 5.13636L2.48736 7.09678C2.95171 7.55483 3.7232 7.45291 4.05267 6.88999L7.5 1" stroke="white" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="trade_overlay_main_info p2p_market">
                    <div style="color: black">{{ value }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="selectFiat topup_payments" style="gap: 12px;" v-if="changeSafe">
        <div class="trade_overlay_main selectFiat_main">
            <div @click="isSafe = true">
                <div class="topup_checkbox" :class="{'active': isSafe === true}">
                    <svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.5 5.13636L2.48736 7.09678C2.95171 7.55483 3.7232 7.45291 4.05267 6.88999L7.5 1" stroke="white" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="trade_overlay_main_info">
                    <div>Надежный продавец</div>
                    <div style="font-weight: 400; line-height: 16px">Пользователи с высоким рейтингом и подтвержденной личностью</div>
                </div>
            </div>
            <div @click="isSafe = false">
                <div class="topup_checkbox" :class="{'active': isSafe === false}">
                    <svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.5 5.13636L2.48736 7.09678C2.95171 7.55483 3.7232 7.45291 4.05267 6.88999L7.5 1" stroke="white" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="trade_overlay_main_info">
                    <div>Все продавцы</div>
                    <div style="font-weight: 400; line-height: 16px">Все пользователи P2P Маркета с подтвержденной личностью</div>
                </div>
            </div>
        </div>
    </div>
    <div class="p2pMarket_invoice_container" v-if="selectedOrder">
        <div  class="p2pMarket_invoice">
            <div class="p2pMarket_invoice_user">
                <div class="p2pMarket_invoice_user_profile">
                    <img :src="selectedOrder.user_avatar" alt="">
                    <div>{{ selectedOrder.user }}</div>
                </div>
                <div class="p2pMarket_invoice_user_status">
                    <div class="p2pMarket_invoice_user_status_circle"></div>
                    <div class="p2pMarket_invoice_user_status_text">В сети</div>
                </div>
            </div>
            <div class="p2pMarket_invoice_info">
                <div>
                    <div>{{ selectedOrder.user }}</div>
                    <div>сделок: {{ selectedOrder.count_deals }} - 100%</div>
                </div>
                <hr>
                <div>
                    <div>Цена за 1 {{ user.currenciesData?.find(item => item.coingeckoId === this.user.currencies?.find(cur => cur.id === selectedOrder.currency_id)?.coingeckoId).symbol }}</div>
                    <div>{{ formatPrice(selectedOrder.price) }} {{ user.fiat_currencies?.find(a => a.id === selectedOrder.fiat_currency_id).symbol }}</div>
                </div>
            </div>
            <div class="p2pMarket_invoice_input_container">
                <div class="p2pMarket_invoice_input">
                    <input v-model="count" type="number" placeholder="Сумма">
                    <div>
                        <div @click="inFiat = true" :class="{'active': inFiat}">{{ user.fiat_currencies?.find(a => a.id === selectedOrder.fiat_currency_id).symbol }}</div>
                        <div @click="inFiat = false" :class="{'active': !inFiat}">{{ user.currenciesData?.find(item => item.coingeckoId === this.user.currencies?.find(cur => cur.id === selectedOrder.currency_id)?.coingeckoId).symbol }}</div>
                    </div>
                </div>
                <div class="p2pMarket_invoice_input_limits">Лимиты {{ selectedOrder.min_limit ?? "0" }} - {{ selectedOrder.max_limit ?? "∞" }} {{ user.fiat_currencies?.find(a => a.id === selectedOrder.fiat_currency_id).symbol }}</div>
            </div>
            <div class="p2pMarket_invoice_payment_container">
                <div class="p2pMarket_invoice_info">
                    <div>
                        <div>Метод оплаты</div>
                        <div>{{ payments[selectedOrder.payment_method] }}</div>
                    </div>
                    <hr>
                    <div>
                        <div>Оплата в течение</div>
                        <div>15 мин</div>
                    </div>
                </div>
                <div class="p2pMarket_invoice_payment_info">Переведите оплату в течении 15 минут после подтверждения сделки продавцем</div>
            </div>
            <button @click="sendData">Провести оплату</button>
        </div>
    </div>
    <success-component title="Сделка успешно совершена" :description="`В течение 15 минут 1 BTC будет начислен`" back-link="" @onBack="clickBack"/>
</template>

<style scoped>

</style>