<script>
import {toLink} from "@/utils.js";

export default {
    name: "SuccessComponent",
    async mounted () {
    },
    methods: {toLink},
    props: {
        title: {
            type: String,
            default: "Success"
        },
        description: {
            type: String,
            default: ""
        },
        backLink: {
            type: String,
            default: null
        }
    }
}
</script>

<template>
    <div class="successComponent" style="display:none; opacity: 0;">
        <div class="successComponent_info">
            <div class="successComponent_info_title">{{ title }}</div>
            <div class="successComponent_info_description">{{ description }}</div>
        </div>
        <button @click="backLink ? toLink(backLink, null, null, 0) : $emit('onBack')">Вернуться</button>
    </div>
</template>

<style scoped>

</style>