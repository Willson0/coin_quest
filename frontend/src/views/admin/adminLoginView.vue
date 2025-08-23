<script>
import config from "@/config.json";
export default {
    name: "adminLoginView",
    data () {
        return {
            username: '',
            password: '',
        }
    },
    mounted () {
        document.body.classList.add("no-scroll");
    },
    methods: {
        login() {
            fetch (config.backend + 'admin/login', {
                method: 'POST',
                headers: {
                    "Content-Type": "application/json",
                    "Access-Control-Allow-Origin": '127.0.0.1:8000',
                },
                body: JSON.stringify({
                    "login": this.username,
                    "password": this.password,
                }),
                credentials: 'include',
            }).then((response) => {
                if (response.status === 403) return alert ("Неправильный логин или пароль");
                else if (response.ok) this.$router.push({name:'admin'});
                else alert ("Произошла непредвиденная ошибка. Обратитесь к разработчику");
            })
        }
    }
}
</script>

<template>
    <div class="adminLogin">
        <div class="adminLogin_main">
            <div class="adminLogin_main_site">
                <h2>CoinQuest</h2>
            </div>
            <div class="adminLogin_main_title">
                <h1>Welcome back!</h1>
                <p>Please enter your credentials to access the admin panel and manage the content of the website.</p>
            </div>
            <form class="adminLogin_main_form" @submit.prevent="login">
                <input required v-model="username" type="text" placeholder="Username">
                <input required v-model="password" type="password" placeholder="Password">
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
</template>

<style scoped>

</style>