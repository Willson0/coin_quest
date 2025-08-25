<script>
import QRCode from 'qrcode';
import config from "@/config.json";
export default {
    name: "TopupWalletView",
    computed: {
        user () {
            return this.$store.state.user;
        },
    },
    mounted () {
        this.initQr();
    },
    methods: {
        initQr () {
            if (!this.user.id) return;

            let url = `https://t.me/${config.bot}?startapp=wallet_${encodeURIComponent(this.user.wallet)}`;
            QRCode.toCanvas(this.$refs.qrcanvas, url, function (error) {
                if (error) console.error(error);
            });
        }
    },
    watch: {
        user () {
            this.initQr();
        }
    }
}
</script>

<template>
    <div class="topupWallet">
        <div class="topupWalletTitle">Ваш кошелек</div>
        <canvas ref="qrcanvas" class="topupWallet_qr"></canvas>
        <div class="topupWalletContent">{{ user.wallet }}</div>
    </div>
</template>

<style scoped>

</style>