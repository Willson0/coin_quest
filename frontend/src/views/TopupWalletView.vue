<script>
export default {
    name: "TopupWalletView",
    computed: {
        user () {
            return this.$store.state.user;
        },
    }
}
</script>

<template>
    <div class="topupWallet">
        <div class="topupWalletTitle">Ваш кошелек</div>
        <div class="topupWalletContent">{{ user.wallet }}</div>
    </div>
</template>

<style scoped>

</style>