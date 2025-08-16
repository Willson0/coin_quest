<script>
import {closeOverlay, endLoading, openOverlay, toLink} from "@/utils.js";
import axios from "axios";
import CryptoOverlay from "@/components/CryptoOverlay.vue";

export default {
    name: "TradeView",
    components: {CryptoOverlay},
    data () {
        return {
            dateRange: "1d|60",
            symbols: {
                "1d": "1D",
                "5d": "5D",
                "1w": "7D",
                "1m": "1M",
                "3m": "3M",
            },
            widgetKey: 0,
            selectedCurrency: 0,
            change: 0,
            firstInit: false,
        }
    },
    async mounted () {
        await this.initWidget();
    },
    methods: {toLink, closeOverlay, openOverlay,
        async initWidget() {
            if (!this.user.id) return console.log("not user");

            const d = new Date();
            const days = {
                "1d": 1,
                "5d": 5,
                "1w": 7,
                "1m": 30,
                "3m": 90,
            }
            d.setDate(d.getDate() - days[this.dateRange.split('|')[0]]); // вычитаем n дней

            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0'); // месяцы в JS с 0!
            const year = d.getFullYear();

            const date = `${day}-${month}-${year}`;
            await axios.get(`https://api.coingecko.com/api/v3/coins/${this.user.currenciesData[this.selectedCurrency].coingeckoId}/history?date=${date}`).then((response) => {
                let oldPrice = response.data.market_data.current_price.usd;
                let newPrice = this.user.currenciesData[this.selectedCurrency].price;

                this.change = ((newPrice - oldPrice) / oldPrice * 100).toFixed(2);
            }).catch((error) => {
                this.change = "error"
            })

            endLoading('trade_loading');

            const script = document.createElement('script')
            script.type = 'text/javascript'
            script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-symbol-overview.js'
            script.async = true

            script.innerHTML = JSON.stringify({
                "lineWidth": 2,
                "lineType": 1,
                "chartType": "candlesticks",
                "showVolume": false,
                "fontColor": "rgb(106, 109, 120)",
                "gridLineColor": "rgba(46, 46, 46, 0.06)",
                "volumeUpColor": "rgba(34, 171, 148, 0.5)",
                "volumeDownColor": "rgba(247, 82, 95, 0.5)",
                "backgroundColor": "#ffffff",
                "widgetFontColor": "#0F0F0F",
                "upColor": "rgba(217, 217, 217, 1)",
                "downColor": "rgba(217, 217, 217, 1)",
                "borderUpColor": "rgba(217, 217, 217, 1)",
                "borderDownColor": "rgba(217, 217, 217, 1)",
                "wickUpColor": "rgba(217, 217, 217, 1)",
                "wickDownColor": "rgba(217, 217, 217, 1)",
                "colorTheme": "light",
                "isTransparent": true,
                "locale": "ru",
                "chartOnly": true,
                "scalePosition": "no",
                "scaleMode": "Normal",
                "fontFamily": "-apple-system, BlinkMacSystemFont, Trebuchet MS, Roboto, Ubuntu, sans-serif",
                "valuesTracking": "0",
                "changeMode": "price-and-percent",
                "symbols": [
                    [
                        `${this.user.currencies.find(item => item.coingeckoId === this.user.currenciesData[this.selectedCurrency].coingeckoId).tvSymbol}|${this.symbols[this.dateRange.split('|')[0]]}`
                    ]
                ],
                "dateRanges": [
                    this.dateRange,
                ],
                "fontSize": "10",
                "headerFontSize": "medium",
                "autosize": true,
                "dateFormat": "MMM dd, yyyy",
                "width": "100%",
                "height": "100%",
                "noTimeScale": true,
                "hideDateRanges": true
            })

            this.$refs.tvContainer.appendChild(script);
        }
    },
    watch: {
        dateRange () {
            this.widgetKey += 1;
            this.$nextTick(() => {
                this.initWidget();
            });
        },
        selectedCurrency () {
            this.closeOverlay('trade_overlay', 'trade_background');
            this.widgetKey += 1;
            this.$nextTick(() => {
                this.initWidget();
            });
        },
        user () {
            this.widgetKey += 1;
            this.$nextTick(() => {
                this.initWidget();
            });
        }
    },
    computed: {
        user() {
            return this.$store.state.user;
        },
    }
}
</script>

<template>
    <div class="loading trade_loading" style="z-index: 100"></div>
    <crypto-overlay @change="selectedCurrency = $event"/>
    <div class="trade" v-if="user.id">
        <div class="trade_selected">{{ user.currenciesData[selectedCurrency]?.symbol }}</div>
        <div class="trade_coin" @click="openOverlay('trade_overlay', 'trade_background')">
            {{ user.currenciesData[selectedCurrency]?.name }}
            <svg xmlns="http://www.w3.org/2000/svg" width="8" height="15" viewBox="0 0 8 15" fill="none">
                <path d="M1 1.5L6.29289 6.79289C6.68342 7.18342 6.68342 7.81658 6.29289 8.20711L1 13.5" stroke="#1E1E22" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="trade_currency">
            ${{ user.currenciesData[selectedCurrency]?.price }}
            <span v-if="change !== 'error'" :style="{color: change >= 0 ? '#12C667' : '#DD1117' }">{{ change >= 0 ? '+' : '' }}{{ change }}%</span>
        </div>
        <div class="trade_main" ref="tvContainer" :key="widgetKey" id="trade_main">
        </div>
        <div class="trade_time">
            <div :class="{active: dateRange === '1d|60'}" @click="dateRange = '1d|60'">1 Д</div>
            <div :class="{active: dateRange === '5d|240'}" @click="dateRange = '5d|240'">5 Д</div>
            <div :class="{active: dateRange === '1w|240'}" @click="dateRange = '1w|240'">7 Д</div>
            <div :class="{active: dateRange === '1m|1D'}" @click="dateRange = '1m|1D'">1 М</div>
            <div :class="{active: dateRange === '3m|1D'}" @click="dateRange = '3m|1D'">3 М</div>
        </div>
        <button @click="toLink('shop', user.currenciesData[selectedCurrency]?.coingeckoId)">Торговля</button>
    </div>
</template>

<style scoped>

</style>