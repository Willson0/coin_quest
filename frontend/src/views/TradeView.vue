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
                "upColor": "#5AD000",
                "downColor": "#d01100",
                "borderUpColor": "#5AD000",
                "borderDownColor": "#d01100",
                "wickUpColor": "#5AD000",
                "wickDownColor": "#d01100",
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
        <button @click="toLink('support')" style="display:flex; margin-top: 20px; margin-bottom: 12px; background-color: var(--die); box-shadow: 0 0 6px 0 rgba(74, 74, 74, 0.1)">
            <div style="display:flex; flex-direction:row; margin: 0 auto; color: black; gap: 10px; font-size: 16px; line-height: 24px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="24" viewBox="0 0 18 24" fill="none">
                    <path d="M9 18.8567C8.40618 18.8568 7.83093 18.6479 7.37347 18.2661C6.91602 17.8843 6.60505 17.3534 6.4942 16.765C4.82672 16.2453 3.35829 15.2205 2.2884 13.8299C1.4807 12.781 0.925721 11.5571 0.667225 10.2549C0.408729 8.95267 0.453792 7.60768 0.798859 6.32601C1.14393 5.04435 1.77956 3.86107 2.65562 2.86951C3.53168 1.87795 4.62421 1.10524 5.84702 0.612316C7.06983 0.119393 8.38949 -0.0802539 9.7019 0.0291226C11.0143 0.138499 12.2836 0.553907 13.4096 1.24258C14.5355 1.93125 15.4875 2.87435 16.1903 3.99749C16.893 5.12063 17.3274 6.39309 17.4592 7.71449C17.5051 8.18596 17.1192 8.57 16.65 8.57C16.1808 8.57 15.8051 8.18425 15.749 7.71449C15.5973 6.50209 15.1271 5.3526 14.387 4.38495C13.647 3.4173 12.664 2.66666 11.5398 2.21068C10.4156 1.7547 9.19102 1.60996 7.99273 1.79142C6.79445 1.97289 5.666 2.47397 4.72417 3.24283C3.78234 4.01169 3.06132 5.02038 2.63574 6.16455C2.21015 7.30871 2.09545 8.54678 2.3035 9.75065C2.51156 10.9545 3.03481 12.0805 3.81911 13.012C4.60342 13.9435 5.62028 14.6467 6.7645 15.0489C6.98082 14.6509 7.29773 14.3177 7.68304 14.0832C8.06836 13.8487 8.50835 13.7212 8.95833 13.7137C9.40831 13.7062 9.85225 13.8189 10.2451 14.0404C10.6379 14.2619 10.9656 14.5843 11.1948 14.9748C11.4241 15.3654 11.5468 15.8101 11.5505 16.264C11.5542 16.7178 11.4387 17.1646 11.2158 17.5589C10.9929 17.9531 10.6704 18.2809 10.2813 18.5088C9.8921 18.7368 9.45004 18.8568 9 18.8567ZM2.2 16.285V16.2387C1.73517 15.8189 1.30951 15.3569 0.9284 14.8586C0.649116 15.281 0.500055 15.7773 0.5 16.285V17.1422C0.5 20.5214 3.662 24 9 24C14.338 24 17.5 20.5214 17.5 17.1422V16.285C17.5 15.603 17.2313 14.9488 16.7531 14.4666C16.2749 13.9843 15.6263 13.7133 14.95 13.7133H12.4C12.7723 14.2105 13.0375 14.7934 13.165 15.4278H14.95C15.1754 15.4278 15.3916 15.5181 15.551 15.6789C15.7104 15.8396 15.8 16.0576 15.8 16.285V17.1422C15.8 19.6076 13.3656 22.2856 9 22.2856C4.6344 22.2856 2.2 19.6076 2.2 17.1422V16.285ZM9 11.9989C8.02728 11.9988 7.08399 12.3353 6.3276 12.9521C5.58435 12.4929 4.97068 11.8486 4.54556 11.0812C4.12043 10.3138 3.89812 9.44905 3.9 8.57C3.90025 7.67331 4.13295 6.79226 4.57502 6.01426C5.01708 5.23625 5.65315 4.58831 6.42014 4.13471C7.18712 3.68112 8.05837 3.43763 8.94746 3.42839C9.83655 3.41915 10.7126 3.64449 11.4887 4.08205C12.2648 4.51962 12.9139 5.1542 13.3718 5.92286C13.8297 6.69151 14.0803 7.56754 14.0989 8.46403C14.1175 9.36053 13.9033 10.2463 13.4777 11.0336C13.052 11.8209 12.4297 12.4823 11.6724 12.9521C10.916 12.3353 9.97272 11.9988 9 11.9989ZM5.6 8.57C5.6 9.4794 5.95821 10.3515 6.59584 10.9946C7.23346 11.6376 8.09826 11.9989 9 11.9989C9.90174 11.9989 10.7665 11.6376 11.4042 10.9946C12.0418 10.3515 12.4 9.4794 12.4 8.57C12.4 7.6606 12.0418 6.78845 11.4042 6.14541C10.7665 5.50237 9.90174 5.14111 9 5.14111C8.09826 5.14111 7.23346 5.50237 6.59584 6.14541C5.95821 6.78845 5.6 7.6606 5.6 8.57Z" fill="#B963FF"/>
                </svg>
                Перейти в чат с экспертом
            </div>
        </button>
        <button @click="toLink('shop', user.currenciesData[selectedCurrency]?.coingeckoId)">Торговля</button>
    </div>
</template>

<style scoped>

</style>