<script>
import {toLink} from "@/utils.js";

export default {
    name: "WayPaymentComponent",
    methods: {toLink},
    props: {
        svg: {
            type: String,
            default: ""
        },
        title: {
            type: String,
            default: ""
        },
        description: {
            type: String,
            default: ""
        },
        link: {
            type: String,
            default: ""
        }
    }
}
</script>

<template>
    <div class="wayPayment" @click="toLink(link)">
        <div class="wayPayment_img" v-html="svg"></div>
        <div class="wayPayment_info">
            <div class="wayPayment_title">{{ title }}</div>
            <div class="wayPayment_description">{{ description }}</div>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" width="8" height="14" viewBox="0 0 8 14" fill="none">
            <path d="M1 1L6.29289 6.29289C6.68342 6.68342 6.68342 7.31658 6.29289 7.70711L1 13" stroke="#BEBEBE" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>
</template>

<style scoped>

</style>