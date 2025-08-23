<script>
import axios from 'axios';
import config from "@/config.json"
import {endLoading, notify, toLink} from "@/utils.js";
import PhotoSlider from "@/components/PhotoSlider.vue";
export default {
    name: "SupportView",
    components: {PhotoSlider},
    data () {
        return {
            config: config,
            support: {},
            newMessage: "",
            pictures: [],
            mouseDown: false,
            startX: 0,
            scrollLeft: 0,
            isDragging: false,
            startIndex: null,
            sliderID: -1,
            firstLoading: true,
            interval: null,
        }
    },
    async mounted () {
        window.addEventListener('mouseup', this.mouseup);
        window.addEventListener('mousemove', this.mousemove);

        await this.fetchData();
        this.interval = setInterval(() => {
            this.fetchData();
        }, 10000);
    },
    unmounted () {
        clearInterval(this.interval);

        window.removeEventListener('mouseup', this.mouseup);
        window.removeEventListener('mousemove', this.mousemove);
    },
    methods: {
        async fetchData () {
            await axios.post(config.backend + "support", {
                initData: window.Telegram.WebApp.initData,
            }).then((response) => {
                if (this.support.id && !response.data.id) {
                    alert ("Ваша заявка была закрыта. Спасибо за обращение!");
                    return toLink("profile");
                }

                this.support = response.data;

                if (this.firstLoading) {
                    this.firstLoading = false;
                    endLoading("support_loading");

                    requestAnimationFrame(() => {
                        const elem = document.querySelector('.support_dialog');
                        elem.scrollTop = elem.scrollHeight;
                    })
                }
            }).catch((error) => {
                notify(error.response.data.message, 1);
            });
        },
        async sendMessage () {
            this.newMessage = this.newMessage.trim();
            if (this.newMessage.length === 0 && this.pictures.length === 0) return;

            this.support.dialog.push({
                from: "user",
                text: this.newMessage,
                images: this.pictures.map((file) => file.preview),
            })
            requestAnimationFrame(() => {
                const elem = document.querySelector('.support_dialog');
                elem.scrollTo({
                    top: elem.scrollHeight,
                    behavior: 'smooth'
                });
            })

            let fd = new FormData();
            fd.append("message", this.newMessage);
            fd.append("initData", window.Telegram.WebApp.initData);

            for (let img of this.pictures) fd.append("images[]", img.file);

            this.newMessage = "";
            this.pictures = [];

            await axios.post(config.backend + "support/send", fd).then((response) => {
                if (this.support.id && !response.data.id) {
                    alert ("Ваша заявка была закрыта. Спасибо за обращение!");
                    return toLink("profile");
                }

                this.support = response.data;
            }).catch((error) => {
                notify(error.response.data.message, 1);
            });
        },
        addFiles (ev) {
            for (let file of ev.target.files) {
                this.pictures.push({
                    file: file,
                    preview: URL.createObjectURL(file),
                })
            }
            ev.target.value = "";
        },
        removePhoto (index) {
            this.pictures.splice(index, 1);
        },
        mousedown(ev) {
            let el = this.$refs.newsNav;

            document.body.classList.add("grabbing");

            this.mouseDown = true;
            this.startX = ev.pageX - el.offsetLeft;
            this.scrollLeft = el.scrollLeft;
        },
        mousemove (ev) {
            if (!this.mouseDown) return;

            if (Math.abs(ev.pageX - this.startX) > 5) {
                this.isDragging = true
            }

            ev.preventDefault();
            let slider = this.$refs.newsNav;

            const x = ev.pageX - slider.offsetLeft;
            const walk = (x - this.startX) * 1; // 1 = чувствительность
            slider.scrollLeft = this.scrollLeft - walk;
        },
        mouseup (ev) {
            document.body.classList.remove("grabbing");

            this.mouseDown = false;
            setTimeout(() => {
                this.isDragging = false;
            }, 100);
        },
    },
    computed: {

    }
}
</script>

<template>
    <div class="loading support_loading"></div>
    <div class="support">
        <div class="support_header">
            Поддержка №{{ support.id }}
        </div>
        <div class="support_dialog">
            <div :style="{'max-width': message.images?.length ? '100%' : '', 'text-align': message.images?.length ? 'left' : ''}" :class="`from_${message.from}`" v-for="(message, keyMess) in support.dialog">
                <photo-slider v-if="sliderID === keyMess" @close="sliderID = -1"
                              :start-index="startIndex" :images="message.images" />
                <div class="dialog_main_photos" v-if="message.images?.length"
                     :style="{
                        gridTemplateColumns: message.images.length === 1 ? '1fr'
                          : message.images.length % 3 === 0 ? 'repeat(3, 1fr)'
                          : message.images.length % 2 === 0 ? 'repeat(2, 1fr)'
                          : 'repeat(3, 1fr)'
                      }">
                    <img :src="photo.startsWith('blob:') ? photo : config.storage + photo" :style="{
                        height: message.images.length === 1 ? '300px'
                          : message.images.length % 3 === 0 ? '100px'
                          : message.images.length % 2 === 0 ? '150px'
                          : '100px'
                      }" v-for="(photo, key) in message.images" alt="" @click="startIndex = key; sliderID = keyMess;">
                </div>
                <div class="dialog_main_text" v-if="message.text">{{ message.text }}</div>
            </div>
        </div>
        <div v-if="pictures.length" class="dialog_attachment">
            <div @mousedown.stop="mousedown" ref="newsNav">
                <div v-for="(file, key) in pictures">
                    <div class="dialog_attachment_trash" @click="removePhoto(key)">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2.75 6.16667C2.75 5.70644 3.09538 5.33335 3.52143 5.33335L6.18567 5.3329C6.71502 5.31841 7.18202 4.95482 7.36214 4.41691C7.36688 4.40277 7.37232 4.38532 7.39185 4.32203L7.50665 3.94993C7.5769 3.72179 7.6381 3.52303 7.72375 3.34536C8.06209 2.64349 8.68808 2.1561 9.41147 2.03132C9.59457 1.99973 9.78848 1.99987 10.0111 2.00002H13.4891C13.7117 1.99987 13.9056 1.99973 14.0887 2.03132C14.8121 2.1561 15.4381 2.64349 15.7764 3.34536C15.8621 3.52303 15.9233 3.72179 15.9935 3.94993L16.1083 4.32203C16.1279 4.38532 16.1333 4.40277 16.138 4.41691C16.3182 4.95482 16.8778 5.31886 17.4071 5.33335H19.9786C20.4046 5.33335 20.75 5.70644 20.75 6.16667C20.75 6.62691 20.4046 7 19.9786 7H3.52143C3.09538 7 2.75 6.62691 2.75 6.16667Z" fill="#fff"/>
                            <path d="M11.6068 21.9998H12.3937C15.1012 21.9998 16.4549 21.9998 17.3351 21.1366C18.2153 20.2734 18.3054 18.8575 18.4855 16.0256L18.745 11.945C18.8427 10.4085 18.8916 9.6402 18.45 9.15335C18.0084 8.6665 17.2628 8.6665 15.7714 8.6665H8.22905C6.73771 8.6665 5.99204 8.6665 5.55047 9.15335C5.10891 9.6402 5.15777 10.4085 5.25549 11.945L5.515 16.0256C5.6951 18.8575 5.78515 20.2734 6.66534 21.1366C7.54553 21.9998 8.89927 21.9998 11.6068 21.9998Z" fill="#fff"/>
                        </svg>
                    </div>
                    <img draggable="false" :src="file.preview" alt="">
                </div>
            </div>
        </div>
        <div class="support_input">
            <label for="attach">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none">
                    <path d="M27.6935 20.7581L18.8404 28.6482C17.8693 29.5137 16.5521 30 15.1786 30C13.8052 30 12.488 29.5137 11.5168 28.6482C10.5456 27.7827 10 26.6087 10 25.3847C10 24.1606 10.5456 22.9867 11.5168 22.1211L24.1065 10.9008C24.4272 10.6151 24.8079 10.3885 25.2268 10.234C25.6457 10.0794 26.0947 9.9999 26.5481 10C27.0015 10.0001 27.4505 10.0798 27.8694 10.2345C28.2882 10.3892 28.6688 10.616 28.9893 10.9018C29.3098 11.1876 29.5641 11.5269 29.7375 11.9002C29.9109 12.2736 30.0001 12.6738 30 13.0779C29.9999 13.482 29.9105 13.8821 29.7369 14.2554C29.5633 14.6287 29.3088 14.9678 28.9882 15.2535L16.3892 26.4821C16.0624 26.7613 15.6255 26.9139 15.1728 26.9088C14.7201 26.9037 14.2877 26.7403 13.9689 26.4537C13.6501 26.1672 13.4704 25.7805 13.4685 25.3769C13.4666 24.9734 13.6427 24.5854 13.9588 24.2965L22.9465 16.2863M16.3996 26.4718L16.3881 26.4821" stroke="#B963FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </label>
            <input type="file" @change="addFiles" multiple
                   style="display: none" accept="image/*" id="attach">
            <div>
                <input v-model="newMessage" @keyup.enter="sendMessage" type="text" placeholder="Сообщение">
                <svg @click="sendMessage" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="14" fill="#B963FF"/>
                    <path d="M16 24V8M16 8L11 13M16 8L21 13" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>